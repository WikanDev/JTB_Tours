@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-2xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Tambah User</h1>
    <x-secondary-button :href="route('users.index')">Kembali</x-secondary-button>
  </div>

  <form action="{{ route('users.store') }}" method="POST" class="bg-white p-4 rounded shadow" x-data="{ selectedRole: '{{ old('role', 'driver') }}' }">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <x-text-input name="name" label="Nama" :value="old('name')" required />
      </div>

      <div>
        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
          Role <span class="text-red-500">*</span>
        </label>
        <select 
          name="role" 
          id="role" 
          required 
          x-model="selectedRole"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        >
          @foreach($roles as $r)
            <option value="{{ $r }}" @selected(old('role')==$r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
          @endforeach
        </select>
        @error('role')
          <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <x-text-input type="email" name="email" label="Email" :value="old('email')" required />
      </div>

      <div>
        <x-text-input name="phone" label="Phone" :value="old('phone')" />
      </div>

      <div>
        <x-text-input type="date" name="join_date" label="Join Date" :value="old('join_date')" />
      </div>

      <div>
        <x-text-input type="password" name="password" label="Password" required />
      </div>

      <div>
        <x-text-input type="password" name="password_confirmation" label="Confirm Password" required />
      </div>

      {{-- Hanya tampilkan untuk driver/guide --}}
      <div x-show="selectedRole === 'driver' || selectedRole === 'guide'" x-transition>
        <x-text-input type="number" name="monthly_work_limit" label="Monthly Work Limit (jam)" :value="old('monthly_work_limit', '200')" min="0" />
        <p class="text-xs text-gray-500 mt-1">Default: 200 jam/bulan</p>
      </div>
    </div>

    <div class="mt-4 flex space-x-2">
      <x-primary-button>Simpan</x-primary-button>
      <x-secondary-button :href="route('users.index')">Batal</x-secondary-button>
    </div>
  </form>
</div>
@endsection
