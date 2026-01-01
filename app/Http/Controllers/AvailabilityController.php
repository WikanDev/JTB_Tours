<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvailabilityController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $q = User::whereIn('role', ['driver','guide']);

            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            }

            if ($request->filled('status')) {
                $q->where('status', $request->status); 
            }

            $users = $q->orderBy('role')->orderBy('name')->paginate(25)->withQueryString();

            
            $month = now()->month; $year = now()->year;
            $schedules = WorkSchedule::where('month', $month)->where('year', $year)
                ->whereIn('user_id', $users->pluck('id')->toArray())->get()->keyBy('user_id');

            return view('availability.index', compact('users','schedules'));
        } catch (\Throwable $e) {
            Log::error('Availability.index error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal memuat daftar availability.');
        }
    }

    
    public function toggle(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            
            if (! in_array($user->role, ['driver','guide'])) {
                abort(403, 'Unauthorized');
            }

            
            $newStatus = $user->status === 'online' ? 'offline' : 'online';

            
            $user->status = $newStatus;
            $user->save();

            DB::commit();

            
            return redirect()->back()->with('success','Status Anda: '.$newStatus);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Availability.toggle error: '.$e->getMessage(), ['user_id'=>Auth::id(), 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal mengubah status availability.');
        }
    }

    
    public function forceChange(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|in:online,offline'
        ]);

        DB::beginTransaction();
        try {
            $actor = Auth::user();
            if (! in_array($actor->role, ['super_admin','admin'])) {
                abort(403, 'Unauthorized');
            }

            if (! in_array($user->role, ['driver','guide'])) {
                return redirect()->back()->with('error', 'Hanya driver & guide yang dapat diubah statusnya.');
            }

            $user->status = $request->status;
            $user->save();

            DB::commit();

            Log::info('Availability.forceChange', ['actor_id'=>$actor->id, 'target_id'=>$user->id, 'status'=>$user->status]);

            return redirect()->back()->with('success','Status user berhasil diubah menjadi '.$user->status);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Availability.forceChange error: '.$e->getMessage(), ['actor_id'=>Auth::id(),'target_id'=>$user->id,'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal memaksa ubah status user.');
        }
    }
}
