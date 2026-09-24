<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class AdminFirefighterController extends Controller
{
    public function index()
    {
        $firefighters = User::latest()->get();
        return view('admin.firefighters.index', compact('firefighters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'badge_number' => ['required', 'string', 'max:50', 'unique:users,badge_number'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role' => ['required', 'in:Admin,Firefighter'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'badge_number' => $request->badge_number,
            'email' => $request->email,
            'rank' => $request->role, // Synchronizes the rank column in the database
            'password' => Hash::make($request->password),
        ]);

        // Assign Role via Spatie
        if (method_exists($user, 'assignRole')) {
            $role = Role::firstOrCreate(['name' => $request->role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return redirect()->back()->with('status', "Personnel {$user->name} registered successfully!");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'badge_number' => ['required', 'string', 'max:50', 'unique:users,badge_number,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:Admin,Firefighter'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'name' => $request->name,
            'badge_number' => $request->badge_number,
            'email' => $request->email,
            'rank' => $request->role, // Synchronizes the rank column in the database
            'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
        ]);

        // Sync Spatie Role
        if (method_exists($user, 'syncRoles')) {
            $role = Role::firstOrCreate(['name' => $request->role, 'guard_name' => 'web']);
            $user->syncRoles([$role]);
        }

        return redirect()->back()->with('status', "Personnel {$user->name} updated successfully!");
    }
}