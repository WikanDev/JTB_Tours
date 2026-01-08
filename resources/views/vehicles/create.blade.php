@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-2xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Tambah Kendaraan</h1>
    <x-secondary-button :href="route('vehicles.index')">Kembali</x-secondary-button>
  </div>

  <form action="{{ route('vehicles.store') }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <x-text-input name="brand" label="Brand" :value="old('brand')" required />
      </div>

      <div>
        <x-text-input name="type" label="Type" :value="old('type')" required />
      </div>

      <div>
        <x-text-input name="plate_number" label="Plate Number" :value="old('plate_number')" required />
      </div>

      <div>
        <x-text-input name="color" label="Color" :value="old('color')" />
      </div>

      <div>
        <x-text-input type="number" name="year" label="Year" :value="old('year')" min="1900" max="{{ date('Y')+1 }}" />
      </div>

      <div>
        <x-text-input type="number" name="capacity" label="Capacity" :value="old('capacity', 4)" required min="1" />
      </div>

      <div class="md:col-span-2">
        <x-select-input name="status" label="Status">
          <option value="available" @selected(old('status')=='available')>Available</option>
          <option value="in_use" @selected(old('status')=='in_use')>In Use</option>
          <option value="maintenance" @selected(old('status')=='maintenance')>Maintenance</option>
        </x-select-input>
      </div>
    </div>

    <div class="mt-4 flex space-x-2">
      <x-primary-button>Simpan</x-primary-button>
      <x-secondary-button :href="route('vehicles.index')">Batal</x-secondary-button>
    </div>
  </form>
</div>
@endsection
