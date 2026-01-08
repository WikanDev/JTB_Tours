@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-7xl mx-auto p-4">
  
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
    <h1 class="text-2xl font-semibold">Orders</h1>
    <div class="flex flex-wrap gap-2">
      @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
        <x-primary-button :href="route('orders.create')">Buat Order</x-primary-button>
      @endif

      <x-secondary-button :href="route('reports.export.excel', array_merge(request()->query(), ['type' => 'orders']))">Export Excel</x-secondary-button>
      <x-secondary-button :href="route('reports.export.pdf', array_merge(request()->query(), ['type' => 'orders']))">Export PDF</x-secondary-button>
    </div>
  </div>

  
  <form method="GET" class="mb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
    <div>
      <label class="block text-xs text-gray-600">Cari</label>
      <x-text-input name="q" value="{{ request('q') }}" placeholder="nama / telepon / produk" />
    </div>

    <div>
      <label class="block text-xs text-gray-600">Product</label>
      <select name="product_id" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        @foreach(($products ?? collect()) as $p)
          @if(is_object($p))
            <option value="{{ $p->id }}" @if(request('product_id') == $p->id) selected @endif>
              {{ $p->name ?? 'Unknown Product' }}
            </option>
          @endif
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">Status</label>
      <select name="status" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        <option value="pending" @if(request('status')=='pending') selected @endif>Pending</option>
        <option value="assigned" @if(request('status')=='assigned') selected @endif>Assigned</option>
        <option value="completed" @if(request('status')=='completed') selected @endif>Completed</option>
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">From</label>
      <input type="date" name="from" value="{{ request('from') }}" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div>
      <label class="block text-xs text-gray-600">To</label>
      <input type="date" name="to" value="{{ request('to') }}" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div class="flex items-end gap-2">
      <x-primary-button type="submit">Filter</x-primary-button>
      <x-secondary-button :href="route('orders.index')">Reset</x-secondary-button>
    </div>
  </form>

  
  <div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-gray-700 text-xs uppercase tracking-wider">
        <tr>
          <th class="px-4 py-2 text-left">#</th>
          <th class="px-4 py-2 text-left">Customer</th>
          <th class="px-4 py-2 text-left">Pickup / Arrival</th>
          <th class="px-4 py-2 text-left">Product</th>
          <th class="px-4 py-2 text-left">People</th>
          <th class="px-4 py-2 text-left">Status</th>
          <th class="px-4 py-2 text-right">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($orders as $o)
          <tr class="hover:bg-gray-50">
            
            <td class="px-4 py-3">{{ $orders->firstItem() ? $orders->firstItem() + $loop->index : $loop->iteration }}</td>

            <td class="px-4 py-3">
              <div class="font-medium">{{ $o->customer_name ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $o->summary_contact ?? '-' }}</div>
            </td>

            <td class="px-4 py-3">
              <div>{{ $o->formatted_pickup ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $o->formatted_arrival ?? '-' }}</div>
            </td>

            <td class="px-4 py-3">{{ optional($o->product)->name ?? '-' }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ $o->summary_people ?? '-' }}</td>

            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded text-xs font-medium whitespace-nowrap
                {{ $o->status == 'completed' ? 'bg-blue-100 text-blue-800' :
                   ($o->status=='assigned' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                {{ ucfirst(str_replace('_', ' ', $o->status ?? 'pending')) }}
              </span>
            </td>

            <td class="px-4 py-3 text-right whitespace-nowrap">
              @php
                $payload = [
                  'id' => $o->id,
                  'customer' => $o->customer_name ?? '-',
                  'email' => $o->email ?? '-',
                  'phone' => $o->phone ?? '-',
                  'pickup' => $o->formatted_pickup ?? '-',
                  'arrival' => $o->formatted_arrival ?? '-',
                  'from' => $o->pickup_location ?? '-',
                  'to' => $o->destination ?? '-',
                  'product' => optional($o->product)->name ?? '-',
                  'adults' => $o->adults ?? 0,
                  'children' => $o->children ?? 0,
                  'babies' => $o->babies ?? 0,
                  'note' => $o->note ?? '-',
                  'status' => $o->status ?? 'pending',
                ];
                $jsonPayload = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
              @endphp

              <x-primary-button type="button" onclick="openOrderModal({!! $jsonPayload !!})" class="text-xs px-2 py-1 inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Detail
              </x-primary-button>

              @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin']))
                <x-edit-button :href="route('orders.edit', $o)">Edit</x-edit-button>

                <button 
                  type="button"
                  onclick="confirmDelete('{{ route('orders.destroy', $o) }}', 'Hapus Order', 'Apakah Anda yakin ingin menghapus order ini?')"
                  class="inline-flex items-center px-2 py-1 ml-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Hapus
                </button>

              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="p-4 text-center text-gray-500">Belum ada order.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">{{ $orders->links() }}</div>
  </div>
</div>

<div x-data="orderModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 z-50 transform transition-all overflow-y-auto max-h-[90vh]">
    <div class="flex items-start justify-between border-b pb-3 mb-4">
      <h3 class="text-xl font-bold text-gray-900">Order #<span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="space-y-4">
       
       <div class="bg-blue-50 p-4 rounded border border-blue-100 mb-2">
         <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
               <span class="block text-xs font-semibold text-blue-600 uppercase">Customer</span>
               <div class="text-lg font-bold text-blue-900" x-text="payload.customer"></div>
               <div class="text-sm text-blue-700">
                  <span x-text="payload.email"></span> • <span x-text="payload.phone"></span>
               </div>
            </div>
            <div class="text-right">
               <span class="block text-xs font-semibold text-blue-600 uppercase">Product</span>
               <div class="text-lg font-medium text-blue-900" x-text="payload.product"></div>
            </div>
         </div>
       </div>

       
       <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 p-3 rounded">
          <div>
            <span class="block text-xs font-semibold text-gray-500 uppercase">Adults</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.adults"></span>
          </div>
          <div>
            <span class="block text-xs font-semibold text-gray-500 uppercase">Children</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.children"></span>
          </div>
          <div>
            <span class="block text-xs font-semibold text-gray-500 uppercase">Babies</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.babies"></span>
          </div>
          <div>
            <span class="block text-xs font-semibold text-gray-500 uppercase">Status</span>
            <span class="inline-block px-2 py-0.5 rounded text-sm font-medium uppercase" 
                :class="{
                  'bg-yellow-100 text-yellow-800': payload.status === 'pending',
                  'bg-green-100 text-green-800': payload.status === 'assigned',
                  'bg-blue-100 text-blue-800': payload.status === 'completed'
                }"
                x-text="payload.status ? payload.status.replace('_', ' ') : ''"></span>
          </div>
       </div>

       
       <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-gray-50 p-3 rounded">
             <span class="block text-xs font-semibold text-gray-500 uppercase">Pickup Time</span>
             <span class="text-base font-medium text-gray-900" x-text="payload.pickup"></span>
          </div>
          <div class="bg-gray-50 p-3 rounded">
             <span class="block text-xs font-semibold text-gray-500 uppercase">Flight Arrival</span>
             <span class="text-base font-medium text-gray-900" x-text="payload.arrival"></span>
          </div>
       </div>

       <div class="bg-gray-50 p-3 rounded border border-gray-100">
         <span class="block text-xs font-semibold text-gray-500 uppercase mb-1">Rute (Pickup → Destination)</span>
         <div class="flex items-center text-sm font-medium text-gray-900">
            <span x-text="payload.from"></span>
            <span class="mx-2 text-gray-400">→</span>
            <span x-text="payload.to"></span>
         </div>
       </div>

       
       <div x-show="payload.note && payload.note !== '-'">
         <span class="block text-sm font-semibold text-gray-700 mb-1">Catatan (Note)</span>
         <div class="p-3 bg-yellow-50 rounded text-sm text-gray-800 border border-yellow-100 italic" x-text="payload.note"></div>
       </div>
    </div>

    <div class="mt-6 pt-4 border-t flex justify-end">
      <x-secondary-button type="button" @click="close()">Tutup</x-secondary-button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function orderModal() {
    return {
      open: false,
      payload: {},
      init() {
        window.addEventListener('open-order-modal', (e) => {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close() { this.open = false; this.payload = {}; }
    }
  }

  function openOrderModal(data) {
    try {
      window.dispatchEvent(new CustomEvent('open-order-modal', { detail: data }));
    } catch (err) {
      console.error('openOrderModal error', err);
    }
  }
</script>
@endpush
@endsection
