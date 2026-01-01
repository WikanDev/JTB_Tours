@extends('layouts.app')
@section('title', 'Dashboard - ' . ucfirst(auth()->user()->role))

@section('content')
@php
    $user              = auth()->user();
    $assignmentsCount  = $assignmentsCount  ?? 0;
    $recentAssignments = $recentAssignments ?? collect();
    $usedHours         = $usedHours         ?? 0;
    $totalHours        = $totalHours        ?? ($user->monthly_work_limit ?? 200);
    $usagePercent      = $usagePercent      ?? 0;
    $completedThisMonth = $completedThisMonth ?? 0;
    $month             = $month ?? now()->month;
    $year              = $year  ?? now()->year;
    $completedPerMonth = $completedPerMonth ?? [];
    $availableYears    = $availableYears    ?? [now()->year];

    // Tentukan warna progress bar
    if ($usagePercent > 90) {
        $usageClass = 'bg-red-500';
    } elseif ($usagePercent > 70) {
        $usageClass = 'bg-yellow-500';
    } else {
        $usageClass = 'bg-green-500';
    }

    $dashboardRoute = $user->role === 'driver' ? 'dashboard.driver' : 'dashboard.guide';
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
  
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Dashboard {{ ucfirst($user->role) }}</h1>
      <p class="text-sm text-gray-500">
        Halo, <span class="font-medium">{{ $user->name }}</span>
      </p>
      <p class="text-xs text-gray-400 mt-1">
        Bulan aktif untuk ringkasan: {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
      </p>
    </div>

    <form
      method="GET"
      action="{{ route($dashboardRoute) }}"
      class="flex items-center gap-2 text-sm"
    >

      <select
        id="year"
        name="year"
        class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"
        onchange="this.form.submit()"
      >
        @foreach($availableYears as $y)
          <option value="{{ $y }}" {{ (int)$y === (int)$year ? 'selected' : '' }}>
            {{ $y }}
          </option>
        @endforeach
      </select>
      
      
    </form>
  </div>

  
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    
    <div class="bg-white rounded-lg shadow p-5">
      <div class="flex items-center">
        <div class="p-3 bg-indigo-100 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <div class="ml-4">
          <h3 class="text-sm font-medium text-gray-500">Total Tugas Bulan Ini</h3>
          <p class="text-2xl font-bold text-gray-900">{{ $assignmentsCount }}</p>
        </div>
      </div>
    </div>

    
    <div class="bg-white rounded-lg shadow p-5">
      <div class="flex items-center">
        <div class="p-3 bg-green-100 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="ml-4">
          <h3 class="text-sm font-medium text-gray-500">Jam Kerja Bulan Ini</h3>
          <p class="text-2xl font-bold text-gray-900">{{ $usedHours }} / {{ $totalHours }} jam</p>
        </div>
      </div>
      <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
        <div 
          class="h-2 rounded-full {{ $usageClass }}" 
          style="width: {{ $usagePercent }}%">
        </div>
      </div>
      <p class="mt-1 text-xs text-gray-500">
        {{ $usagePercent }}% dari kuota jam kerja digunakan
        @if($usagePercent >= 90)
          <span class="text-red-600 font-medium">— Hampir penuh!</span>
        @endif
      </p>
    </div>

    
    <div class="bg-white rounded-lg shadow p-5">
      <div class="flex items-center">
        <div class="p-3 bg-blue-100 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="ml-4">
          <h3 class="text-sm font-medium text-gray-500">Selesai Bulan Ini</h3>
          <p class="text-2xl font-bold text-gray-900">
            {{ $completedThisMonth }}
          </p>
        </div>
      </div>
    </div>
  </div>

  
  <div class="bg-white rounded-lg shadow p-5">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">
          Tugas Selesai per Bulan ({{ $year }})
        </h2>
        <p class="text-xs text-gray-500">
          Menampilkan jumlah assignment dengan status <span class="font-semibold">completed</span> per bulan dalam tahun terpilih.
        </p>
      </div>
    </div>
    <div class="h-72">
      <canvas id="completedPerMonthChart"></canvas>
    </div>
  </div>

  
  <div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b">
      <h2 class="text-lg font-semibold text-gray-900">Tugas Terbaru Bulan Ini</h2>
    </div>
    <div class="divide-y">
      @if($recentAssignments->isEmpty())
        <div class="px-5 py-8 text-center text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="mt-2">Belum ada tugas pada bulan ini.</p>
        </div>
      @else
        @foreach($recentAssignments as $a)
          @php
            $order = $a->order ?? null;
            $pickup = $order && $order->pickup_time 
              ? \Carbon\Carbon::parse($order->pickup_time)->format('d M Y H:i') 
              : '-';

            if ($a->status === 'completed') {
                $statusClass = 'bg-green-100 text-green-800';
            } elseif ($a->status === 'accepted') {
                $statusClass = 'bg-blue-100 text-blue-800';
            } elseif ($a->status === 'pending') {
                $statusClass = 'bg-yellow-100 text-yellow-800';
            } elseif ($a->status === 'declined') {
                $statusClass = 'bg-red-100 text-red-800';
            } else {
                $statusClass = 'bg-gray-100 text-gray-800';
            }
          @endphp
          <div class="px-5 py-4 hover:bg-gray-50">
            <div class="flex justify-between">
              <div>
                <div class="font-medium text-gray-900">
                  #{{ $a->id }} — {{ $order?->customer_name ?? '-' }}
                </div>
                <div class="text-sm text-gray-500 mt-1">
                  {{ $pickup }} · {{ $order?->product?->name ?? '-' }}
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                  {{ ucfirst($a->status) }}
                </span>
                <a href="{{ route('assignments.my') }}" class="text-sm text-indigo-600 hover:underline">
                  Detail
                </a>
              </div>
            </div>
          </div>
        @endforeach
      @endif
    </div>
    <div class="px-5 py-3 bg-gray-50 text-right">
      <a href="{{ route('assignments.my') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
        Lihat semua tugas →
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  (function () {
    const rawData = @json($completedPerMonth);

    console.log('completedPerMonth:', rawData);

    const labels = rawData.map(item => item.label);
    const data   = rawData.map(item => item.completed);

    const canvas = document.getElementById('completedPerMonthChart');
    if (!canvas) {
      console.error('Canvas #completedPerMonthChart tidak ditemukan');
      return;
    }

    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Tugas selesai',
            data: data,
            borderWidth: 1,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            ticks: {
              autoSkip: false,
            }
          },
          y: {
            beginAtZero: true,
            precision: 0
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return 'Selesai: ' + context.parsed.y + ' tugas';
              }
            }
          }
        }
      }
    });
  })();
</script>
@endsection
