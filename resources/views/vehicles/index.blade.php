@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Vehicles</h1>
    <a href="{{ route('vehicles.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Tambah Kendaraan</a>
  </div>

  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-2">
    <div>
      <input name="search" value="{{ request('search') }}" placeholder="Cari brand / tipe / plat" class="mt-1 block w-full rounded border-gray-200 px-3 py-2" />
    </div>
    <div>
      <select name="status" class="mt-1 block w-full rounded border-gray-200 px-3 py-2">
        <option value="">Semua Status</option>
        <option value="available" @if(request('status')=='available') selected @endif>Available</option>
        <option value="in_use" @if(request('status')=='in_use') selected @endif>In Use</option>
        <option value="maintenance" @if(request('status')=='maintenance') selected @endif>Maintenance</option>
      </select>
    </div>
    <div class="flex items-end">
      <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
      <a href="{{ route('vehicles.index') }}" class="ml-2 px-3 py-2 bg-gray-200 rounded">Reset</a>
    </div>
  </form>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">#</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Brand / Type</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Plat</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Capacity</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Year</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($vehicles as $v)
          @php
            $activeAssign = $v->assignments->first();
            
            // prepare safe JSON payload for onclick
            $payload = [
              'id' => $v->id,
              'brand' => $v->brand,
              'type' => $v->type,
              'plate' => $v->plate_number,
              'color' => $v->color,
              'capacity' => $v->capacity,
              'year' => $v->year,
              'status' => $v->status,
              // Usage details
              'driver_name' => $activeAssign?->driver?->name,
              'customer_name' => $activeAssign?->order?->customer_name,
              'passengers' => $activeAssign?->order?->passengers,
              'pickup_location' => $activeAssign?->order?->pickup_location,
              'destination' => $activeAssign?->order?->destination,
              'pickup_time' => $activeAssign?->order?->pickup_time ? \Carbon\Carbon::parse($activeAssign->order->pickup_time)->format('d M H:i') : null,
              'estimated_end_time' => ($activeAssign?->order?->pickup_time && $activeAssign->order->estimated_duration_minutes) 
                  ? \Carbon\Carbon::parse($activeAssign->order->pickup_time)->addMinutes($activeAssign->order->estimated_duration_minutes)->format('d M H:i') 
                  : null,
            ];
            // encode and escape quotes so it can be printed raw inside onclick attribute
            $payloadJson = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
          @endphp

          <tr>
            <td class="px-4 py-3 text-sm">{{ $v->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $v->brand }} — {{ $v->type }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ $v->plate_number }}</td>
            <td class="px-4 py-3 text-sm">{{ $v->capacity }}</td>
            <td class="px-4 py-3 text-sm">{{ $v->year ?? '-' }}</td>
            <td class="px-4 py-3 text-sm">
              @if($v->status === 'in_use')
                <button onclick="openUsageModal({!! $payloadJson !!})" class="px-2! py-1! rounded text-xs bg-yellow-100 text-yellow-800 hover:bg-yellow-200 cursor-pointer underline decoration-dotted">
                   In Use <i class="ph ph-info text-[10px] ml-1"></i>
                </button>
              @elseif($v->status === 'available')
                <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">Available</span>
              @else
                <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-800">{{ ucfirst($v->status) }}</span>
              @endif
            </td>
            <td class="px-4 py-3 text-sm text-right">
              
              <button onclick="openVehicleModal({!! $payloadJson !!})" class="inline-flex items-center px-2 py-1 bg-indigo-600 text-white rounded text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Detail
              </button>

              <x-edit-button :href="route('vehicles.edit', $v)">Edit</x-edit-button>

              <form action="{{ route('vehicles.destroy', $v) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus kendaraan?')">
                @csrf @method('DELETE')
                <button class="inline-flex items-center px-2 py-1 ml-1 bg-red-600 text-white rounded text-xs">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Hapus
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="p-4 text-center text-gray-500">Belum ada kendaraan.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $vehicles->links() }}
    </div>
  </div>
</div>


<div x-data="vehicleModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded-lg shadow-xl max-w-xl w-full p-6 z-50 transform transition-all">
    <div class="flex items-start justify-between border-b pb-3 mb-4">
      <h3 class="text-xl font-bold text-gray-900">Vehicle Info</h3>
      <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="space-y-4">
      
      <div class="bg-indigo-50 p-4 rounded border border-indigo-100 mb-2">
         <span class="block text-xs font-semibold text-indigo-600 uppercase">Kendaraan</span>
         <div class="flex items-baseline space-x-2">
            <span class="text-xl font-bold text-indigo-900" x-text="payload.brand"></span>
            <span class="text-lg text-indigo-700 font-medium" x-text="payload.type"></span>
         </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Plat Nomor</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.plate"></span>
         </div>
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Kapasitas</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.capacity + ' Orang'"></span>
         </div>
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Warna</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.color || '-'"></span>
         </div>
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Tahun</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.year || '-'"></span>
         </div>
      </div>

      <div>
         <span class="block text-sm font-semibold text-gray-700 mb-1">Status</span>
         <span class="inline-block px-3 py-1 rounded text-sm font-medium uppercase" 
               :class="{
                  'bg-green-100 text-green-800': payload.status === 'available',
                  'bg-yellow-100 text-yellow-800': payload.status === 'in_use',
                  'bg-red-100 text-red-800': payload.status === 'maintenance'
               }"
               x-text="payload.status ? payload.status.replace('_', ' ') : '-'"></span>
      </div>
    </div>

    <div class="mt-6 pt-4 border-t flex justify-end space-x-3">
      <a :href="'/vehicles/' + payload.id + '/edit'" class="px-4 py-2 bg-yellow-400 text-white rounded shadow hover:bg-yellow-500 transition-colors font-medium">Edit</a>
      <button @click="close()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded shadow hover:bg-gray-300 transition-colors font-medium">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal In Use (Usage Details) -->
<div x-data="usageModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 z-50 transform transition-all border-t-4 border-yellow-500">
    <div class="flex items-start justify-between border-b pb-3 mb-4">
      <div class="flex items-center gap-2">
          <div class="p-2 bg-yellow-100 rounded-full text-yellow-600">
            <i class="ph ph-steering-wheel text-xl"></i>
          </div>
          <div>
              <h3 class="text-lg font-bold text-gray-900">Detail Penggunaan</h3>
              <p class="text-xs text-gray-500" x-text="payload.brand + ' - ' + payload.plate"></p>
          </div>
      </div>
      <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="space-y-4">
        <div class="flex justify-between items-center border-b pb-2 mb-2">
            <span class="text-gray-500">Customer</span>
            <span class="font-bold text-gray-900 text-lg" x-text="payload.customer_name || '-'"></span>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                 <span class="block text-xs text-gray-400 uppercase">Driver</span>
                 <span class="text-sm font-semibold text-gray-800" x-text="payload.driver_name"></span>
            </div>
            <div class="text-right">
                 <span class="block text-xs text-gray-400 uppercase">Penumpang</span>
                 <span class="text-sm font-semibold text-gray-800" x-text="(payload.passengers || 0) + ' Orang'"></span>
            </div>
        </div>

        <div class="mt-2 bg-blue-50 rounded p-3 border border-blue-100">
            <div class="flex items-center gap-2 mb-2">
                <i class="ph ph-clock text-blue-500"></i>
                <span class="text-xs font-semibold text-blue-700">Waktu & Estimasi</span>
            </div>
            <div class="flex justify-between text-sm text-gray-700">
                 <span>Mulai: <strong x-text="payload.pickup_time || '-'"></strong></span>
                 <span>Selesai: <strong x-text="payload.estimated_end_time || '-'"></strong></span>
            </div>
        </div>

        <div class="mt-2">
            <span class="block text-xs text-gray-400 uppercase mb-1">Rute Perjalanan</span>
            <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                <div class="flex-1 font-medium text-gray-900" x-text="payload.pickup_location || 'Lokasi Jemput'"></div>
                <i class="ph ph-arrow-right text-gray-400"></i>
                <div class="flex-1 font-medium text-gray-900 text-right" x-text="payload.destination || 'Tujuan'"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 pt-4 border-t flex justify-end">
      <button @click="close()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors font-medium">Tutup</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function vehicleModal(){
    return {
      open:false,
      payload:{},
      init(){
        window.addEventListener('open-vehicle-modal', (e)=> {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close(){ this.open=false; this.payload={}; }
    }
  }

  function usageModal(){
    return {
      open:false,
      payload:{},
      init(){
        window.addEventListener('open-usage-modal', (e)=> {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close(){ this.open=false; this.payload={}; }
    }
  }

  function openVehicleModal(data){
    const evt = new CustomEvent('open-vehicle-modal', { detail: data });
    window.dispatchEvent(evt);
  }

  function openUsageModal(data){
    const evt = new CustomEvent('open-usage-modal', { detail: data });
    window.dispatchEvent(evt);
  }
</script>
@endpush

@endsection
