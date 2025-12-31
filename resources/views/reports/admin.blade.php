@extends('layouts.app')

@section('title','Laporan - JTB Tours')

@section('content')
<div class="container mx-auto">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Laporan</h1>

    <div class="flex space-x-2">
      <form method="GET" action="{{ route('reports.index') }}" class="flex items-center space-x-2">
        <input type="number" name="year" value="{{ $year }}" class="border p-2 rounded w-28" />
        <select name="month" class="border p-2 rounded">
          <option value="">All months</option>
          @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $m }}</option>
          @endfor
        </select>
        <button class="bg-blue-600 text-white px-3 py-2 rounded">Filter</button>
      </form>

      <a href="{{ route('reports.export.excel', ['year'=>$year, 'month'=>$month]) }}" class="bg-green-600 text-white px-3 py-2 rounded">Export Excel</a>
      <a href="{{ route('reports.export.pdf', ['year'=>$year, 'month'=>$month]) }}" class="bg-gray-700 text-white px-3 py-2 rounded">Export PDF</a>
    </div>
  </div>


  {{-- Flash messages now handled by global notification card component --}}

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-4 rounded shadow">
      <h3 class="text-lg font-medium mb-2">Jumlah Order per Bulan ({{ $year }})</h3>
      
      <div class="w-full h-56">
        <canvas id="ordersChart" class="w-full h-full"></canvas>
      </div>
    </div>

    <div class="bg-white p-4 rounded shadow">
      <h3 class="text-lg font-medium mb-2">Assignment (Accepted) per Bulan ({{ $year }})</h3>
      <div class="w-full h-56">
        <canvas id="assignmentsChart" class="w-full h-full"></canvas>
      </div>
    </div>

    <div class="bg-white p-4 rounded shadow lg:col-span-2">
      <h3 class="text-lg font-medium mb-2">Product Usage (Accepted Assignments) - {{ $year }}</h3>
      <div class="flex items-center space-x-6">
        <div class="w-48 h-48 shrink-0">
          <canvas id="productPie" class="w-full h-full"></canvas>
        </div>
        <div class="flex-1">
          <ul id="productLegend" class="text-sm"></ul>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // --- data from server (PHP -> JS)
  const ordersPerMonth = {!! json_encode(array_values($ordersPerMonth)) !!}; // index 0..11
  const acceptedPerMonth = {!! json_encode(array_values($acceptedPerMonth)) !!};
  const productUsage = {!! json_encode($productUsage->map(function($p){ return ['name'=>$p->name,'total'=> (int)$p->total]; })->values()) !!};

  const monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  // --- helper: destroy existing charts (defensive)
  window._charts = window._charts || {};
  function destroyChartIfExists(key) {
    if (window._charts[key]) {
      try {
        window._charts[key].destroy();
      } catch (e) {
        console.warn('Error destroying chart', key, e);
      }
      window._charts[key] = null;
    }
  }

  // ORDERS line chart
  (function(){
    const container = document.getElementById('ordersChart').closest('div');
    // ensure container has height; we set via Tailwind .h-56 so Chart has a stable height
    destroyChartIfExists('ordersChart');

    const ctxOrders = document.getElementById('ordersChart').getContext('2d');
    window._charts.ordersChart = new Chart(ctxOrders, {
      type: 'line',
      data: {
        labels: monthLabels,
        datasets: [{
          label: 'Jumlah Order',
          data: ordersPerMonth,
          fill: false,
          tension: 0.2,
          borderWidth: 2,
          pointRadius: 4,
        }]
      },
      options: {
        responsive: true,
        // maintainAspectRatio true so chart respects its container height
        maintainAspectRatio: true,
        scales: {
          y: { beginAtZero: true, ticks: { precision:0 } }
        },
        plugins: { legend: { display: true } }
      }
    });
  })();

  // ASSIGNMENTS line chart
  (function(){
    destroyChartIfExists('assignmentsChart');

    const ctxAssign = document.getElementById('assignmentsChart').getContext('2d');
    window._charts.assignmentsChart = new Chart(ctxAssign, {
      type: 'line',
      data: {
        labels: monthLabels,
        datasets: [{
          label: 'Accepted Assignments',
          data: acceptedPerMonth,
          fill: false,
          tension: 0.2,
          borderWidth: 2,
          pointRadius: 4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          y: { beginAtZero: true, ticks: { precision:0 } }
        },
        plugins: { legend: { display: true } }
      }
    });
  })();

  // PRODUCT PIE chart
  (function(){
    destroyChartIfExists('productPie');

    const pieCtx = document.getElementById('productPie').getContext('2d');
    const productLabels = productUsage.map(p => p.name);
    const productData = productUsage.map(p => p.total);

    // Ensure Chart.js will auto generate colors by leaving backgroundColor empty.
    // Chart.js v3+ will auto pick default colors for pie if backgroundColor omitted for each data point.
    window._charts.productPie = new Chart(pieCtx, {
      type: 'pie',
      data: {
        labels: productLabels,
        datasets: [{
          data: productData,
          borderWidth: 1,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false } // we render custom legend
        }
      }
    });

    // render legend
    const legendEl = document.getElementById('productLegend');
    legendEl.innerHTML = ''; // clear first
    productUsage.forEach((p, idx) => {
      const li = document.createElement('li');
      li.className = 'mb-2 flex items-center';

      const color = window._charts.productPie.data.datasets[0].backgroundColor?.[idx] || getComputedStyle(document.documentElement).getPropertyValue('--tw-prose-code') || '#999';

      const box = document.createElement('span');
      box.style.display = 'inline-block';
      box.style.width = '12px';
      box.style.height = '12px';
      box.style.background = color;
      box.style.marginRight = '8px';
      box.style.verticalAlign = 'middle';
      box.className = 'flex-shrink-0';
      li.appendChild(box);

      const text = document.createTextNode(`${p.name} — ${p.total}`);
      li.appendChild(text);
      legendEl.appendChild(li);
    });
  })();

  // Optional: on window resize sometimes Chart.js recalculates; charts stored in window._charts
  // No extra action needed; Chart.js will handle resize automatically.
</script>
@endsection
