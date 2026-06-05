<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'user')->withCount('orders')->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        abort_unless($user->role === 'user', 404);
        $user->load(['addresses', 'orders' => fn ($q) => $q->latest()->take(10)]);

        return view('admin.users.show', compact('user'));
    }
}
