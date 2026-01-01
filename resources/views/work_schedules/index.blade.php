@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Work Schedules</h1>
      <div class="text-sm text-gray-500">
        Month: {{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}
        @if (now()->year == $year && now()->month == $month)
          <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded">Current Month</span>
        @endif
      </div>
    </div>

  </div>

  
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
    <div>
      <label class="block text-xs text-gray-600">Year</label>
      <input name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-1 block w-full rounded border-gray-200 px-3 py-2" />
    </div>

    <div>
      <label class="block text-xs text-gray-600">Month</label>
      <select name="month" class="mt-1 block w-full rounded border-gray-200 px-3 py-2">
        @for($m = 1; $m <= 12; $m++)
          <option value="{{ $m }}" @if($m == $month) selected @endif>
            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
          </option>
        @endfor
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">Cari (nama)</label>
      <input name="q" value="{{ request('q') }}" placeholder="cari nama driver/guide" class="mt-1 block w-full rounded border-gray-200 px-3 py-2" />
    </div>

    <div class="flex items-center">
      <button type="submit" class="px-3 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded transition">
        Terapkan
      </button>
      <a href="{{ route('work-schedules.index') }}" class="ml-2 px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded transition">
        Reset Filter
      </a>
    </div>
  </form>

  
  <form action="{{ route('work-schedules.bulkUpdate') }}" method="POST">
    @csrf
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="month" value="{{ $month }}">

    <div class="bg-white rounded shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            
            <th class="px-4 py-2 text-left text-sm font-medium">ID</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Role</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Total Hours</th>
            <th class="px-4 py-2 text-left text-sm font-medium">Used Hours</th>

          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
          @forelse($users as $u)
            @php
              $ws = $schedules[$u->id] ?? null;
              $total = $ws ? $ws->total_hours : ($u->monthly_work_limit ?? 200);
              $used = $ws ? ($ws->used_hours ?? 0) : 0;
              $isGenerated = $ws !== null;
            @endphp
            <tr class="{{ $isGenerated ? '' : 'bg-blue-50' }}">
              
              <td class="px-4 py-3 text-sm">{{ $u->id }}</td>
              <td class="px-4 py-3 text-sm">
                <div class="font-medium">{{ $u->name }}</div>
                <div class="text-xs text-gray-500">{{ $u->email ?? '-' }} · {{ $u->phone ?? '-' }}</div>
                @unless($isGenerated)
                  <span class="mt-1 inline-block px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded">
                    Not generated yet
                  </span>
                @endunless
              </td>
              <td class="px-4 py-3 text-sm">{{ ucfirst($u->role) }}</td>

              <td class="px-4 py-3 text-sm">
                <input
                  name="schedules[{{ $u->id }}]"
                  type="number"
                  min="0"
                  value="{{ old('schedules.'.$u->id, $total) }}"
                  class="w-28 px-2 py-1 rounded border-gray-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                  {{ $isGenerated ? '' : 'disabled title="Generate dulu untuk edit"' }}
                />
                @unless($isGenerated)
                  <span class="text-xs text-gray-500 ml-1">(disabled)</span>
                @endunless
              </td>

              <td class="px-4 py-3 text-sm">
                <div class="font-medium {{ $used > 0 ? 'text-gray-900' : 'text-gray-500' }}">
                  {{ $used }} jam
                </div>
                @if($isGenerated && $ws->total_hours > 0)
                  <div class="text-xs text-gray-500">
                    {{ round(($used / $ws->total_hours) * 100, 1) }}% used
                  </div>
                @endif
              </td>

            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-6 text-center text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tidak ada driver/guide ditemukan.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      @if($users->isNotEmpty())
        <div class="p-4 bg-gray-50 border-t flex items-center justify-between">
          <div class="space-x-2">
            <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm transition">
              Simpan Perubahan
            </button>
            
          </div>

          <div class="text-xs text-gray-500">
            <strong>Jam kerja</strong> di-reset otomatis tiap bulan oleh sistem.
          </div>
        </div>
      @endif
    </div>
  </form>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select_all');
    if (selectAll) {
      selectAll.addEventListener('change', function() {
        document.querySelectorAll('.row_checkbox').forEach(cb => {
          cb.checked = selectAll.checked;
        });
      });
    }
  });
</script>
@endpush

@endsection