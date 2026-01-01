@extends('layouts.app')
@section('title', 'Dashboard ')

@section('content')
@php
    $ordersThisMonth     = $ordersThisMonth     ?? 0;
    $assignedThisMonth   = $assignedThisMonth   ?? 0;
    $completedThisMonth  = $completedThisMonth  ?? 0;
    $activeDrivers       = $activeDrivers       ?? 0;
    $monthlyOrders       = $monthlyOrders       ?? [];
    $productDistribution = $productDistribution ?? [];
    $topDrivers          = $topDrivers          ?? [];
    $month               = $month               ?? now()->month;
    $year                = $year                ?? now()->year;
    $availableYears      = $availableYears      ?? [now()->year];
    $lastOrderId         = $lastOrderId         ?? 0;  // 🔔 untuk notifikasi staff
@endphp

<div class="bg-gray-50 min-h-screen">
  <div class="bg-white border-b shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Dashboard Admin</h1>
        <p class="text-sm text-gray-500">Ringkasan aktivitas sistem</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-sm text-gray-600">
          <span class="font-medium">{{ auth()->user()->name }}</span>
          <span class="text-gray-400">•</span>
          <span>{{ ucfirst(auth()->user()->role) }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">

    
    <div class="bg-white rounded-lg shadow p-4">
      <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Tahun</label>
          <select name="year" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @foreach($availableYears as $y)
              <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Bulan</label>
          <select name="month" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @for($m = 1; $m <= 12; $m++)
              <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
              </option>
            @endfor
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Terapkan
          </button>
        </div>
      </form>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Orders Bulan Ini</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $ordersThisMonth }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-yellow-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Assigned</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $assignedThisMonth }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Completed</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $completedThisMonth }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
          <div class="p-3 bg-indigo-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-500">Driver/Guide Aktif</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $activeDrivers }}</p>
          </div>
        </div>
      </div>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <div class="lg:col-span-2 bg-white rounded-lg shadow p-5">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Orders & Completed — 12 Bulan Terakhir</h3>
        </div>
        <div class="h-72">
          <canvas id="monthlyOrdersChart"></canvas>
        </div>
      </div>

      
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Produk ({{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }})</h3>
        <div class="h-72">
          <canvas id="productPieChart"></canvas>
        </div>
      </div>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-start">
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <div class="ml-4">
            <h4 class="font-semibold text-gray-900">Kelola Order</h4>
            <p class="text-sm text-gray-600 mt-1">Kelola pesanan pelanggan, ubah status, dan lihat detail.</p>
            <div class="mt-3">
              <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Buka Order
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-start">
          <div class="p-3 bg-green-100 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <h4 class="font-semibold text-gray-900">Kelola Assignment</h4>
            <p class="text-sm text-gray-600 mt-1">Tugaskan order ke driver/guide, pantau status pengerjaan.</p>
            <div class="mt-3">
              <a href="{{ route('assignments.index') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Kelola Assignment
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>

    
    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Order Hari Ini ({{ \Carbon\Carbon::today()->format('d F Y') }})</h3>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                {{ $todaysOrders->count() }} Orders
            </span>
        </div>
        
        @if($todaysOrders->isEmpty())
            <div class="text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-gray-500">Tidak ada order untuk hari ini</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Passengers</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($todaysOrders as $order)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ \Carbon\Carbon::parse($order->pickup_time)->format('H:i') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    Pickup
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</div>
                                <div class="text-xs text-gray-500">{{ $order->phone ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ $order->product->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $order->passengers ?? 0 }} orang</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap
                                    {{ $order->status == 'completed' ? 'bg-green-100 text-green-800' :
                                       ($order->status == 'assigned' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($order->assignments->isNotEmpty())
                                    @foreach($order->assignments as $assignment)
                                        <div class="text-xs">
                                            <span class="font-medium">{{ $assignment->driver->name ?? '-' }}</span>
                                            @if($assignment->vehicle)
                                                <span class="text-gray-500">({{ $assignment->vehicle->plate_number }})</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum di-assign</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <x-primary-button :href="route('orders.show', $order)" class="text-xs px-2 py-1">
                                    Detail
                                </x-primary-button>
                                @if($order->status == 'pending')
                                    <x-success-button :href="route('assignments.create', ['order' => $order->id])" class="text-xs px-2 py-1">
                                        Assign
                                    </x-success-button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    
    
    
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tugas Hari Ini & Sedang Berjalan</h3>
            @php
               // We should fetch this from controller ideally, but for now we can rely on view composer or mock if variable not passed?
               // The controller for DashboardController needs to pass 'todaysAssignments' and 'inProgressAssignments'
               // Assuming they are passed or we can fetch directly if needed (not active practice but okay for quick view update if controller code not shown/accessible in same step).
               // Let's assume passed variable: $todaysTasks
               $todaysTasks = $todaysTasks ?? collect(); 
            @endphp
            
            @if($todaysTasks->isEmpty())
                <p class="text-gray-500 italic text-sm">Tidak ada tugas aktif untuk hari ini.</p>
            @else
                <div class="overflow-y-auto max-h-96 space-y-3">
                   @foreach($todaysTasks as $task)
                     <div class="border-l-4 {{ $task->status == 'accepted' ? 'border-blue-500 bg-blue-50' : 'border-yellow-500 bg-yellow-50' }} p-3 rounded shadow-sm">
                        <div class="flex justify-between items-start">
                            <div class="text-sm">
                                <div class="font-bold text-gray-800">#{{ $task->order_id }} - {{ $task->order->customer_name ?? 'Guest' }}</div>
                                <div class="text-gray-600">{{ \Carbon\Carbon::parse($task->order->pickup_time)->format('H:i') }} - {{ $task->order->product->name ?? '-' }}</div>
                                <div class="mt-1">
                                    <span class="text-xs font-semibold">Driver:</span> {{ $task->driver->name ?? '-' }}
                                    @if($task->vehicle)
                                     | <span class="text-xs font-semibold">Mobil:</span> {{ $task->vehicle->plate_number }}
                                    @endif
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-bold rounded {{ $task->status == 'accepted' ? 'bg-blue-200 text-blue-800' : 'bg-yellow-200 text-yellow-800' }}">
                                {{ $task->status == 'accepted' ? 'In Progress' : 'Pending/Today' }}
                            </span>
                        </div>
                     </div>
                   @endforeach
                </div>
            @endif
        </div>

        
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Driver/Guide (Jam Kerja Digunakan)</h3>
            @if(!empty($topDrivers))
                <div class="space-y-4">
                  @foreach($topDrivers as $driver)
                    <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition flex items-center justify-between">
                      <div class="flex items-center space-x-3">
                          <div class="bg-gray-200 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
                             {{ substr($driver['name'], 0, 2) }}
                          </div>
                          <div>
                              <div class="text-sm font-medium text-gray-900">{{ $driver['name'] }}</div>
                              <div class="text-xs text-gray-500">{{ $driver['role'] ?? 'Driver' }}</div>
                          </div>
                      </div>
                      <div class="text-right">
                          <div class="text-sm font-bold text-gray-800">{{ $driver['used_hours'] }}h</div>
                          <div class="w-24 bg-gray-200 rounded-full h-1.5 mt-1">
                             <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $driver['total_hours'] ? min(100, round(($driver['used_hours'] / $driver['total_hours']) * 100)) : 0 }}%"></div>
                          </div>
                      </div>
                    </div>
                  @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                  <p class="mt-2 text-sm">Tidak ada data driver/guide untuk bulan ini.</p>
                </div>
            @endif
        </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // === Monthly Orders & Completed Chart ===
    const monthlyOrders = @json($monthlyOrders);
    const ctx1 = document.getElementById('monthlyOrdersChart');
    if (ctx1) {
      const labels = monthlyOrders.map(i => i.label);
      const orderData = monthlyOrders.map(i => i.orders || 0);
      const completedData = monthlyOrders.map(i => i.completed || 0);

      new Chart(ctx1, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Total Orders',
              data: orderData,
              backgroundColor: 'rgba(59, 130, 246, 0.7)',
              borderColor: 'rgba(59, 130, 246, 1)',
              borderWidth: 1,
              borderRadius: 4,
            },
            {
              label: 'Completed (Driver/Guide)',
              data: completedData,
              backgroundColor: 'rgba(16, 185, 129, 0.7)',
              borderColor: 'rgba(16, 185, 129, 1)',
              borderWidth: 1,
              borderRadius: 4,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top' },
            tooltip: { backgroundColor: 'rgba(0,0,0,0.8)' }
          },
          scales: {
            x: {
              grid: { display: false }
            },
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
    }

    // === Product Pie Chart ===
    const productDist = @json($productDistribution);
    const ctx2 = document.getElementById('productPieChart');
    if (ctx2) {
      const labels = productDist.map(i => i.label);
      const data = productDist.map(i => i.count || 0);

      if (data.length === 0 || data.every(v => v === 0)) {
        const ctx = ctx2.getContext('2d');
        ctx.font = '14px sans-serif';
        ctx.fillStyle = '#9ca3af';
        ctx.textAlign = 'center';
        ctx.fillText('Tidak ada data produk', ctx2.width / 2, ctx2.height / 2);
      } else {
        new Chart(ctx2, {
          type: 'pie',
          data: {
            labels: labels,
            datasets: [{
              data: data,
              backgroundColor: [
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
                '#8b5cf6', '#ec4899', '#06b6d4', '#6b7280'
              ],
              borderWidth: 2,
              borderColor: '#fff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' },
              tooltip: { backgroundColor: 'rgba(0,0,0,0.8)' }
            }
          }
        });
      }
    }
  });
</script>


@if(auth()->check() && auth()->user()->role === 'staff')
<script>
  (function () {
    let lastOrderId = {{ (int) $lastOrderId }};

    function requestNotificationPermission() {
      if (!('Notification' in window)) {
        console.warn('Browser tidak mendukung Notification API.');
        return;
      }

      if (Notification.permission === 'default') {
        Notification.requestPermission().then(function (result) {
          console.log('Notification permission:', result);
        });
      }
    }

    function showOrderNotification(order) {
      if (!('Notification' in window)) {
        alert('Order baru #' + order.id + ' dari ' + (order.customer_name || '-'));
        return;
      }

      if (Notification.permission !== 'granted') {
        // fallback kalau user belum kasih izin
        alert('Order baru #' + order.id + ' dari ' + (order.customer_name || '-'));
        return;
      }

      const title = 'Order Baru #' + order.id;
      const body  =
        (order.customer_name ? 'Customer: ' + order.customer_name + '\n' : '') +
        (order.pickup_time   ? 'Pickup: '   + order.pickup_time : '');

      const notification = new Notification(title, {
        body: body,
        icon: '/favicon.ico', 
      });

      notification.onclick = function () {
        window.focus();
        window.location.href = "{{ route('orders.index') }}";
      };
    }

    function checkNewOrders() {
      fetch("{{ route('orders.check-latest') }}?after_id=" + lastOrderId, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(response => response.json())
        .then(data => {
          if (data.has_new && data.order) {
            lastOrderId = data.order.id;
            showOrderNotification(data.order);
          }
        })
        .catch(err => {
          console.error('Error checking new orders:', err);
        });
    }

    requestNotificationPermission();
    // cek tiap 15 detik
    setInterval(checkNewOrders, 15000);
  })();
</script>
@endif
@endpush
@endsection
