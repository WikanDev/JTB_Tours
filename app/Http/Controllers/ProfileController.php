<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    
    public function edit(Request $request)
    {
        $user = Auth::user();

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'max:2048'], 
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        
        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        
        if ($request->hasFile('profile_photo')) {
            
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo = $path;
        }

        
        if (!empty($validated['password'])) {
            
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
