<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartController
{
    private function getCartIdentifier()
    {
        if (Auth::check()) {
            return ['user_id' => Auth::id()];
        }
        
        $sessionId = Session::getId();
        return ['session_id' => $sessionId];
    }

    private function getOrCreateCart()
    {
        $identifier = $this->getCartIdentifier();
        
        $cart = DB::table('carts');
        if (isset($identifier['user_id'])) {
            $cart->where('user_id', $identifier['user_id']);
        } else {
            $cart->where('session_id', $identifier['session_id']);
        }
        $cart = $cart->first();

        if (!$cart) {
            $cartId = DB::table('carts')->insertGetId(array_merge($identifier, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            return DB::table('carts')->where('id', $cartId)->first();
        }
        
        return $cart;
    }

    public function getCartData()
    {
        $cart = $this->getOrCreateCart();
        
        $items = DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->where('cart_items.cart_id', $cart->id)
            ->select('cart_items.*', 'products.name', 'products.image')
            ->orderBy('cart_items.created_at', 'desc')
            ->get();
            
        $itemIds = $items->pluck('id');
        $itemToppings = DB::table('cart_item_toppings')
            ->join('toppings', 'cart_item_toppings.topping_id', '=', 'toppings.id')
            ->whereIn('cart_item_toppings.cart_item_id', $itemIds)
            ->select('cart_item_toppings.cart_item_id', 'toppings.name')
            ->get();
            
        $total = 0;
        foreach ($items as $item) {
            $item->toppings = $itemToppings->where('cart_item_id', $item->id)->values();
            $total += $item->unit_price * $item->quantity;
        }

        return response()->json([
            'success' => true,
            'items' => $items,
            'count' => count($items),
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', '.') . 'đ'
        ]);
    }

    public function add(Request $request)
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 1);
        $sizeName = $request->input('size_name');
        $sugarLevel = $request->input('sugar_level');
        $iceLevel = $request->input('ice_level');
        $toppingIds = $request->input('toppings', []);
        
        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'Product ID is missing']);
        }

        $product = DB::table('products')->where('id', $productId)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        // Tính giá dựa theo size
        $unitPrice = $product->base_price;
        if ($sizeName) {
            $sizeRecord = DB::table('product_sizes')
                ->where('product_id', $productId)
                ->where('size_name', $sizeName)
                ->first();
            if ($sizeRecord) {
                $unitPrice += $sizeRecord->price_adjustment;
            }
        }
        
        // Tính giá dựa theo topping
        $tops = [];
        if (!empty($toppingIds)) {
            $tops = DB::table('toppings')->whereIn('id', $toppingIds)->get();
            foreach ($tops as $t) {
                $unitPrice += $t->price;
            }
        }

        $cart = $this->getOrCreateCart();

        // Tìm các items cùng product_id, size, sugar, ice
        $potentialItems = DB::table('cart_items')
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('size_name', $sizeName)
            ->where('sugar_level', $sugarLevel)
            ->where('ice_level', $iceLevel)
            ->get();

        $existingItem = null;
        $reqTops = $toppingIds;
        sort($reqTops);

        foreach ($potentialItems as $pi) {
            $piToppings = DB::table('cart_item_toppings')
                ->where('cart_item_id', $pi->id)
                ->pluck('topping_id')
                ->toArray();
                
            sort($piToppings);
            
            if ($piToppings == $reqTops) {
                $existingItem = $pi;
                break;
            }
        }

        if ($existingItem) {
            DB::table('cart_items')
                ->where('id', $existingItem->id)
                ->update([
                    'quantity' => $existingItem->quantity + $quantity,
                    'updated_at' => now()
                ]);
        } else {
            $newItemId = DB::table('cart_items')->insertGetId([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'size_name' => $sizeName,
                'sugar_level' => $sugarLevel,
                'ice_level' => $iceLevel,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            if (!empty($tops)) {
                $inserts = [];
                foreach ($tops as $t) {
                    $inserts[] = [
                        'cart_item_id' => $newItemId,
                        'topping_id' => $t->id,
                        'price' => $t->price
                    ];
                }
                DB::table('cart_item_toppings')->insert($inserts);
            }
        }

        return $this->getCartData();
    }

    public function remove(Request $request)
    {
        $itemId = $request->input('item_id');
        
        if ($itemId) {
            DB::table('cart_items')->where('id', $itemId)->delete();
        }

        return $this->getCartData();
    }

    public function update(Request $request)
    {
        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity');
        
        if ($itemId && $quantity > 0) {
            DB::table('cart_items')
                ->where('id', $itemId)
                ->update([
                    'quantity' => $quantity,
                    'updated_at' => now()
                ]);
        }

        return $this->getCartData();
    }

    public function addAll(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        }

        $favorites = DB::table('favorites')
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->select('products.id', 'products.base_price')
            ->get();

        if ($favorites->isEmpty()) {
             return $this->getCartData();
        }

        $cart = $this->getOrCreateCart();

        foreach ($favorites as $product) {
            $existingItem = DB::table('cart_items')
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existingItem) {
                DB::table('cart_items')
                    ->where('id', $existingItem->id)
                    ->update([
                        'quantity' => $existingItem->quantity + 1,
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('cart_items')->insert([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->base_price,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return $this->getCartData();
    }
}
