<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserGroupController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:user_groups,name',
            'description' => 'nullable|string|max:1000',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group = UserGroup::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        if ($request->user_ids) {
            $group->users()->attach($request->user_ids, [
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Group created successfully!');
    }

    public function show(UserGroup $group)
    {
        $group->load(['users', 'creator']);
        $availableUsers = User::where('is_admin', false)
            ->active()
            ->whereNotIn('id', $group->users->pluck('id'))
            ->get();

        return response()->json([
            'group' => $group,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function update(Request $request, UserGroup $group)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:user_groups,name,' . $group->id,
            'description' => 'nullable|string|max:1000',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'is_active' => 'boolean',
        ]);

        $group->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->has('user_ids')) {
            $group->users()->detach();
            if ($request->user_ids) {
                $group->users()->attach($request->user_ids, [
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Group updated successfully!');
    }

    public function destroy(UserGroup $group)
    {
        $group->delete();

        return redirect()->back()->with('success', 'Group deleted successfully!');
    }
}