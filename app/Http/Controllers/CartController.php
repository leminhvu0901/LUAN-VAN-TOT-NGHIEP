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
            
        $total = 0;
        foreach ($items as $item) {
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
        
        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'Product ID is missing']);
        }

        $product = DB::table('products')->where('id', $productId)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        $cart = $this->getOrCreateCart();

        // Check if exact item exists
        $existingItem = DB::table('cart_items')
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingItem) {
            DB::table('cart_items')
                ->where('id', $existingItem->id)
                ->update([
                    'quantity' => $existingItem->quantity + $quantity,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('cart_items')->insert([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product->base_price,
                'created_at' => now(),
                'updated_at' => now()
            ]);
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
