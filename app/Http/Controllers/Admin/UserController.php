<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        //
    }

    private function checkAdminAccess()
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    /**
     * Display a listing of users.
     */
    public function index()
    {
        $this->checkAdminAccess();

        $users = User::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->checkAdminAccess();

        return view('admin.users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $this->checkAdminAccess();

        \Log::info('User creation attempt', ['request_data' => $request->all()]);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'api_key' => 'nullable|string|max:255',
                'api_operator_id' => 'nullable|string|max:255',
                'api_base_url' => 'nullable|url|max:255',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['is_admin'] = $request->has('is_admin');
            $validated['is_active'] = $request->has('is_active');
            $validated['email_verified_at'] = now();

            \Log::info('About to create user', ['validated_data' => array_merge($validated, ['password' => '[HIDDEN]'])]);

            $user = User::create($validated);

            \Log::info('User created successfully', ['user_id' => $user->id]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            \Log::error('User creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->checkAdminAccess();

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->checkAdminAccess();

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        \Log::info('UPDATE METHOD CALLED for user ID: ' . $user->id);

        $this->checkAdminAccess();

        // Debug logging
        \Log::info('User update request data:', $request->all());

        // Manual validation and update
        $data = [];

        // Basic fields
        $data['name'] = $request->input('name');
        $data['email'] = $request->input('email');

        // Password handling
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        // API credentials - only update if not masked
        if ($request->filled('api_base_url')) {
            $data['api_base_url'] = $request->input('api_base_url');
        }

        if ($request->filled('api_key') && $request->input('api_key') !== '••••••••••••') {
            $data['api_key'] = $request->input('api_key');
        }

        if ($request->filled('api_operator_id')) {
            $data['api_operator_id'] = $request->input('api_operator_id');
        }

        // Boolean fields
        $data['is_admin'] = $request->has('is_admin');
        $data['is_active'] = $request->has('is_active');

        \Log::info('Data to update:', $data);

        try {
            $user->update($data);
            \Log::info('User updated successfully with all fields');

            // If we're updating the current user's API credentials, refresh the API service
            if (auth()->id() === $user->id &&
                (isset($data['api_key']) || isset($data['api_operator_id']) || isset($data['api_base_url']))) {

                // Clear the singleton from the container to force recreation with new credentials
                app()->forgetInstance(\App\Services\PostingApiService::class);
                \Log::info('API service instance cleared due to credential update');
            }
        } catch (\Exception $e) {
            \Log::error('Update failed:', ['error' => $e->getMessage()]);
            throw $e;
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $this->checkAdminAccess();
        // Prevent deleting the last admin
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return redirect()->back()
                ->with('error', 'Cannot delete the last admin user.');
        }

        // Prevent users from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        $this->checkAdminAccess();
        // Prevent deactivating the last admin
        if ($user->is_admin && $user->is_active && User::where('is_admin', true)->where('is_active', true)->count() <= 1) {
            return redirect()->back()
                ->with('error', 'Cannot deactivate the last active admin user.');
        }

        // Prevent users from deactivating themselves
        if ($user->id === auth()->id() && $user->is_active) {
            return redirect()->back()
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->back()
            ->with('success', "User has been {$status} successfully.");
    }
}
