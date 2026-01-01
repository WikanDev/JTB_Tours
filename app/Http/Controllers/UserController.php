<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $q = User::query();

            if ($request->filled('role')) {
                $q->where('role', $request->role);
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function($w) use ($s){
                    $w->where('name','like',"%{$s}%")
                      ->orWhere('email','like',"%{$s}%")
                      ->orWhere('phone','like',"%{$s}%");
                });
            }

            $users = $q->orderBy('role')->orderBy('name')->paginate(20)->withQueryString();

            
            $roles = ['super_admin','admin','staff','driver','guide'];

            return view('users.index', compact('users','roles'));
        } catch (\Throwable $e) {
            Log::error('User.index error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString(), 'query'=>$request->all()]);
            return redirect()->back()->with('error','Gagal memuat daftar user.');
        }
    }

    
    public function create()
    {
        try {
            $roles = ['super_admin','admin','staff','driver','guide'];
            return view('users.create', compact('roles'));
        } catch (\Throwable $e) {
            Log::error('User.create error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form tambah user.');
        }
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::in(['super_admin','admin','staff','driver','guide'])],
            'join_date' => 'nullable|date',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:25',
            'password' => 'required|confirmed|min:8',
            'monthly_work_limit' => 'nullable|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            
            $data['used_hours'] = 0;
            $data['status'] = in_array($data['role'], ['driver','guide']) ? 'offline' : 'offline';

            
            if (!isset($data['monthly_work_limit'])) {
                $data['monthly_work_limit'] = $data['role'] === 'driver' || $data['role'] === 'guide' ? 200 : null;
            }

            
            User::create($data);

            DB::commit();
            return redirect()->route('users.index')->with('success','User berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User.store error: '.$e->getMessage(), ['payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal membuat user.');
        }
    }

    
    public function show(User $user)
    {
        try {
            return view('users.show', compact('user'));
        } catch (\Throwable $e) {
            Log::error('User.show error: '.$e->getMessage(), ['user_id'=>$user->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka user.');
        }
    }

    
    public function edit(User $user)
    {
        try {
            $roles = ['super_admin','admin','staff','driver','guide'];
            return view('users.edit', compact('user','roles'));
        } catch (\Throwable $e) {
            Log::error('User.edit error: '.$e->getMessage(), ['user_id'=>$user->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form edit user.');
        }
    }

    
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::in(['super_admin','admin','staff','driver','guide'])],
            'join_date' => 'nullable|date',
            'email' => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'phone' => 'nullable|string|max:25',
            'password' => 'nullable|confirmed|min:8',
            'monthly_work_limit' => 'nullable|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            if (empty($data['password'])) {
                unset($data['password']);
            }

            
            if (isset($data['role']) && !in_array($data['role'], ['driver','guide'])) {
                $data['monthly_work_limit'] = null;
                $data['status'] = $user->status ?? 'offline';
            } else {
                
                if (in_array($data['role'] ?? $user->role, ['driver','guide']) && !isset($data['monthly_work_limit'])) {
                    $data['monthly_work_limit'] = $user->monthly_work_limit ?? 200;
                }
            }

            $user->update($data);

            DB::commit();
            return redirect()->route('users.index')->with('success','User berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User.update error: '.$e->getMessage(), ['user_id'=>$user->id, 'payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal memperbarui user.');
        }
    }

    
    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            
            if (Auth::check() && Auth::id() === $user->id) {
                return redirect()->back()->with('error','Kamu tidak bisa menghapus akun sendiri.');
            }

            
            if ($user->role === 'super_admin') {
                $count = User::where('role','super_admin')->count();
                if ($count <= 1) {
                    return redirect()->back()->with('error','Tidak dapat menghapus Super Admin terakhir.');
                }
            }

            
            $hasAssignments = $user->assignmentsAsDriver()->exists() || $user->assignmentsAsGuide()->exists();
            if ($hasAssignments) {
                return redirect()->back()->with('error','Tidak dapat menghapus user yang memiliki assignment.');
            }

            $user->delete();
            DB::commit();
            return redirect()->route('users.index')->with('success','User dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User.destroy error: '.$e->getMessage(), ['user_id'=>$user->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal menghapus user.');
        }
    }
}
