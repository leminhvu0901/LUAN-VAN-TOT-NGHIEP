<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController
{
    public function index()
    {
        return view('pages.profile');
    }

    public function toggleFavorite(Request $request)
    {
        $productId = $request->input('product_id');
        $userId = \Illuminate\Support\Facades\Auth::id();

        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'Product ID is missing']);
        }

        $exists = \Illuminate\Support\Facades\DB::table('favorites')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        $status = '';
        if ($exists) {
            \Illuminate\Support\Facades\DB::table('favorites')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();
            $status = 'removed';
        } else {
            \Illuminate\Support\Facades\DB::table('favorites')->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => now(),
            ]);
            $status = 'added';
        }

        // Fetch updated favorites
        $favorites = \Illuminate\Support\Facades\DB::table('favorites')
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', $userId)
            ->select('products.*', 'favorites.id as favorite_id')
            ->get();

        return response()->json([
            'success' => true, 
            'status' => $status,
            'items' => $favorites,
            'count' => count($favorites)
        ]);
    }
}
