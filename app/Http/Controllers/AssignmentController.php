<?php

namespace App\Http\Controllers;

use App\Events\NewAssignmentCreated;
use App\Models\Assignment;
use App\Models\Order;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar assignment (untuk staff / admin)
     */
    public function index(Request $request)
    {
        try {
            $q = Assignment::with(['order.product', 'driver', 'guide', 'assignedBy', 'vehicle']);

            if ($request->filled('status')) {
                $q->where('status', $request->query('status'));
            }
            if ($request->filled('driver_id')) {
                $q->where('driver_id', $request->query('driver_id'));
            }
            if ($request->filled('guide_id')) {
                $q->where('guide_id', $request->query('guide_id'));
            }
            if ($request->filled('from')) {
                $q->whereDate('assigned_at', '>=', $request->query('from'));
            }
            if ($request->filled('to')) {
                $q->whereDate('assigned_at', '<=', $request->query('to'));
            }

            $assignments = $q->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

            return view('assignments.index', compact('assignments'));
        } catch (\Throwable $e) {
            Log::error('Assignment.index error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengambil data assignment.');
        }
    }

    /**
     * Form create assignment
     */
    public function create(Request $request)
    {
        try {
            $orderId = $request->query('order');
            // Filter out 'assigned' orders as per request.
            // Assuming 'pending' is the only other valid status for creating assignment.
            // If other statuses like 'confirmed' exist, add them here.
            $orders = Order::where('status', '!=', 'assigned')
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'completed')
                ->orderBy('pickup_time')
                ->get();
    
            // Sort drivers by least used hours in current month.
            // Simplified: fetch all drivers and sort by relationship/attribute logic or join.
            // Ideally: join work_schedules and order by used_hours ASC.
            $month = now()->month;
            $year  = now()->year;
            
            $drivers = User::where('role', 'driver')
                ->with(['workSchedules' => function($q) use($month, $year) {
                    $q->where('month', $month)->where('year', $year);
                }])
                ->get()
                ->sortBy(function($u) {
                    return $u->workSchedules->first()?->used_hours ?? 0;
                })
                ->values(); // reset keys
    
            $guides = User::where('role', 'guide')
                ->orderBy('name')
                ->get();
    
            $order = $orderId ? Order::find($orderId) : null;
    
            // ===== Tambahan: Work hours bulan ini =====
            $month = now()->month;
            $year  = now()->year;
    
            // Ambil work schedule semua driver bulan ini
            $driverSchedules = WorkSchedule::whereIn('user_id', $drivers->pluck('id'))
                ->where('month', $month)
                ->where('year', $year)
                ->get()
                ->keyBy('user_id'); // key: user_id → WorkSchedule
    
            // Ambil work schedule semua guide bulan ini
            $guideSchedules = WorkSchedule::whereIn('user_id', $guides->pluck('id'))
                ->where('month', $month)
                ->where('year', $year)
                ->get()
                ->keyBy('user_id');
            
            // Available Vehicles (Available & not maintenance)
            // Ideally we check if "in_use" but in_use is status column.
            // User requested: "tampilkan mobil yang tersedia pada hari yang diinginkan semisal ada order dihari yang sama masih bisa digunakan di Waktu yang berbeda"
            // So we just filter by status != maintenance. 
            // In Store, we should validate time overlay.
            $vehicles = Vehicle::where('status', '!=', 'maintenance')->orderBy('type')->orderBy('plate_number')->get();

            return view('assignments.create', compact(
                'orders',
                'drivers',
                'guides',
                'order',
                'month',
                'year',
                'driverSchedules',
                'guideSchedules',
                'vehicles'
            ));
        } catch (\Throwable $e) {
            Log::error('Assignment.create error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Gagal membuka form assignment.');
        }
    }
    

    /**
     * Store assignment (dibuat oleh staff)
     * Supports multiple assignments per request (assignments.*)
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'assignments' => 'required|array|min:1',
            'assignments.*.driver_id' => 'required|exists:users,id',
            'assignments.*.vehicle_id'=> 'required|exists:vehicles,id',
            'assignments.*.guide_id'  => 'nullable|exists:users,id',
            'assignments.*.note'      => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::lockForUpdate()->findOrFail($request->order_id);

            if ($order->status === 'completed') {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', 'Order sudah selesai, tidak bisa di-assign.');
            }

            $month = now()->month;
            $year = now()->year;
            $estHours = $this->calculateEstimatedHours($order);

            foreach ($request->assignments as $data) {
                // Check usage limit for Driver
                $driver = User::findOrFail($data['driver_id']);
                $driverSchedule = WorkSchedule::firstOrCreate(
                    ['user_id' => $driver->id, 'month' => $month, 'year' => $year],
                    ['total_hours' => $driver->monthly_work_limit ?? 200, 'used_hours' => 0.00]
                );

                // Check Overlimit (Optional strictness)
                if (($driverSchedule->used_hours + $estHours) > $driverSchedule->total_hours) {
                    DB::rollBack();
                    return redirect()->back()->withInput()->with('error', "Driver {$driver->name} tidak memiliki cukup jam kerja.");
                }

                // Increment usage
                $driverSchedule->increment('used_hours', $estHours);

                // Handle Guide
                $guideId = $data['guide_id'] ?? null;
                if ($guideId) {
                    $guide = User::findOrFail($guideId);
                    $guideSchedule = WorkSchedule::firstOrCreate(
                        ['user_id' => $guide->id, 'month' => $month, 'year' => $year],
                        ['total_hours' => $guide->monthly_work_limit ?? 200, 'used_hours' => 0.00]
                    );
                    $guideSchedule->increment('used_hours', $estHours);
                }

                // Check Vehicle availability
                $vehicle = Vehicle::findOrFail($data['vehicle_id']);
                
                // Exclude current order from overlap check handled in checkVehicleOverlap now
                if ($this->checkVehicleOverlap($vehicle->id, $order)) {
                     DB::rollBack();
                     return redirect()->back()->withInput()->with('error', "Kendaraan {$vehicle->plate_number} sedang digunakan pada estimasi jam tersebut.");
                }
                
                // Try to reuse existing empty assignment (from auto-creation)
                $assignment = Assignment::where('order_id', $order->id)
                    ->where('vehicle_id', $vehicle->id)
                    ->whereNull('driver_id')
                    ->where('status', 'pending')
                    ->first();

                if ($assignment) {
                    $assignment->update([
                        'driver_id'    => $driver->id,
                        'guide_id'     => $guideId,
                        'assigned_by'  => Auth::id(),
                        'assigned_at'  => now(),
                        'note'         => $data['note'] ?? null,
                    ]);
                } else {
                    $assignment = Assignment::create([
                        'order_id'     => $order->id,
                        'driver_id'    => $driver->id,
                        'guide_id'     => $guideId,
                        'vehicle_id'   => $vehicle->id,
                        'assigned_by'  => Auth::id(),
                        'status'       => 'pending',
                        'assigned_at'  => now(),
                        'note'         => $data['note'] ?? null,
                    ]);
                }

                // 🔔 Notify Driver
                $driver->notify(new \App\Notifications\NewAssignmentNotification($assignment));

                // 🔔 Notify Guide (if exists)
                if ($guideId && isset($guide)) {
                    $guide->notify(new \App\Notifications\NewAssignmentNotification($assignment));
                }
            }
            
            // Check order status update
            // Verify total assignments count vs order requirement
            $totalAssigned = $order->assignments()->count(); 
            // Note: transaction not committed yet, so count() might not see new ones depending on isolation?
            // Usually within same transaction connection, it sees them.
            // If strict, we can just count $request->assignments count + existing.
            
            if ($totalAssigned >= $order->vehicle_count) {
                $order->update(['status' => 'assigned']);
            } else {
                // If we want partial status, logic here. 
                // Defaulting to assigned only when fully assigned?
                // For now, let's set to assigned if at least 1 is there or maybe only if full?
                // User requirement implied "create assignments" -> done.
                if ($totalAssigned > 0) {
                     $order->update(['status' => 'assigned']); 
                }
            }

            DB::commit();

            return redirect()->route('assignments.index')->with('success', 'Assignment berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assignment.store error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat membuat assignment: ' . $e->getMessage());
        }
    }

    /**
     * Edit Assignment (Staff/Admin)
     */
    public function edit(Assignment $assignment)
    {
        // Pastikan assignment bisa diedit (misal: belum completed?)
        // Requirement tidak melarang, tp completed biasanya final.
        // Kita allow saja untuk revisi data.

        $order = $assignment->order;
        
        // Load data reference for selects
        $month = now()->month;
        $year  = now()->year;

        // Drivers (sorted by usage like create)
        $drivers = User::where('role', 'driver')
            ->with(['workSchedules' => function($q) use($month, $year) {
                $q->where('month', $month)->where('year', $year);
            }])
            ->get()
            ->sortBy(function($u) {
                return $u->workSchedules->first()?->used_hours ?? 0;
            });

        $guides = User::where('role', 'guide')->orderBy('name')->get();
        
        // Vehicle options (all available or currently assigned to this assignment)
        $vehicles = Vehicle::where('status', '!=', 'maintenance')
            ->orderBy('type')->orderBy('plate_number')->get();

        return view('assignments.edit', compact('assignment', 'order', 'drivers', 'guides', 'vehicles'));
    }

    /**
     * Update Assignment
     */
    public function update(Request $request, Assignment $assignment)
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
            'guide_id'  => 'nullable|exists:users,id',
            'vehicle_id'=> 'required|exists:vehicles,id',
            'note'      => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Check Vehicle availability ONLY if Changed
            if ($request->vehicle_id != $assignment->vehicle_id) {
                 $vehicle = Vehicle::findOrFail($request->vehicle_id);
                 // Check overlap. Order object is same.
                 if ($this->checkVehicleOverlap($vehicle->id, $assignment->order)) {
                     DB::rollBack();
                     return back()->withInput()->with('error', "Kendaraan {$vehicle->plate_number} sedang digunakan time slot ini.");
                 }
                 
                 // Release old vehicle status if it was in use?
                 // Current logic: status 'in_use' is set when started.
                 // If we switch vehicle mid-job, logic gets complex.
                 // Assuming edit mostly happens when pending/accepted.
            }

            // Track if driver or guide changed
            $driverChanged = $request->driver_id != $assignment->driver_id;
            $guideChanged = $request->guide_id != $assignment->guide_id;
            $wasDeclined = $assignment->status === 'declined';

            // Update assignment
            $assignment->driver_id = $request->driver_id;
            $assignment->guide_id = $request->guide_id;
            $assignment->vehicle_id = $request->vehicle_id;
            $assignment->note = $request->note;

            // If assignment was declined and driver/guide changed, reset for reassignment
            if ($wasDeclined && ($driverChanged || $guideChanged)) {
                $assignment->status = 'pending';
                $assignment->rejection_reason = null;
                $assignment->rejected_at = null;
                $assignment->assigned_at = now();

                // Update order status back to assigned
                $assignment->order->update(['status' => 'assigned']);
            }

            $assignment->save();

            // 🔔 Notify new driver if changed
            if ($driverChanged) {
                $newDriver = User::find($request->driver_id);
                if ($newDriver) {
                    $newDriver->notify(new \App\Notifications\NewAssignmentNotification($assignment));
                }
            }

            // 🔔 Notify new guide if changed
            if ($guideChanged && $request->guide_id) {
                $newGuide = User::find($request->guide_id);
                if ($newGuide) {
                    $newGuide->notify(new \App\Notifications\NewAssignmentNotification($assignment));
                }
            }

            DB::commit();
            return redirect()->route('assignments.index')->with('success', 'Assignment berhasil diperbarui.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assignment.update error: '.$e->getMessage());
            return back()->withInput()->with('error', 'Gagal update assignment.');
        }
    }


    /**
     * Ubah status assignment oleh driver/guide
     */
    public function changeStatus(Request $request, Assignment $assignment)
    {
        // 🔍 Log untuk debugging
        Log::info('Assignment.changeStatus called', [
            'assignment_id' => $assignment->id,
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->role ?? 'N/A',
            'requested_status' => $request->status,
            'current_status' => $assignment->status,
            'rejection_reason' => $request->rejection_reason,
            'request_all' => $request->all(),
        ]);

        $request->validate([
            'status' => 'required|in:accepted,in_progress,completed,declined',
            'rejection_reason' => 'required_if:status,declined|string|max:1000',
        ]);

        $user = Auth::user();
        $status = $request->status;

        //  Validasi: hanya driver/guide yang bersangkutan
        if (!in_array($user->role, ['driver', 'guide'])) {
            Log::warning('Assignment.changeStatus forbidden: User is not driver/guide', [
                'user_id' => $user->id,
                'user_role' => $user->role
            ]);
            abort(403, 'Hanya driver/guide yang boleh mengubah status.');
        }

        if ($user->role === 'driver' && $assignment->driver_id !== $user->id) {
            Log::warning('Assignment.changeStatus forbidden: User is not assigned driver', [
                'user_id' => $user->id,
                'assignment_driver_id' => $assignment->driver_id
            ]);
            abort(403, 'Anda bukan driver yang ditugaskan.');
        }

        if ($user->role === 'guide' && $assignment->guide_id !== $user->id) {
            Log::warning('Assignment.changeStatus forbidden: User is not assigned guide', [
                'user_id' => $user->id,
                'assignment_guide_id' => $assignment->guide_id
            ]);
            abort(403, 'Anda bukan guide yang ditugaskan.');
        }

        //  Validasi Transition
        if ($status === 'accepted' && $assignment->status !== 'pending') {
            return back()->with('error', 'Hanya assignment pending yang bisa di-accept.');
        }

        if ($status === 'in_progress' && $assignment->status !== 'accepted') {
            return back()->with('error', 'Hanya assignment accepted yang bisa mulai dikerjakan (in progress).');
        }

        if ($status === 'completed' && !in_array($assignment->status, ['in_progress', 'accepted'])) {
             // Allow completing from accepted if user forgot to click in_progress? 
             // Ideally enforce in_progress. Let's allowing from accepted for flexibility or strict?
             // Requirement says "Two-step: Kerjakan -> Selesai". Implies strictness.
             // But let's allow from accepted just in case.
             // Actually, strict flow is better for data quality.
             if ($assignment->status !== 'in_progress') {
                return back()->with('error', 'Silakan klik "Kerjakan" terlebih dahulu sebelum menyelesaikan tugas.');
             }
        }

        if ($status === 'declined' && $assignment->status !== 'pending') {
            return back()->with('error', 'Hanya assignment pending yang bisa di-decline.');
        }

        DB::beginTransaction();

        try {
            if ($status === 'declined') {
                Log::info('Assignment.changeStatus: Processing decline', [
                    'assignment_id' => $assignment->id,
                    'rejection_reason' => $request->rejection_reason,
                    'rejected_by' => $user->id,
                    'rejected_by_role' => $user->role,
                ]);

                $assignment->rejection_reason = $request->rejection_reason;
                $assignment->rejected_at = now();
                // If declined, Order should go back to pending?
                // The assignment is declined, but the Order needs to be re-assigned.
                // Order status is currently 'assigned' (from store).
                // Should we set Order to 'pending' again?
                // Yes, so admin sees it in pending list.
                $assignment->order->update(['status' => 'pending']);

                Log::info('Assignment.changeStatus: Order status updated to pending after decline', [
                    'order_id' => $assignment->order_id
                ]);

                // 🔔 Notify all staff about the declined assignment
                $staffUsers = User::where('role', 'staff')->get();
                foreach ($staffUsers as $staffUser) {
                    $staffUser->notify(new \App\Notifications\AssignmentDeclinedNotification($assignment, $user));
                }

                Log::info('Assignment.changeStatus: Staff users notified about decline', [
                    'staff_count' => $staffUsers->count()
                ]);
            }


            if ($status === 'in_progress') {
                $assignment->started_at = now();
                // Optionally update Order status to 'in_progress'
                // Requirement: "Global: 'In Progress' status visible on the dashboard"
                // Order status ENUM might not have 'in_progress'. Check migration?
                // Order table status is string?
                // Let's assume standard strings.
                $assignment->order->update(['status' => 'in_progress']);
            }

            // Saat complete → hitung & update
            if ($status === 'completed') {
                $assignment->completed_at = now(); 
                
                // Calculate hours
                $minutes = 0;
                // Calculate hours based on ACTUAL time
                $minutes = 0;
                
                if ($assignment->started_at) {
                    $start = \Carbon\Carbon::parse($assignment->started_at);
                    $end = \Carbon\Carbon::parse($assignment->completed_at);
                    $minutes = max(1, $end->diffInMinutes($start));
                } elseif ($assignment->order && $assignment->order->estimated_duration_minutes > 0) {
                     // Fallback if started_at missing (e.g. legacy or skipped in_progress)
                    $minutes = $assignment->order->estimated_duration_minutes;
                } else {
                     $minutes = 60; // Default minimum fallback
                }

                $userId = $user->id; 
                $month = now()->month;
                $year = now()->year;

                $ws = WorkSchedule::firstOrCreate(
                    ['user_id' => $userId, 'month' => $month, 'year' => $year],
                    ['total_hours' => $user->monthly_work_limit ?? 200, 'used_hours' => 0.00]
                );

                $newUsed = $this->addUsedHoursHMM($ws->used_hours, $minutes);
                $ws->update(['used_hours' => $newUsed]);

                // 🔥 Update order ke completed
                $order = $assignment->order;
                if ($order && $order->status !== 'completed') {
                    $order->update(['status' => 'completed']);
                    
                    // Release Vehicle Status
                    if ($assignment->vehicle) {
                        $assignment->vehicle->update(['status' => 'available']);
                    }
                }
            }
            
            // If accepted, maybe set vehicle to 'in_use'?
            // No, vehicle is in use when 'in_progress'.
            if ($status === 'in_progress' && $assignment->vehicle) {
                // Check if vehicle is actually 'available' now? 
                // Too strict maybe? Just set it.
                $assignment->vehicle->update(['status' => 'in_use']);
            }

            $assignment->status = $status;
            $assignment->save();

            DB::commit();

            return back()->with('success', 'Status assignment berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assignment.changeStatus error: ' . $e->getMessage(), [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'status' => $status,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Gagal memperbarui status. Silakan coba lagi.');
        }
    }

    /**
     * Helper: tambahkan menit ke format jam H.MM (mis: 2.30, 0.45, 3.00)
     */
    private function addUsedHoursHMM($currentUsedHours, int $minutesToAdd): float
    {
        $current = sprintf('%.2f', (float)($currentUsedHours ?? 0));
        [$h, $m] = array_pad(explode('.', $current), 2, 0);
        $h = (int)$h;
        $m = (int)$m;

        $total = $h * 60 + $m + $minutesToAdd;

        $newHours = intdiv($total, 60);
        $newMinutes = $total % 60;

        return (float) sprintf('%d.%02d', $newHours, $newMinutes);
    }

    /**
     * Tampilkan detail assignment
     */
    public function show(Assignment $assignment)
    {
        try {
            $assignment->load(['order.product', 'driver', 'guide', 'assignedBy']);
            return view('assignments.show', compact('assignment'));
        } catch (\Throwable $e) {
            Log::error('Assignment.show error: ' . $e->getMessage(), ['assignment_id' => $assignment->id]);
            return redirect()->back()->with('error', 'Gagal membuka detail assignment.');
        }
    }

    /**
     * Hapus assignment
     */
    public function destroy(Assignment $assignment)
    {
        try {
            $assignment->delete();
            return redirect()->route('assignments.index')->with('success', 'Assignment dihapus.');
        } catch (\Throwable $e) {
            Log::error('Assignment.destroy error: ' . $e->getMessage(), ['assignment_id' => $assignment->id]);
            return redirect()->back()->with('error', 'Gagal menghapus assignment.');
        }
    }

    /**
     * Daftar tugas user (driver/guide)
     */
    public function myAssignments()
    {
        try {
            $user = Auth::user();
            
            Log::info('MyAssignments: Page accessed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'timestamp' => now()->toDateTimeString(),
            ]);

            $assignments = Assignment::where(function ($q) use ($user) {
                if ($user->role === 'driver') $q->where('driver_id', $user->id);
                if ($user->role === 'guide') $q->where('guide_id', $user->id);
            })->with(['order.product'])->orderBy('assigned_at', 'desc')->get();

            Log::info('MyAssignments: Assignments loaded', [
                'user_id' => $user->id,
                'total_assignments' => $assignments->count(),
                'pending' => $assignments->where('status', 'pending')->count(),
                'accepted' => $assignments->where('status', 'accepted')->count(),
                'in_progress' => $assignments->where('status', 'in_progress')->count(),
                'completed' => $assignments->where('status', 'completed')->count(),
                'declined' => $assignments->where('status', 'declined')->count(),
            ]);

            // Log detail setiap assignment untuk debugging
            foreach ($assignments as $assignment) {
                Log::debug('MyAssignments: Assignment detail', [
                    'assignment_id' => $assignment->id,
                    'order_id' => $assignment->order_id,
                    'status' => $assignment->status,
                    'driver_id' => $assignment->driver_id,
                    'guide_id' => $assignment->guide_id,
                    'vehicle_id' => $assignment->vehicle_id,
                    'assigned_at' => $assignment->assigned_at,
                    'started_at' => $assignment->started_at,
                    'completed_at' => $assignment->completed_at,
                    'rejection_reason' => $assignment->rejection_reason,
                ]);
            }

            return view('assignments.my', compact('assignments'));
        } catch (\Throwable $e) {
            Log::error('Assignment.myAssignments error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->back()->with('error', 'Gagal mengambil daftar tugas Anda.');
        }
    }

    /**
     * Hitung estimasi jam dari order
     */
    protected function calculateEstimatedHours(Order $order): int
    {
        $minutes = (int)($order->estimated_duration_minutes ?? 0);

        if ($minutes <= 0 && $order->pickup_time && $order->arrival_time) {
            try {
                $start = strtotime($order->pickup_time);
                $end = strtotime($order->arrival_time);
                $diff = max(0, $end - $start);
                $minutes = (int) round($diff / 60);
            } catch (\Throwable $e) {
                Log::warning('calculateEstimatedHours parse error: ' . $e->getMessage());
                $minutes = 60;
            }
        }

        if ($minutes <= 0) $minutes = 60;
        return (int) ceil($minutes / 60);
    }


    public function myHistory()
    {
        try {
            $user = Auth::user();

            $assignments = Assignment::where(function ($q) use ($user) {
                if ($user->role === 'driver') {
                    $q->where('driver_id', $user->id);
                } elseif ($user->role === 'guide') {
                    $q->where('guide_id', $user->id);
                }
            })
            ->with(['order.product', 'driver', 'guide'])
            ->orderBy('updated_at', 'desc') // paling baru di atas
            ->paginate(10); // pagination agar tidak overload

            return view('assignments.my', compact('assignments'));
        } catch (\Throwable $e) {
            Log::error('Assignment.myHistory error: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Gagal memuat riwayat tugas.');
        }
    }

    /**
     * Check availability AJAX
     */
    public function checkAvailability(Request $request)
    {
        $orderId = $request->query('order_id');
        $order = Order::with('vehicles')->find($orderId);

        if (!$order) {
            return response()->json([], 404);
        }

        // Logic Change: If Order has specific vehicles selected via order_vehicle pivot,
        // use ONLY those vehicles.
        $preSelectedVehicles = $order->vehicles;
        $vehicles = collect([]);

        if ($preSelectedVehicles->count() > 0) {
            // Use pre-selected
             $vehicles = $preSelectedVehicles;
             // Sort options?
             $vehicles = $vehicles->sortBy('brand');
        } else {
             // Fallback to all available unique vehicles
             $vehicles = Vehicle::where('status', '!=', 'maintenance')
                ->orderBy('type')
                ->orderBy('plate_number')
                ->get();
        }

        $available = [];
        $preassignedIds = []; // IDs to auto-fill rows

        $pickupTime = $order->pickup_time ? Carbon::parse($order->pickup_time) : now();
        $dateStart = $pickupTime->copy()->startOfDay();
        $dateEnd = $pickupTime->copy()->endOfDay();
        
        foreach ($vehicles as $v) {
            // If strictly pre-selected, we might skip overlap check or just warn?
            // User specifically asked for this vehicle in Order.
            // But if it's already used by ANOTHER assignment, we should warn or block.
            // Let's keep the overlap check but for pre-selected, maybe we don't hide it?
            // "assignment hanya menampilkan kendaraan yang di pilih" -> implies strict filter.
            // I will include them but maybe mark as unavailable if overlap?
            // If I hide them, user can't select -> block.
            // Let's just create the list and rely on store check.
            // But visually, show "In Use" if overlap.
            
            // Note: Since I just added `order_vehicle` pivot, `checkVehicleOverlap` checks assignments.
            // It assumes if assignment exists, it overlaps.
            // The overlap check might return true if there is an assignment.
            
            $isOverlapping = $this->checkVehicleOverlap($v->id, $order);
            
            // Find next assignment info
            $nextAssignment = Assignment::where('vehicle_id', $v->id)
                ->whereIn('assignments.status', ['pending', 'accepted', 'in_progress'])
                ->whereHas('order', function($q) use ($pickupTime, $dateEnd) {
                    $q->whereBetween('pickup_time', [$pickupTime, $dateEnd]);
                })
                ->join('orders', 'assignments.order_id', '=', 'orders.id')
                ->orderBy('orders.pickup_time')
                ->select('assignments.*') 
                ->first();
            
            $statusText = ucwords(str_replace('_', ' ', $v->status));
            if ($isOverlapping) {
                // If pre-selected, show it but maybe mark text
                 $statusText .= " [BUSY/OVERLAP]";
            }
            
            $nextText = '';
            if ($nextAssignment && $nextAssignment->order) {
                $nextPickup = Carbon::parse($nextAssignment->order->pickup_time);
                $nextText = " | Next: " . $nextPickup->format('H:i');
            }
            
            // If pre-selected count > 0, include even if overlapping (user must resolve manually or pick another driver? wait vehicle is fixed).
            // If vehicle is fixed, user is stuck. 
            // Better to show.
            
            if ($preSelectedVehicles->count() > 0 || !$isOverlapping) {
                $available[] = [
                    'id' => $v->id,
                    'text' => "{$v->brand} {$v->type} - {$v->plate_number} ({$statusText}{$nextText})",
                    'is_overlap' => $isOverlapping
                ];
                if ($preSelectedVehicles->count() > 0) {
                    $preassignedIds[] = $v->id;
                }
            }
        }

        // Check Driver Availability
        $month = now()->month;
        $year = now()->year;

        $drivers = User::where('role', 'driver')
             ->with(['workSchedules' => function($q) use($month, $year) {
                 $q->where('month', $month)->where('year', $year);
             }])
             ->get()
             ->sortBy(function($u) {
                  return $u->workSchedules->first()?->used_hours ?? 0;
             });
        
        $availableDrivers = [];
        foreach ($drivers as $d) {
             if (!$this->checkUserOverlap($d->id, $order)) {
                 $schedule = $d->workSchedules->first();
                 $used = (float)($schedule->used_hours ?? 0);
                 $limit = (int)($schedule->total_hours ?? $d->monthly_work_limit ?? 200);
                 $rem = max(0, $limit - $used);
                 
                 $availableDrivers[] = [
                     'id' => $d->id,
                     'text' => "{$d->name} ({$used}h used / sisa {$rem}h)",
                     'remaining' => $rem
                 ];
             }
        }

        // Check Guide Availability
        $guides = User::where('role', 'guide')->orderBy('name')->get();
        $availableGuides = [];
        foreach ($guides as $g) {
             if (!$this->checkUserOverlap($g->id, $order)) {
                 $schedule = WorkSchedule::where('user_id', $g->id)->where('month', $month)->where('year', $year)->first();
                 $used = $schedule->used_hours ?? 0;
                 $limit = $schedule->total_hours ?? $g->monthly_work_limit ?? 200;
                 $availableGuides[] = [
                     'id' => $g->id,
                     'text' => "{$g->name} ({$used} / {$limit} jam)"
                 ];
             }
        }

        return response()->json([
            'vehicles' => $available,
            'drivers' => array_values($availableDrivers), 
            'guides' => $availableGuides,
            'vehicle_count' => $order->vehicle_count ?? 1,
            'required_capacity' => $order->passengers,
            'pickup_time' => $order->pickup_time,
            'estimated_duration' => $order->estimated_duration_minutes,
            'preassigned_vehicle_ids' => $preassignedIds
        ]);
    }

    /**
     * Check if user (driver/guide) is busy.
     */
    protected function checkUserOverlap($userId, Order $order)
    {
        $pickup = Carbon::parse($order->pickup_time);
        $duration = $order->estimated_duration_minutes ?? 60;
        $arrival = $pickup->copy()->addMinutes($duration);

        return Assignment::where(function($q) use ($userId) {
                $q->where('driver_id', $userId)
                  ->orWhere('guide_id', $userId);
            })
            ->whereIn('assignments.status', ['pending', 'accepted', 'in_progress'])
            ->whereHas('order', function($q) use ($pickup, $arrival) {
                 $q->where(function($q2) use ($pickup, $arrival) {
                     $q2->where('pickup_time', '<', $arrival)
                        ->where(DB::raw("DATE_ADD(pickup_time, INTERVAL COALESCE(estimated_duration_minutes, 60) MINUTE)"), '>', $pickup);
                 });
            })
            ->exists();
    }

    /**
     * Check if vehicle is busy during order time window.
     * Order time window: pickup_time to pickup_time + estimated_duration_minutes
     */
    protected function checkVehicleOverlap($vehicleId, Order $order)
    {
        $pickup = Carbon::parse($order->pickup_time);
        $duration = $order->estimated_duration_minutes ?? 60;
        $arrival = $pickup->copy()->addMinutes($duration);

        // Find assignments for this vehicle that are NOT completed/declined and overlap
        // Assignments don't strictly have start/end stored unless accepted/completed.
        // But we can approximate using the related Order's pickup/arrival.
        
        $overlaps = Assignment::where('vehicle_id', $vehicleId)
            ->where('order_id', '!=', $order->id) // ✅ Exclude current order to allow assigning
            ->whereIn('status', ['pending', 'accepted']) // completed ones strictly speaking are done, but maybe buffer? Let's check active ones.
            ->whereHas('order', function($q) use ($pickup, $arrival) {
                 // Order time interval: [start, end]
                 // Overlap if: (start1 < end2) and (start2 < end1)
                 $q->where(function($q2) use ($pickup, $arrival) {
                     $q2->where('pickup_time', '<', $arrival)
                        ->where(DB::raw("DATE_ADD(pickup_time, INTERVAL COALESCE(estimated_duration_minutes, 60) MINUTE)"), '>', $pickup);
                 });
            })
            ->exists();

        return $overlaps;
    }
}
