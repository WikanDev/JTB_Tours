@extends('layouts.app')
@section('title','Dashboard - Super Admin')

@section('content')
@php
  $ordersThisMonth = $ordersThisMonth ?? 0;
  $assignedThisMonth = $assignedThisMonth ?? 0;
  $completedThisMonth = $completedThisMonth ?? 0;
  $activeDrivers = $activeDrivers ?? 0;
  $monthlyOrders = $monthlyOrders ?? [];
  $productDistribution = $productDistribution ?? [];
  $topDrivers = $topDrivers ?? [];
  $month = $month ?? \Carbon\Carbon::now()->month;
  $year = $year ?? \Carbon\Carbon::now()->year;
  $availableYears = $availableYears ?? [\Carbon\Carbon::now()->year];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6">
  
  <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h1 class="text-2xl font-semibold">Admin Dashboard (Super Admin)</h1>
    <div class="text-sm text-gray-600">Welcome, {{ auth()->user()->name }}</div>
  </div>

  
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Orders ({{ \Carbon\Carbon::create($year, $month, 1)->format('M Y') }})</div>
      <div class="text-2xl font-semibold mt-2">{{ $ordersThisMonth }}</div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Assigned</div>
      <div class="text-2xl font-semibold mt-2">{{ $assignedThisMonth }}</div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Completed</div>
      <div class="text-2xl font-semibold mt-2">{{ $completedThisMonth }}</div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <div class="text-sm text-gray-500">Active Drivers/Guides</div>
      <div class="text-2xl font-semibold mt-2">{{ $activeDrivers }}</div>
    </div>
  </div>

  
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded shadow p-4">
      <h4 class="font-semibold">Manage Users</h4>
      <p class="text-sm text-gray-600 mt-2">Create/edit users and roles.</p>
      <div class="mt-3">
        <a href="{{ route('users.index') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Users</a>
      </div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <h4 class="font-semibold">Reports</h4>
      <p class="text-sm text-gray-600 mt-2">Export PDF/Excel reports.</p>
      <div class="mt-3">
        <a href="{{ route('reports.index') }}" class="px-3 py-2 bg-indigo-600 text-white rounded">Reports</a>
      </div>
    </div>

    <div class="bg-white rounded shadow p-4">
      <h4 class="font-semibold">System</h4>
      <p class="text-sm text-gray-600 mt-2">Products & Vehicles</p>
      <div class="mt-3 flex gap-2 flex-wrap">
        <a href="{{ route('products.index') }}" class="px-3 py-2 bg-gray-800 text-white rounded">Products</a>
        <a href="{{ route('vehicles.index') }}" class="px-3 py-2 bg-gray-800 text-white rounded">Vehicles</a>
      </div>
    </div>
  </div>

  
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    
    <div class="lg:col-span-2 bg-white rounded shadow p-4">
      <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
        <h3 class="font-semibold">Orders — 12 months</h3>
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
          <label for="year" class="text-sm text-gray-600">Year:</label>
          <select name="year" id="year" onchange="this.form.submit()" class="border-gray-300 rounded text-sm py-1 px-2">
            @foreach($availableYears as $y)
              <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
          </select>
        </form>
      </div>
      <div class="h-60">
        <canvas id="monthlyOrdersChart"></canvas>
      </div>
    </div>

    
    <div class="bg-white rounded shadow p-4">
      <h3 class="font-semibold mb-2">Product Distribution ({{ \Carbon\Carbon::create($year, $month, 1)->format('M Y') }})</h3>
      <div class="h-60">
        <canvas id="productPieChart"></canvas>
      </div>
    </div>
  </div>

  
  <div class="bg-white rounded shadow p-4">
    <h4 class="font-semibold mb-3">Top Drivers (by used hours)</h4>
    @if(!empty($topDrivers))
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
        @foreach($topDrivers as $d)
          <div class="p-3 border rounded hover:shadow transition">
            <div class="font-medium">{{ $d['name'] }}</div>
            <div class="text-xs text-gray-500">Used: {{ $d['used_hours'] }} / {{ $d['total_hours'] }} hrs</div>
          </div>
        @endforeach
      </div>
    @else
      <div class="text-sm text-gray-500">No data available.</div>
    @endif
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const monthlyOrders = {!! json_encode($monthlyOrders ?? []) !!};
  const productDistribution = {!! json_encode($productDistribution ?? []) !!};

  // Small helper: safe numeric array from items and key
  function numericArray(arr, key) {
    if (!Array.isArray(arr)) return [];
    return arr.map(i => {
      const v = (i && typeof i[key] !== 'undefined') ? i[key] : 0;
      return Number(v) || 0;
    });
  }

  // === BAR CHART (Orders per month) ===
  (function(){
    try {
      const el = document.getElementById('monthlyOrdersChart');
      if(!el) return;
      const ctx = el.getContext('2d');

      const labels = (monthlyOrders || []).map(i => i.label ?? '');
      const data = numericArray(monthlyOrders, 'count');

      // destroy existing chart instance on canvas if any (useful during HMR)
      if (el._chartInstance) { el._chartInstance.destroy(); }

      el._chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Orders',
            data,
            borderWidth: 1,
            backgroundColor: '#3B82F6',
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: undefined } // let chart engine pick
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const v = context.parsed?.y ?? context.raw ?? 0;
                  return `${v} Orders`;
                }
              }
            }
          }
        }
      });
    } catch (err) {
      // don't break the rest of the page
      console.error('bar chart error', err);
    }
  })();

  // === PIE CHART (Product Distribution) ===
  (function(){
    try {
      const el = document.getElementById('productPieChart');
      if(!el) return;
      const ctx = el.getContext('2d');

      const labels = (productDistribution || []).map(i => i.label ?? 'Unknown');
      const data = numericArray(productDistribution, 'count');

      // when no meaningful data, show fallback text instead of initializing chart
      const total = data.reduce((s, n) => s + (Number(n) || 0), 0);
      if (!data.length || total === 0) {
        // clear canvas then draw text
        ctx.clearRect(0,0,el.width,el.height);
        ctx.save();
        ctx.font = '14px sans-serif';
        ctx.fillStyle = '#374151';
        ctx.fillText('Tidak ada data produk bulan ini', 10, 30);
        ctx.restore();
        return;
      }

      // destroy existing chart instance on canvas if any
      if (el._chartInstance) { el._chartInstance.destroy(); }

      const backgroundColors = [
        '#4F46E5','#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#14B8A6',
        '#F472B6','#06B6D4','#F97316'
      ];

      el._chartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: backgroundColors.slice(0, labels.length),
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: function(context) {
                  // Chart.js v3+ provides context.raw as the value
                  const value = Number(context.raw ?? 0);
                  const dataset = context.chart.data.datasets[context.datasetIndex];
                  const totalLocal = dataset.data.reduce((acc, n) => acc + (Number(n) || 0), 0);
                  const pct = totalLocal ? ((value / totalLocal) * 100).toFixed(1) : '0.0';
                  return `${context.label}: ${value} orders (${pct}%)`;
                }
              }
            }
          }
        }
      });
    } catch (err) {
      console.error('pie chart error', err);
    }
  })();
</script>
@endpush

@endsection
