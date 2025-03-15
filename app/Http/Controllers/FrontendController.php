<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;

class FrontendController extends Controller
{
    public function index(Request $request)
    {
        $store = User::where('username', $request->username)->first();
        if (!$store) {
            abort(404);
        }

        $populars = Product::where('user_id', $store->id)->where('is_popular', true)->get();
        $products = Product::where('user_id', $store->id)->where('is_popular', false)->get();

        return view('pages.index', compact('store', 'populars', 'products'));
    }  
}
