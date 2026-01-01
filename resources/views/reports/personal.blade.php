@extends('layouts.app')
@section('title', 'Laporan - ' . ucfirst($user->role))

@section('content')
@php
    // Dari controller: $assignments, $summary, $chartData, $start, $end, $user, $usedHours, $totalHours, $usagePercent

    $totalTugas     = $summary['total']     ?? $assignments->count();
    $completedTugas = $summary['completed'] ?? $assignments->where('status', 'completed')->count();

    // Label periode (pakai awal periode)
    $periodeLabel = $start->format('F Y');

    // “Tugas terbaru” batasi 10
    $recentAssignments = $assignments->take(10);

    // Tentukan warna progress bar jam kerja
    if ($usagePercent > 90) {
        $usageClass = 'bg-red-500';
    } elseif ($usagePercent > 70) {
        $usageClass = 'bg-yellow-500';
    } else {
        $usageClass = 'bg-green-500';
    }
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
  
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Laporan {{ ucfirst($user->role) }}</h1>
      <p class="text-sm text-gray-500">
        Halo, <span class="font-medium">{{ $user->name }}</span>
        <span class="ml-1">— periode {{ $start->format('d M Y') }} s/d {{ $end->format('d M Y') }}</span>
      </p>
    </div>
    <div class="text-sm bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full">
      {{ $periodeLabel }}
    </div>
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
          <h3 class="text-sm font-medium text-gray-500">Total Tugas</h3>
          <p class="text-2xl font-bold text-gray-900">{{ $totalTugas }}</p>
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
          <h3 class="text-sm font-medium text-gray-500">Jam Kerja</h3>
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
        {{ $usagePercent }}% digunakan
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
          <h3 class="text-sm font-medium text-gray-500">Selesai Periode Ini</h3>
          <p class="text-2xl font-bold text-gray-900">
            {{ $completedTugas }}
          </p>
        </div>
      </div>
    </div>
  </div>

  
  <div class="bg-white rounded-lg shadow p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-900">Performa Tugas per Hari</h2>
      <p class="text-xs text-gray-500">
        Menampilkan total tugas dan tugas selesai per hari untuk periode dipilih.
      </p>
    </div>
    <div class="h-72">
      <canvas id="assignmentsChart"></canvas>
    </div>
  </div>

  
  <div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b">
      <h2 class="text-lg font-semibold text-gray-900">Tugas Terbaru</h2>
    </div>
    <div class="divide-y">
      @if($recentAssignments->isEmpty())
        <div class="px-5 py-8 text-center text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="mt-2">Belum ada tugas pada periode ini.</p>
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
    const rawData = @json($chartData);

    console.log('chartData:', rawData);

    if (!rawData || rawData.length === 0) {
      console.warn('Tidak ada data untuk chart');
      return;
    }

    const labels = rawData.map(item => item.date);
    const completedData = rawData.map(item => item.completed);
    const totalData = rawData.map(item => item.total);

    const canvas = document.getElementById('assignmentsChart');
    if (!canvas) {
      console.error('Canvas #assignmentsChart tidak ditemukan');
      return;
    }

    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
      type: 'bar', // Changed from line to bar
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Total Tugas',
            data: totalData,
            borderColor: 'rgba(37, 99, 235, 1)',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 2,
            tension: 0.3,
          },
          {
            label: 'Tugas Selesai',
            data: completedData,
            borderColor: 'rgba(16, 185, 129, 1)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            tension: 0.3,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        scales: {
          x: {
            ticks: {
              autoSkip: true,
              maxTicksLimit: 10,
            }
          },
          y: {
            beginAtZero: true,
            precision: 0
          }
        },
        plugins: {
          legend: {
            position: 'bottom'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + context.parsed.y + ' tugas';
              }
            }
          }
        }
      }
    });
  })();
</script>
@endsection
