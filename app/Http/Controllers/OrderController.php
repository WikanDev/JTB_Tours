<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class OrderController extends Controller
{
    protected function logContext($extra = [])
    {
        $user = Auth::user();
        return array_merge([
            'user_id' => $user ? $user->id : null,
            'user_role' => $user ? $user->role : 'guest',
            'ip' => request()->ip(),
            'method' => request()->method(),
            'url' => request()->fullUrl(),
        ], $extra);
    }

    /**
     * Display a listing of the orders with optional filters.
     */
    public function index(Request $request)
    {
        Log::info('OrderController@index accessed', $this->logContext([
            'filters' => $request->only(['q', 'product_id', 'status', 'from', 'to'])
        ]));

        try {
            $q = Order::with('product', 'createdBy')->orderBy('pickup_time', 'desc');

            if ($request->filled('q')) {
                $search = $request->q;
                $q->where(function ($w) use ($search) {
                    $w->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('pickup_location', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%");
                });
            }

            if ($request->filled('product_id')) {
                $q->where('product_id', $request->product_id);
            }

            if ($request->filled('status')) {
                $q->where('status', $request->status);
            } else {
                $q->where('status', '!=', 'completed');
            }

            if ($request->filled('from')) {
                $q->whereDate('pickup_time', '>=', $request->from);
            }

            if ($request->filled('to')) {
                $q->whereDate('pickup_time', '<=', $request->to);
            }

            $orders = $q->paginate(25)->withQueryString();
            $products = Product::orderBy('name')->get();

            $orders->getCollection()->transform(function ($o) {
                try {
                    $o->formatted_pickup = $o->pickup_time
                        ? \Carbon\Carbon::parse($o->pickup_time)->format('d M Y H:i')
                        : '-';
                } catch (\Throwable $e) {
                    $o->formatted_pickup = '-';
                }

                try {
                    $o->formatted_arrival = $o->arrival_time
                        ? \Carbon\Carbon::parse($o->arrival_time)->format('d M Y H:i')
                        : '-';
                } catch (\Throwable $e) {
                    $o->formatted_arrival = '-';
                }

                $o->summary_people =
                    ($o->adults ?? 0) . ' adults · ' .
                    ($o->children ?? 0) . ' children · ' .
                    ($o->babies ?? 0) . ' babies';

                $o->summary_contact = ($o->email ?? '-') . ' · ' . ($o->phone ?? '-');

                return $o;
            });

            $lastOrderId = Order::max('id') ?? 0;

            Log::info('OrderController@index completed', $this->logContext([
                'order_count' => $orders->count(),
                'page' => $orders->currentPage(),
                'total_pages' => $orders->lastPage()
            ]));

            return view('orders.index', compact('orders', 'products', 'lastOrderId'));
        } catch (\Throwable $e) {
            Log::error('Order.index error: ' . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ] + $this->logContext());
            return redirect()->back()->with('error', 'Gagal mengambil daftar order.');
        }
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        Log::info('OrderController@create accessed', $this->logContext());

        try {
            $products = Product::with('branches')->orderBy('name')->get();
            $dbTypes = \App\Models\Vehicle::select('type')->distinct()->pluck('type')->toArray();
            $defaultTypes = ['Avanza', 'Innova', 'HiAce', 'Bus', 'Alphard', 'APV'];
            $vehicleTypes = array_unique(array_merge($dbTypes, $defaultTypes));
            sort($vehicleTypes);

            Log::info('OrderController@create completed', $this->logContext([
                'product_count' => $products->count(),
                'vehicle_type_count' => count($vehicleTypes)
            ]));

            return view('orders.create', compact('products', 'vehicleTypes'));
        } catch (\Throwable $e) {
            Log::error('Order.create error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ] + $this->logContext());
            return redirect()->back()->with('error', 'Gagal membuka form pembuatan order.');
        }
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        Log::info('OrderController@store accessed', $this->logContext([
            'input_keys' => array_keys($request->all())
        ]));

        $validated = $request->validate([
            'customer_name'              => 'required|string|max:255',
            'email'                      => 'nullable|email',
            'phone'                      => 'nullable|string|max:25',
            'pickup_time'                => 'required|date',
            'arrival_time'               => 'nullable|date|after_or_equal:pickup_time',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'passengers'                 => 'required|integer|min:1',
            'pickup_location'            => 'nullable|string|max:255',
            'destination'                => 'nullable|string|max:255',
            'product_id'                 => 'required|exists:products,id',
            'adults'                     => 'nullable|integer|min:0',
            'children'                   => 'nullable|integer|min:0',
            'babies'                     => 'nullable|integer|min:0',
            'vehicle_count'              => 'nullable|integer|min:1',
            'note'                       => 'nullable|string|max:2000',
            'vehicle_ids'                => 'nullable|array',
            'vehicle_ids.*'              => 'exists:vehicles,id',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::with('branches')->find($validated['product_id']);
            if (!$product) {
                throw new \Exception("Product tidak ditemukan: {$validated['product_id']}");
            }

            $adults   = $validated['adults']   ?? 0;
            $children = $validated['children'] ?? 0;
            $babies   = $validated['babies']   ?? 0;
            $totalPassengers = $adults + $children + $babies;

            // Validate passenger capacity
            if ($product && $product->capacity && $totalPassengers > $product->capacity) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Total penumpang melebihi kapasitas product (' . $product->capacity . ').'
                );
            }

            $vehicleIds = $validated['vehicle_ids'] ?? [];
            if (!empty($vehicleIds)) {
                $validated['vehicle_count'] = count($vehicleIds);
            } else {
                $vehicleCap = $product->capacity ?? 4;
                $validated['vehicle_count'] = ceil($totalPassengers / max(1, $vehicleCap));
            }

            if (empty($validated['estimated_duration_minutes'])) {
                if (!empty($validated['arrival_time']) && !empty($validated['pickup_time'])) {
                    $diffMinutes = max(1, (int) round(
                        (strtotime($validated['arrival_time']) - strtotime($validated['pickup_time'])) / 60
                    ));
                    $validated['estimated_duration_minutes'] = $diffMinutes;
                }
                if (empty($validated['estimated_duration_minutes'])) {
                    $validated['estimated_duration_minutes'] = (int)($product->hour * 60) ?: 60;
                }
            }

            $validated['created_by'] = Auth::check() ? Auth::id() : null;
            $validated['status']     = 'pending';

            $order = Order::create($validated);

            if (!empty($vehicleIds)) {
                $order->vehicles()->attach($vehicleIds);
            }

            DB::commit();

            Log::info('Order created successfully', $this->logContext([
                'order_id' => $order->id,
                'customer_name' => $order->customer_name,
                'product_id' => $order->product_id,
                'vehicle_count' => $order->vehicle_count,
                'status' => $order->status
            ]));

            // ✅ Create Assignments based on vehicle_count
            $vehicleIds = $validated['vehicle_ids'] ?? [];
            $count = max(1, (int)$order->vehicle_count);

            for ($i = 0; $i < $count; $i++) {
                \App\Models\Assignment::create([
                    'order_id'    => $order->id,
                    'vehicle_id'  => $vehicleIds[$i] ?? null, // Assign vehicle if selected
                    'status'      => 'pending',
                    'assigned_by' => Auth::id(),
                    // 'assigned_at' => now(), // Optional: only if directly assigned
                ]);
            }

            Log::info('Assignments created for order', $this->logContext([
                'order_id' => $order->id,
                'count' => $count
            ]));

            // 🔔 Kirim Notifikasi ke Staff jika pembuat adalah Admin
            $user = auth()->user();
            if ($user && in_array($user->role, ['admin', 'super_admin'])) {
                $staffUsers = \App\Models\User::where('role', 'staff')->get();
                if ($staffUsers->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send($staffUsers, new \App\Notifications\NewOrderNotification($order, $user));
                    Log::info('NewOrderNotification sent to staff', $this->logContext([
                        'order_id' => $order->id,
                        'staff_count' => $staffUsers->count()
                    ]));
                }
            }

            return redirect()->route('orders.index')->with('success', 'Order created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.store error: ' . $e->getMessage(), [
                'payload' => Arr::except($validated, ['note']), // hindari log note sensitif
                'trace'   => $e->getTraceAsString()
            ] + $this->logContext());
            return redirect()->back()->withInput()->with('error', 'Gagal membuat order.');
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        Log::info('OrderController@show accessed', $this->logContext([
            'order_id' => $order->id
        ]));

        try {
            $order->load(['product', 'assignments']);

            Log::info('OrderController@show completed', $this->logContext([
                'order_id' => $order->id,
                'status' => $order->status
            ]));

            return view('orders.show', compact('order'));
        } catch (\Throwable $e) {
            Log::error('Order.show error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString()
            ] + $this->logContext());
            return redirect()->back()->with('error', 'Gagal membuka detail order.');
        }
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        Log::info('OrderController@edit accessed', $this->logContext([
            'order_id' => $order->id
        ]));

        try {
            $products = Product::with('branches')->orderBy('name')->get();
            $dbTypes = \App\Models\Vehicle::select('type')->distinct()->pluck('type')->toArray();
            $defaultTypes = ['Avanza', 'Innova', 'HiAce', 'Bus', 'Alphard', 'APV'];
            $vehicleTypes = array_unique(array_merge($dbTypes, $defaultTypes));
            sort($vehicleTypes);

            Log::info('OrderController@edit completed', $this->logContext([
                'order_id' => $order->id
            ]));

            return view('orders.edit', compact('order', 'products', 'vehicleTypes'));
        } catch (\Throwable $e) {
            Log::error('Order.edit error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString()
            ] + $this->logContext());
            return redirect()->back()->with('error', 'Gagal membuka form edit order.');
        }
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        Log::info('OrderController@update accessed', $this->logContext([
            'order_id' => $order->id,
            'input_keys' => array_keys($request->all()),
            'raw_vehicle_ids' => $request->input('vehicle_ids'),
        ]));

        $validated = $request->validate([
            'customer_name'              => 'required|string|max:255',
            'email'                      => 'nullable|email',
            'phone'                      => 'nullable|string|max:25',
            'pickup_time'                => 'required|date',
            'arrival_time'               => 'nullable|date|after_or_equal:pickup_time',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'passengers'                 => 'required|integer|min:1',
            'pickup_location'            => 'nullable|string|max:255',
            'destination'                => 'nullable|string|max:255',
            'product_id'                 => 'required|exists:products,id',
            'adults'                     => 'nullable|integer|min:0',
            'children'                   => 'nullable|integer|min:0',
            'babies'                     => 'nullable|integer|min:0',
            'vehicle_count'              => 'nullable|integer|min:1',
            'note'                       => 'nullable|string|max:2000',
            'vehicle_ids'                => 'nullable|array',
            'vehicle_ids.*'              => 'numeric|exists:vehicles,id', // ✅ numeric allows strings "1"
        ]);

        // ✅ Pastikan vehicle_ids array integer
        $validated['vehicle_ids'] = array_map('intval', $validated['vehicle_ids'] ?? []);

        Log::info('OrderController@update validation passed', $this->logContext([
            'order_id' => $order->id,
            'validated_keys' => array_keys($validated),
            'vehicle_ids' => $validated['vehicle_ids']
        ]));

        DB::beginTransaction();
        try {
            $product = Product::find($validated['product_id']);
            $adults   = $validated['adults'] ?? 0;
            $children = $validated['children'] ?? 0;
            $babies   = $validated['babies'] ?? 0;
            $totalPassengers = $adults + $children + $babies;

            if ($product && $product->capacity && $totalPassengers > $product->capacity) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Total penumpang melebihi kapasitas product (' . $product->capacity . ').'
                );
            }

            $vehicleIds = $validated['vehicle_ids'];
            if (!empty($vehicleIds)) {
                $validated['vehicle_count'] = count($vehicleIds);
            } else {
                if (empty($validated['vehicle_count'])) {
                    $vehicleCap = $product->capacity ?? 4;
                    $validated['vehicle_count'] = ceil($totalPassengers / max(1, $vehicleCap));
                }
            }

            $est = $validated['estimated_duration_minutes'] ?? null;
            if (empty($est) && !empty($validated['arrival_time']) && !empty($validated['pickup_time'])) {
                $diffMinutes = max(1, (int) round(
                    (strtotime($validated['arrival_time']) - strtotime($validated['pickup_time'])) / 60
                ));
                $validated['estimated_duration_minutes'] = $diffMinutes;
            } elseif (empty($est) && empty($order->estimated_duration_minutes)) {
                $validated['estimated_duration_minutes'] = 60;
            }

            // ✅ Ganti array_except → Arr::except
            $order->update(Arr::except($validated, ['vehicle_ids']));

            // ✅ Sync relasi kendaraan
            $order->vehicles()->sync($vehicleIds);

            DB::commit();

            Log::info('Order updated successfully', $this->logContext([
                'order_id' => $order->id,
                'vehicle_ids' => $vehicleIds,
                'vehicle_count' => $order->vehicle_count,
            ]));

            return redirect()->route('orders.index')->with('success', 'Order diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.update error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'validated_keys' => array_keys($validated),
                'vehicle_ids' => $validated['vehicle_ids'] ?? [],
                'trace' => $e->getTraceAsString(),
            ] + $this->logContext());
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui order: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Order $order)
    {
        Log::info('OrderController@destroy accessed', $this->logContext([
            'order_id' => $order->id,
            'current_status' => $order->status
        ]));

        DB::beginTransaction();
        try {
            if (in_array($order->status, ['assigned', 'completed'])) {
                return redirect()->back()->with(
                    'error',
                    'Order yang sudah di-assign atau completed tidak dapat dihapus.'
                );
            }

            $order->delete();

            DB::commit();

            Log::info('Order deleted successfully', $this->logContext([
                'order_id' => $order->id
            ]));

            return redirect()->route('orders.index')->with('success', 'Order dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order.destroy error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString()
            ] + $this->logContext());
            return redirect()->back()->with('error', 'Gagal menghapus order.');
        }
    }

    /**
     * AJAX endpoint untuk staff: cek order baru.
     */
    public function checkLatest(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'staff') {
            Log::warning('Unauthorized access to checkLatest', $this->logContext());
            abort(403);
        }

        $afterId = (int) $request->query('after_id', 0);

        $order = Order::where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->first();

        if (!$order) {
            return response()->json(['has_new' => false]);
        }

        Log::info('New order detected for staff notification', $this->logContext([
            'after_id' => $afterId,
            'new_order_id' => $order->id,
            'status' => $order->status
        ]));

        return response()->json([
            'has_new' => true,
            'order'   => [
                'id'            => $order->id,
                'customer_name' => $order->customer_name,
                'pickup_time'   => $order->pickup_time
                    ? $order->pickup_time->format('d M Y H:i')
                    : null,
                'status'        => $order->status,
            ],
        ]);
    }
}