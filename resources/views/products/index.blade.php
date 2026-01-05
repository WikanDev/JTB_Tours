@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-5xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Products</h1>

    <div class="flex items-center space-x-2">
      <x-primary-button :href="route('products.create')">Tambah Product</x-primary-button>
    </div>
  </div>

  <form method="GET" class="mb-4 flex gap-2">
    <x-text-input name="search" value="{{ request('search') }}" placeholder="cari nama atau deskripsi" />
    <x-primary-button type="submit">Cari</x-primary-button>
    <x-secondary-button :href="route('products.index')">Reset</x-secondary-button>
  </form>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">#</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Kapasitas</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Deskripsi</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($products as $p)
          <tr>
            <td class="px-4 py-3 text-sm">
              {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
            </td>
            <td class="px-4 py-3 text-sm">{{ $p->name }}</td>
            <td class="px-4 py-3 text-sm">{{ $p->capacity }}</td>
            <td class="px-4 py-3 text-sm truncate max-w-xl">{{ $p->description ?? '-' }}</td>
            <td class="px-4 py-3 text-sm text-right space-x-1">
              @php
                $jsonPayload = htmlspecialchars(json_encode([
                  'name' => $p->name,
                  'capacity' => $p->capacity,
                  'description' => $p->description,
                  'is_exclusive' => $p->is_exclusive,
                  'custom_exclusive_benefits' => $p->custom_exclusive_benefits,
                  'branches' => $p->branches->map(fn($b) => [
                      'name' => $b->name,
                      'duration' => $b->duration_minutes,
                      'origin' => $b->origin_region,
                      'dest' => $b->destination_region
                  ]),
                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
              @endphp

              <button onclick="openProductModal({!! $jsonPayload !!})" class="inline-flex items-center px-2 py-1 bg-indigo-600 text-white rounded text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Detail
              </button>

              <x-edit-button :href="route('products.edit', $p)">Edit</x-edit-button>

              <form action="{{ route('products.destroy', $p) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus produk ini?')">
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
          <tr><td colspan="5" class="p-4 text-center text-gray-500">Belum ada product.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $products->links() }}
    </div>
  </div>
</div>

<div x-data="productModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 z-50 transform transition-all">
    <div class="flex items-start justify-between border-b pb-3 mb-4">
      <h3 class="text-xl font-bold text-gray-900" x-text="payload.name"></h3>
      <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Kapasitas</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.capacity + ' Orang'"></span>
        </div>
        <div class="bg-gray-50 p-3 rounded" x-show="payload.is_exclusive">
            <span class="block text-xs font-semibold text-purple-600 uppercase">Status</span>
            <span class="text-lg font-medium text-purple-700">Product Eksklusif</span>
        </div>
      </div>

      <div>
        <span class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</span>
        <div class="p-3 bg-gray-50 rounded text-sm text-gray-600 whitespace-pre-line" x-text="payload.description || '-'"></div>
      </div>

      
      <template x-if="payload.is_exclusive && payload.custom_exclusive_benefits && payload.custom_exclusive_benefits.length > 0">
        <div>
           <span class="block text-sm font-semibold text-gray-700 mb-2">Benefit Eksklusif</span>
           <ul class="list-disc list-inside space-y-1 bg-purple-50 p-3 rounded border border-purple-100 text-sm text-gray-700">
             <template x-for="benefit in payload.custom_exclusive_benefits">
               <li x-text="benefit"></li>
             </template>
           </ul>
        </div>
      </template>

      
      <template x-if="payload.branches && payload.branches.length > 0">
        <div>
           <span class="block text-sm font-semibold text-gray-700 mb-2">Cabang / Rute (Branches)</span>
           <div class="border rounded-md overflow-hidden">
             <table class="min-w-full divide-y divide-gray-200">
               <thead class="bg-gray-100">
                 <tr>
                   <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Nama</th>
                   <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Durasi</th>
                   <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Rute (Asal → Tujuan)</th>
                 </tr>
               </thead>
               <tbody class="bg-white divide-y divide-gray-200">
                 <template x-for="branch in payload.branches">
                   <tr>
                     <td class="px-3 py-2 text-xs text-gray-900" x-text="branch.name"></td>
                     <td class="px-3 py-2 text-xs text-gray-500" x-text="branch.duration + ' menit'"></td>
                     <td class="px-3 py-2 text-xs text-gray-500">
                        <span x-text="branch.origin || '-'"></span> → <span x-text="branch.dest || '-'"></span>
                     </td>
                   </tr>
                 </template>
               </tbody>
             </table>
           </div>
        </div>
      </template>
    </div>

    <div class="mt-6 pt-4 border-t flex justify-end">
      <x-secondary-button type="button" @click="close()">Tutup</x-secondary-button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function productModal() {
    return {
      open: false,
      payload: {},
      init() {
        window.addEventListener('open-product-modal', (e) => {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close() {
        this.open = false;
        this.payload = {};
      }
    }
  }

  function openProductModal(data) {
    const evt = new CustomEvent('open-product-modal', { detail: data });
    window.dispatchEvent(evt);
  }
</script>
@endpush
@endsection
