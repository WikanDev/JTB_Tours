<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $q = Vehicle::query();

            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function ($w) use ($s) {
                    $w->where('brand', 'like', "%{$s}%")
                      ->orWhere('type', 'like', "%{$s}%")
                      ->orWhere('plate_number', 'like', "%{$s}%");
                });
            }

            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }

            $vehicles = $q->with(['assignments' => function($query) {
                $query->whereIn('status', ['in_progress', 'accepted', 'pending'])
                      ->with(['driver', 'order']);
            }])->orderBy('brand')->paginate(20)->withQueryString();

            return view('vehicles.index', compact('vehicles'));
        } catch (\Throwable $e) {
            Log::error('Vehicle.index error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString(), 'query'=>$request->all()]);
            return redirect()->back()->with('error','Gagal memuat daftar kendaraan.');
        }
    }

    
    public function create()
    {
        try {
            return view('vehicles.create');
        } catch (\Throwable $e) {
            Log::error('Vehicle.create error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form tambah kendaraan.');
        }
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'brand'=>'required|string|max:255',
            'type'=>'required|string|max:100',
            'plate_number'=>'required|string|max:50|unique:vehicles,plate_number',
            'color'=>'nullable|string|max:50',
            'status'=>['required', Rule::in(['available','in_use','maintenance'])],
            'year'=>'nullable|integer|min:1900|max:'.(date('Y')+1),
            'capacity'=>'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            Vehicle::create($data);
            DB::commit();
            return redirect()->route('vehicles.index')->with('success','Kendaraan ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vehicle.store error: '.$e->getMessage(), ['payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal menambahkan kendaraan.');
        }
    }

    
    public function edit(Vehicle $vehicle)
    {
        try {
            return view('vehicles.edit', compact('vehicle'));
        } catch (\Throwable $e) {
            Log::error('Vehicle.edit error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form edit kendaraan.');
        }
    }

    
    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'brand'=>'required|string|max:255',
            'type'=>'required|string|max:100',
            'plate_number'=>['required','string','max:50', Rule::unique('vehicles','plate_number')->ignore($vehicle->id)],
            'color'=>'nullable|string|max:50',
            'status'=>['required', Rule::in(['available','in_use','maintenance'])],
            'year'=>'nullable|integer|min:1900|max:'.(date('Y')+1),
            'capacity'=>'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            $vehicle->update($data);
            DB::commit();
            return redirect()->route('vehicles.index')->with('success','Kendaraan diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vehicle.update error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal memperbarui kendaraan.');
        }
    }

    
    public function destroy(Vehicle $vehicle)
    {
        DB::beginTransaction();
        try {
            
            
            $hasRelations = false;

            
            if (method_exists($vehicle, 'assignments')) {
                try {
                    if ($vehicle->assignments()->exists()) $hasRelations = true;
                } catch (\Throwable $e) {
                    
                }
            }

            
            if (!$hasRelations && method_exists($vehicle, 'orders')) {
                try {
                    if ($vehicle->orders()->exists()) $hasRelations = true;
                } catch (\Throwable $e) {
                    
                }
            }

            if ($hasRelations) {
                return redirect()->back()->with('error','Tidak dapat menghapus kendaraan yang masih terkait assignment atau order.');
            }

            $vehicle->delete();
            DB::commit();
            return redirect()->route('vehicles.index')->with('success','Kendaraan dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vehicle.destroy error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal menghapus kendaraan.');
        }
    }
    
    public function show(Vehicle $vehicle)
    {
        try {
            
            
            $history = \App\Models\Assignment::with(['driver', 'order'])
                        ->where('vehicle_id', $vehicle->id)
                        ->orderBy('assigned_at', 'desc')
                        ->paginate(20);

            
            $activeAssignment = \App\Models\Assignment::with(['driver', 'order'])
                                ->where('vehicle_id', $vehicle->id)
                                ->whereIn('status', ['accepted', 'pending', 'in_progress']) 
                                ->orderBy('assigned_at', 'asc')
                                ->first();

            return view('vehicles.show', compact('vehicle', 'history', 'activeAssignment'));
        } catch (\Throwable $e) {
            Log::error('Vehicle.show error: '.$e->getMessage(), ['vehicle_id'=>$vehicle->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal memuat detail kendaraan.');
        }
    }
    
    public function checkAvailabilityTypes(Request $request)
    {
        $pickup = $request->query('pickup_time');
        $duration = (int) $request->query('duration', 60);

        if (!$pickup) {
             return response()->json([]);
        }

        try {
            $startTime = \Carbon\Carbon::parse($pickup);
            $endTime = $startTime->copy()->addMinutes($duration);

            
            
            

            $availableVehicles = $this->getAvailableVehicles($startTime, $endTime);
            $types = $availableVehicles->pluck('type')->unique()->values()->all();
            sort($types);

            return response()->json($types);

        } catch (\Throwable $e) {
            Log::error('Vehicle.checkAvailabilityTypes error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    
    public function checkAvailabilityList(Request $request)
    {
        $startStr = $request->query('start');
        $endStr = $request->query('end');
        $ignoreOrderId = $request->query('ignore_order_id');

        if (!$startStr || !$endStr) {
             return response()->json([]);
        }

        try {
            $startTime = \Carbon\Carbon::parse($startStr);
            $endTime = \Carbon\Carbon::parse($endStr);

            $availableVehicles = $this->getAvailableVehicles($startTime, $endTime, $ignoreOrderId);

            return response()->json($availableVehicles->values()); 

        } catch (\Throwable $e) {
            Log::error('Vehicle.checkAvailabilityList error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    private function getAvailableVehicles($startTime, $endTime, $ignoreOrderId = null)
    {
        
        $vehicles = Vehicle::where('status', '!=', 'maintenance')->get();
        
        $available = $vehicles->filter(function ($v) use ($startTime, $endTime, $ignoreOrderId) {
            
            
            $assignmentsQ = $v->assignments()
                ->whereIn('status', ['pending', 'accepted', 'in_progress']);
            
            if ($ignoreOrderId) {
                $assignmentsQ->where('order_id', '!=', $ignoreOrderId);
            }

            $hasAssignmentOverlap = $assignmentsQ
                ->whereHas('order', function ($q) use ($startTime, $endTime) {
                    $q->where(function ($sub) use ($startTime, $endTime) {
                        $sub->where('pickup_time', '<', $endTime)
                            ->whereRaw("DATE_ADD(pickup_time, INTERVAL COALESCE(estimated_duration_minutes, 60) MINUTE) > ?", [$startTime]);
                    });
                })
                ->exists();

            if ($hasAssignmentOverlap) return false;

            
            $ordersQ = $v->orders()
                ->whereIn('status', ['pending', 'assigned']);
            
            if ($ignoreOrderId) {
                
                $ordersQ->where('orders.id', '!=', $ignoreOrderId);
            }

            $hasOrderOverlap = $ordersQ
                ->where(function ($q) use ($startTime, $endTime) {
                     $q->where('pickup_time', '<', $endTime)
                       ->whereRaw("DATE_ADD(pickup_time, INTERVAL COALESCE(estimated_duration_minutes, 60) MINUTE) > ?", [$startTime]);
                })
                ->exists();
            
            if ($hasOrderOverlap) return false;

            return true;
        });

        return $available;
    }
}
