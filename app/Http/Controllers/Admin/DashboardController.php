<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'products' => \App\Models\Product::count(),
            'users' => User::where('role', 'user')->count(),
            'orders' => Order::count(),
            'revenue' => Order::whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->sum('total'),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(8)->get();
        $lowStock = ProductVariant::with('product')
            ->where('stock', '<', 5)
            ->orderBy('stock')
            ->take(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStock'));
    }
}
