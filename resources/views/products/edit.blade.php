@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-4xl mx-auto p-6" x-data="productEditForm()">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Product {{ $product->name }}</h1>
    <x-secondary-button :href="route('products.index')">Kembali</x-secondary-button>
  </div>

  <form action="{{ route('products.update', $product) }}" method="POST" class="bg-white p-6 rounded-lg shadow-md space-y-6">
    @csrf
    @method('PUT')

    
    @if($errors->any())
      <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
        <ul class="list-disc pl-5">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      
      <div class="space-y-4">
        <h3 class="text-lg font-semibold border-b pb-2">Informasi Dasar</h3>
        
        <div>
          <x-text-input name="name" label="Nama Product" :value="old('name', $product->name)" required />
        </div>

        <div>
           <x-text-input type="number" name="capacity" label="Kapasitas (Default)" :value="old('capacity', $product->capacity)" min="1" required />
        </div>

        <div>
          <x-textarea-input name="description" label="Deskripsi" rows="3">{{ old('description', $product->description) }}</x-textarea-input>
        </div>

        <div>
           <x-text-input type="number" name="hour" label="Durasi Dasar (Hour) - Legacy" :value="old('hour', $product->hour)" min="0" step="0.1" class="bg-gray-50 text-gray-500" />
        </div>
      </div>

      
      <div class="space-y-4 bg-gray-50 p-4 rounded-lg">
        <h3 class="text-lg font-semibold border-b pb-2">Fitur Eksklusif</h3>
        
        <div class="flex items-center space-x-3">
            <input type="checkbox" id="is_exclusive" name="is_exclusive" value="1" x-model="isExclusive"
                   class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <label for="is_exclusive" class="font-medium text-gray-700">Product Eksklusif?</label>
        </div>

        <div x-show="isExclusive" x-transition class="space-y-3 pl-8">
             <p class="text-sm text-gray-500 italic">Silakan isi detail fasilitas eksklusif secara manual di bawah.</p>

            
            <div class="pt-2 border-t mt-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Benefit Tambahan (Opsional)</label>
                <div class="space-y-2">
                    <template x-for="(benefit, index) in customBenefits" :key="index">
                        <div class="flex items-center space-x-2">
                            <input type="text" :name="`custom_exclusive_benefits[]`" x-model="customBenefits[index]" 
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                   placeholder="Masukan nama benefit...">
                            <button type="button" @click="removeBenefit(index)" class="text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addBenefit()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Benefit Lain
                </button>
            </div>
        </div>
      </div>
    </div>

    
    <div class="mt-8">
        <div class="flex items-center justify-between border-b pb-2 mb-4">
            <h3 class="text-lg font-semibold">Cabang / Rute Product</h3>
            <button type="button" @click="addBranch()" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Rute
            </button>
        </div>

        <div class="space-y-4">
            <template x-if="branches.length === 0">
                <div class="text-center py-8 text-gray-500 bg-gray-50 rounded border border-dashed text-sm">
                    Belum ada cabang/rute. Standard durasi akan menggunakan "Durasi Dasar".
                </div>
            </template>
            
            <template x-for="(branch, index) in branches" :key="index">
                
                <div x-show="!branch.deleted" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end bg-gray-50 p-3 rounded border">
                    <input type="hidden" :name="`branches[${index}][id]`" x-model="branch.id">
                    <input type="hidden" :name="`branches[${index}][_destruct]`" x-model="branch.deleted">
                    
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-600">Nama Rute</label>
                        <input type="text" :name="`branches[${index}][name]`" x-model="branch.name" 
                               :required="!branch.deleted"
                               class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600">Asal</label>
                        <input type="text" :name="`branches[${index}][origin_region]`" x-model="branch.origin"
                               class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                    </div>
                     <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600">Tujuan</label>
                        <input type="text" :name="`branches[${index}][destination_region]`" x-model="branch.destination"
                               class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600">Durasi (Menit)</label>
                        <input type="number" :name="`branches[${index}][duration_minutes]`" x-model="branch.duration" 
                               :required="!branch.deleted" min="1"
                               class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                    </div>

                    <div class="md:col-span-1 text-right">
                        <button type="button" @click="markDeleted(index)" class="text-red-600 hover:text-red-800 p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="pt-6 border-t flex justify-end space-x-3">
      <x-secondary-button :href="route('products.index')">Batal</x-secondary-button>
      <x-primary-button type="submit">Update Product</x-primary-button>
    </div>
  </form>
</div>

<script>
    function productEditForm() {
        return {
            isExclusive: {{ old('is_exclusive', $product->is_exclusive) ? 'true' : 'false' }},
            customBenefits: [
                @if(old('custom_exclusive_benefits'))
                    @foreach(old('custom_exclusive_benefits') as $cb)
                        '{{ addslashes($cb) }}',
                    @endforeach
                @elseif($product->custom_exclusive_benefits)
                    @foreach($product->custom_exclusive_benefits as $cb)
                        '{{ addslashes($cb) }}',
                    @endforeach
                @endif
            ],
            addBenefit() {
                this.customBenefits.push('');
            },
            removeBenefit(index) {
                this.customBenefits.splice(index, 1);
            },
            branches: [
                @if(old('branches'))
                    @foreach(old('branches') as $b)
                    {
                        id: '{{ $b['id'] ?? '' }}',
                        name: '{{ addslashes($b['name'] ?? '') }}',
                        origin: '{{ addslashes($b['origin_region'] ?? '') }}',
                        destination: '{{ addslashes($b['destination_region'] ?? '') }}',
                        duration: '{{ $b['duration_minutes'] ?? '' }}',
                        price: '{{ $b['price'] ?? '' }}',
                        deleted: {{ isset($b['_destruct']) && $b['_destruct'] ? 'true' : 'false' }}
                    },
                    @endforeach
                @else
                    @foreach($product->branches as $b)
                    {
                        id: '{{ $b->id }}',
                        name: '{{ addslashes($b->name) }}',
                        origin: '{{ addslashes($b->origin_region) }}',
                        destination: '{{ addslashes($b->destination_region) }}',
                        duration: '{{ $b->duration_minutes }}',
                        price: '{{ $b->price }}',
                        deleted: false
                    },
                    @endforeach
                @endif
            ],
            addBranch() {
                this.branches.push({
                    id: '', name: '', origin: '', destination: '', duration: '', price: '', deleted: false
                });
            },
            markDeleted(index) {
                if (this.branches[index].id) {
                    this.branches[index].deleted = true;
                } else {
                    this.branches.splice(index, 1);
                }
            }
        }
    }
</script>
@endsection
