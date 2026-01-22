@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-4xl mx-auto p-6" 
     x-data="orderEditForm({ 
        products: {{ $products->toJson() }}, 
        currentProductId: '{{ old('product_id', $order->product_id) }}',
        currentVehicleCount: '{{ old('vehicle_count', $order->vehicle_count ?? 1) }}',
        currentDuration: '{{ old('estimated_duration_minutes', $order->estimated_duration_minutes) }}',
        adults: {{ old('adults', $order->adults ?? 0) }},
        children: {{ old('children', $order->children ?? 0) }},
        babies: {{ old('babies', $order->babies ?? 0) }},
        totalPassengers: {{ old('passengers', $order->passengers ?? 1) }},
        currentVehicleIds: {{ $order->vehicles->pluck('id') }}
     })">
     
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Order {{ $order->customer_name }}</h1>
    <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Kembali</a>
  </div>

  <form action="{{ route('orders.update', $order->id) }}" method="POST" class="bg-white p-6 rounded-lg shadow-md space-y-6">
    @csrf
    @method('PUT')

    @if($errors->any())
      <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
        <ul class="list-disc pl-5">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Data Pelanggan</h3>
        <div>
           <x-text-input name="customer_name" label="Nama Customer" :value="old('customer_name', $order->customer_name)" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-text-input type="email" name="email" label="Email" :value="old('email', $order->email)" required />
            </div>
            <div>
                <x-text-input name="phone" label="Telepon" :value="old('phone', $order->phone)" required />
            </div>
        </div>
      </div>

      
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Layanan & Rute</h3>
        
        
        <div>
            <x-select-input name="product_id" label="Pilih Layanan / Product" x-model="productId" @change="handleProductChange()" required>
                <option value="">-- Pilih Product --</option>
                <template x-for="p in products" :key="p.id">
                    <option :value="p.id" x-text="p.name" :selected="p.id == productId"></option>
                </template>
            </x-select-input>
        </div>

        
        <div x-show="availableBranches.length > 0" x-transition class="bg-gray-50 p-3 rounded border border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Rute Perjalanan (Itinerary)</h4>
            <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                <template x-for="b in availableBranches" :key="b.id">
                    <li>
                         <span class="font-medium" x-text="b.name"></span>
                         <span class="text-gray-400 text-xs" x-text="'(' + formatDuration(b.duration_minutes) + ')'"></span>
                    </li>
                </template>
            </ul>
        </div>
        
        
        <div x-show="currentProduct && currentProduct.is_exclusive" x-transition class="bg-indigo-50 p-3 rounded border border-indigo-100 text-sm text-indigo-800">
            <strong>✨ Fasilitas Eksklusif:</strong>
            <ul class="list-disc pl-5 mt-1">
                <template x-if="currentProduct.snack"><li x-text="'Snack'"></li></template>
                <template x-if="currentProduct.water"><li x-text="'Air Mineral'"></li></template>
                <template x-if="currentProduct.magazine"><li x-text="'Majalah'"></li></template>
            </ul>
        </div>
      </div>

      
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Waktu & Lokasi</h3>
        
        <div>
           <x-text-input type="datetime-local" name="pickup_time" label="Waktu Penjemputan" x-model="pickupTime" @change="recalcArrival" required />
        </div>
        
        <div>
           <x-text-input type="datetime-local" name="arrival_time" label="Waktu Sampai (Opsional)" x-model="arrivalTime" @change="recalcDuration" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Durasi (Menit)</label>
            <div class="flex items-center">
                <x-text-input type="number" name="estimated_duration_minutes" x-model="duration" readonly class="w-full bg-gray-100 text-gray-600" />
                <span class="ml-2 text-sm text-gray-500 w-24" x-text="formatDuration(duration)"></span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Durasi otomatis dari Product.</p>
        </div>

        <div>
            <x-text-input name="pickup_location" label="Lokasi Jemput" :value="old('pickup_location', $order->pickup_location)" required />
        </div>
        <div>
            <x-text-input name="destination" label="Tujuan" :value="old('destination', $order->destination)" required />
        </div>
      </div>

      
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Penumpang & Kendaraan</h3>
        
        <div class="grid grid-cols-3 gap-3">
            <div>
                <x-text-input type="number" name="adults" label="Dewasa" x-model.number="adults" @input="updatePassengers" min="0" />
            </div>
            <div>
                <x-text-input type="number" name="children" label="Anak" x-model.number="children" @input="updatePassengers" min="0" />
            </div>
            <div>
                <x-text-input type="number" name="babies" label="Bayi" x-model.number="babies" @input="updatePassengers" min="0" />
            </div>
        </div>
        <input type="hidden" name="passengers" x-model="totalPassengers">

        
        <div class="border p-4 rounded bg-gray-50">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium text-gray-700">Pilih Kendaraan Available</label>
                <button type="button" @click="fetchVehicles" class="text-xs text-blue-600 hover:text-blue-800 underline">Refresh Availability</button>
            </div>
            
            <div x-show="loadingVehicles" class="text-sm text-gray-500 italic">Mencari kendaraan...</div>
            
            <div x-show="!loadingVehicles && availableVehicles.length === 0" class="text-sm text-red-500 italic">
                Tidak ada kendaraan available atau waktu belum diisi. (Pastikan waktu pick-up & durasi valid)
            </div>

            <div x-show="!loadingVehicles && availableVehicles.length > 0" class="grid grid-cols-1 sc-sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto">
                <template x-for="v in availableVehicles" :key="v.id">
                    <label class="flex items-center space-x-2 p-2 bg-white border rounded cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="vehicle_ids[]" :value="v.id" x-model="selectedVehicleIds" @change="updateVehicleCountFromSelection" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <div class="text-sm">
                            <span class="font-semibold" x-text="v.brand + ' ' + v.type"></span>
                            <span class="text-gray-500 text-xs" x-text="'(' + v.plate_number + ')'"></span>
                            <div class="text-xs text-gray-400" x-text="v.capacity + ' pax'"></div>
                        </div>
                    </label>
                </template>
            </div>
            
            <div class="mt-2 text-right text-sm text-gray-600">
                Total Mobil: <span x-text="selectedVehicleIds.length" class="font-bold"></span>
            </div>
            
            <div x-show="isOverCapacity" class="text-sm text-red-600 font-bold mt-2 text-right">
                Jumlah orang melebihi kapasitas mobil yang dipilih!
            </div>

            
            <input type="hidden" name="vehicle_count" x-model="vehicleCount">
        </div>

        <div>
            <x-textarea-input name="note" label="Catatan" rows="2" placeholder="Catatan...">{{ old('note', $order->note) }}</x-textarea-input>
        </div>
      </div>

    </div>

    <div class="flex justify-end pt-6 border-t">
      <x-primary-button ::disabled="isOverCapacity" ::class="{ 'opacity-50 cursor-not-allowed': isOverCapacity }">
          Simpan Perubahan
      </x-primary-button>
    </div>
  </form>
</div>

<script>
function orderEditForm(data) {
    return {
        products: data.products,
        
        productId: data.currentProductId,
        branchId: data.currentBranchId,
        pickupTime: '{{ old('pickup_time', $order->pickup_time ? $order->pickup_time->format('Y-m-d\TH:i') : '') }}',
        arrivalTime: '{{ old('arrival_time', $order->arrival_time ? $order->arrival_time->format('Y-m-d\TH:i') : '') }}',
        duration: data.currentDuration || 0,
        
        adults: data.adults,
        children: data.children,
        babies: data.babies,
        totalPassengers: data.totalPassengers,
        vehicleCount: data.currentVehicleCount,
        
        availableVehicles: [],
        selectedVehicleIds: data.currentVehicleIds || [],
        loadingVehicles: false,

        get currentProduct() {
            return this.products.find(p => p.id == this.productId) || null;
        },

        get availableBranches() {
            const p = this.currentProduct;
            return p && p.branches ? p.branches : [];
        },
        
        get currentBranch() { // Legacy
            if (!this.branchId) return null;
            return this.availableBranches.find(b => b.id == this.branchId) || null;
        },

        init() {
            this.updatePassengers();
            if (this.productId) this.handleProductChange(false);
            
            // Auto fetch if time set to populate list even if IDs exist
            if (this.pickupTime && this.duration) {
                this.fetchVehicles();
            }
        },

        async fetchVehicles() {
            if (!this.pickupTime || !this.duration) {
                this.availableVehicles = [];
                return;
            }

            this.loadingVehicles = true;
            try {
                const start = new Date(this.pickupTime);
                const durationMs = parseInt(this.duration) * 60000;
                const end = new Date(start.getTime() + durationMs);
                
                const format = (d) => d.getFullYear() + "-" + 
                    String(d.getMonth() + 1).padStart(2, '0') + "-" + 
                    String(d.getDate()).padStart(2, '0') + " " + 
                    String(d.getHours()).padStart(2, '0') + ":" + 
                    String(d.getMinutes()).padStart(2, '0') + ":00";

                const startStr = format(start);
                const endStr = format(end);
                
                const response = await fetch(`/vehicles/check-availability-list?start=${startStr}&end=${endStr}&ignore_order_id={{ $order->id }}&_t=${new Date().getTime()}`); 
                
                if (response.ok) {
                    const data = await response.json();
                    this.availableVehicles = data;
                } else {
                    this.availableVehicles = [];
                }
            } catch (error) {
                console.error('Error fetching vehicles:', error);
            } finally {
                this.loadingVehicles = false;
            }
        },

        updateVehicleCountFromSelection() {
             if (this.selectedVehicleIds.length > 0) {
                this.vehicleCount = this.selectedVehicleIds.length;
            } else {
                this.recalcVehicleCount();
            }
        },

        handleProductChange(resetBranch = true) {
            if (resetBranch) {
                this.branchId = '';
                this.duration = 0;
            }
            if (!this.duration && this.currentProduct) {
                // Fallback to legacy hour
                this.duration = Math.round(this.currentProduct.hour * 60);
            }
            this.recalcVehicleCount();
        },

        handleBranchChange() {
            if (this.currentBranch) {
                this.duration = this.currentBranch.duration_minutes;
                this.recalcArrival();
            }
        },

        updatePassengers() {
            this.totalPassengers = (parseInt(this.adults)||0) + (parseInt(this.children)||0) + (parseInt(this.babies)||0);
            this.recalcVehicleCount();
        },

        get selectedCapacity() {
            if (this.selectedVehicleIds.length === 0) return 0;
            return this.availableVehicles
                .filter(v => this.selectedVehicleIds.some(id => id == v.id)) // Loose comparison for string vs int
                .reduce((sum, v) => sum + v.capacity, 0);
        },

        get isOverCapacity() {
            if (this.selectedVehicleIds.length === 0) return false;
            return this.totalPassengers > this.selectedCapacity;
        },

        recalcVehicleCount() {
            // Default capability: product capacity or 4
            let cap = 4; 
            if (this.currentProduct && this.currentProduct.capacity) {
                cap = this.currentProduct.capacity;
            }
            this.vehicleCount = Math.max(1, Math.ceil(this.totalPassengers / cap));
        },

        recalcArrival() { // Recalculate Arrival based on Pickup + Duration
             if (this.pickupTime && this.duration) {
                const start = new Date(this.pickupTime);
                const durationMs = parseInt(this.duration) * 60000;
                const end = new Date(start.getTime() + durationMs);
                
                // Format to YYYY-MM-DDTHH:mm
                const year = end.getFullYear();
                const month = String(end.getMonth() + 1).padStart(2, '0');
                const day = String(end.getDate()).padStart(2, '0');
                const hours = String(end.getHours()).padStart(2, '0');
                const minutes = String(end.getMinutes()).padStart(2, '0');
                
                this.arrivalTime = `${year}-${month}-${day}T${hours}:${minutes}`;
             }
        },

        recalcDuration() {
            if (this.pickupTime && this.arrivalTime) {
                const start = new Date(this.pickupTime);
                const end = new Date(this.arrivalTime);
                if (end > start) {
                    const diffMs = end - start;
                    const diffMins = Math.round(diffMs / 60000);
                    this.duration = diffMins;
                }
            } else if (this.currentBranch) {
                this.duration = this.currentBranch.duration_minutes;
            }
        },
        
        formatDuration(mins) {
            if (!mins) return '-';
            const h = Math.floor(mins / 60);
            const m = mins % 60;
            return (h > 0 ? h + ' Jam ' : '') + (m > 0 ? m + ' Menit' : '');
        }
    }
}
</script>
@endsection