<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $decaySeconds = 60 * 5; 

    
    public function showLoginForm()
    {
        return view('auth.login');
    }

    
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        $email = (string) $request->input('email');
        $throttleKey = $this->throttleKey($email, $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['email' => "Terlalu banyak percobaan login. Coba lagi setelah {$seconds} detik."]);
        }

        $credentials = $request->only('email','password');
        $remember = $request->boolean('remember');

        try {
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                RateLimiter::clear($throttleKey);

                
                $user = Auth::user();
                if ($user) {
                    
                    $user->status = 'online';
                    $user->saveQuietly();
                }

                
                return $this->redirectToRole($user);
            }

            
            RateLimiter::hit($throttleKey, $this->decaySeconds);

            $remaining = max(0, $this->maxAttempts - RateLimiter::attempts($throttleKey));
            $message = 'Email atau password salah.';
            if ($remaining > 0) $message .= " Sisa percobaan: {$remaining}.";

            return back()->withErrors(['email' => $message])->onlyInput('email');
        } catch (\Throwable $e) {
            Log::error('AuthController.login error: '.$e->getMessage(), ['email'=>$email,'trace'=>$e->getTraceAsString()]);
            return back()->withErrors(['email' => 'Terjadi kesalahan saat proses login. Silakan coba lagi.'])->onlyInput('email');
        }
    }

    
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'phone' => ['nullable','string','max:25'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'], 
                'role' => 'staff', 
                'join_date' => now()->toDateString(),
                'monthly_work_limit' => 200,
                'used_hours' => 0,
                'status' => 'offline',
            ]);

            Auth::login($user);
            DB::commit();

            
            $user->status = 'online';
            $user->saveQuietly();

            return $this->redirectToRole($user);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AuthController.register error: '.$e->getMessage(), ['payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return back()->withInput()->withErrors(['email' => 'Gagal membuat akun, coba lagi.']);
        }
    }

    
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user) {
                
                $user->status = 'offline';
                $user->saveQuietly();
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success','Kamu berhasil logout.');
        } catch (\Throwable $e) {
            Log::error('AuthController.logout error: '.$e->getMessage(), ['user_id'=>optional(Auth::user())->id, 'trace'=>$e->getTraceAsString()]);
            
            try {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Throwable $ex) {
                
            }
            return redirect()->route('login')->with('error','Logout gagal sepenuhnya tapi sesi sudah dibersihkan.');
        }
    }

    
    protected function redirectToRole(?User $user): RedirectResponse
    {
        
        if (! $user) {
            return redirect()->route('dashboard');
        }

        
        if ($user->role === 'super_admin' || $user->role === 'admin' || $user->role === 'staff') {
            return redirect()->intended(route('dashboard'));
        }

        if (in_array($user->role, ['driver','guide'])) {
            
            return redirect()->intended(route('assignments.my'));
        }

        
        return redirect()->intended(route('dashboard'));
    }

    
    protected function throttleKey(string $email, ?string $ip = null): string
    {
        $ipPart = $ip ? (string) $ip : request()->ip();
        return Str::lower($email).'|'.$ipPart;
    }
}
