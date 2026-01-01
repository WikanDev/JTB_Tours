@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-2xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Edit User {{ $user->name }}</h1>
    <x-secondary-button :href="route('users.index')">Kembali</x-secondary-button>
  </div>

  <form action="{{ route('users.update', $user) }}" method="POST" class="bg-white p-4 rounded shadow">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <x-text-input name="name" label="Nama" :value="old('name', $user->name)" required />
      </div>

      <div>
        <x-select-input name="role" label="Role" required>
            @foreach($roles as $r)
                <option value="{{ $r }}" @selected(old('role', $user->role)==$r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
            @endforeach
        </x-select-input>
      </div>

      <div>
        <x-text-input type="email" name="email" label="Email" :value="old('email', $user->email)" required />
      </div>

      <div>
        <x-text-input name="phone" label="Phone" :value="old('phone', $user->phone)" />
      </div>

      <div>
        <x-text-input type="date" name="join_date" label="Join Date" :value="old('join_date', $user->join_date ? \Carbon\Carbon::parse($user->join_date)->format('Y-m-d') : '')" />
      </div>

      <div>
        <x-text-input type="password" name="password" label="Password (kosongkan jika tidak diubah)" />
      </div>

      <div>
        <x-text-input type="password" name="password_confirmation" label="Confirm Password" />
      </div>

      <div>
        <x-text-input type="number" name="monthly_work_limit" label="Monthly Work Limit (jam, untuk driver/guide)" :value="old('monthly_work_limit', $user->monthly_work_limit)" min="0" />
      </div>
    </div>

    <div class="mt-4 flex space-x-2">
      <x-primary-button>Update</x-primary-button>
      <x-secondary-button :href="route('users.index')">Batal</x-secondary-button>
    </div>
  </form>
</div>
@endsection
