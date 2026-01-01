<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GuidesDriversController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = User::whereIn('role', ['driver', 'guide']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            // Eager load active assignments to see what they are doing NOW
            // We consider 'in_progress' and 'accepted' as active, though 'in_progress' is the most relevant for "currently doing"
            $users = $query->with(['assignmentsAsDriver' => function($q) {
                                $q->whereIn('status', ['in_progress', 'accepted'])
                                  ->with('order', 'vehicle')
                                  ->orderBy('started_at', 'desc');
                            }, 'assignmentsAsGuide' => function($q) {
                                $q->whereIn('status', ['in_progress', 'accepted'])
                                  ->with('order')
                                  ->orderBy('started_at', 'desc');
                            }])
                           ->orderBy('name')
                           ->paginate(20)
                           ->withQueryString();

            return view('guides-drivers.index', compact('users'));
        } catch (\Throwable $e) {
            Log::error('GuidesDrivers.index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memuat data Guide & Driver.');
        }
    }
}
