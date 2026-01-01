@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = $user ?? auth()->user();
@endphp

<div class="max-w-3xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold text-gray-900 mb-1">Profil Saya</h1>
  <p class="text-sm text-gray-500 mb-6">
    Kelola informasi akun dan data pribadi Anda.
  </p>

  

  
  @if($errors->any())
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
      <div class="font-semibold mb-1">Terjadi kesalahan:</div>
      <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white shadow rounded-lg p-6">
    <form
      action="{{ route('profile.update') }}"
      method="POST"
      enctype="multipart/form-data"
      class="space-y-6"
    >
      @csrf
      @method('PUT')

      
      <div class="flex items-start gap-4">
        <div>
          @if($user->profile_photo)
            <img
              src="{{ asset('storage/' . $user->profile_photo) }}"
              alt="Profile photo"
              class="h-16 w-16 rounded-full object-cover border border-gray-200"
            >
          @else
            <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-xl border border-gray-200">
              {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
          @endif
        </div>
        <div class="flex-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Foto Profil
          </label>
          <input
            type="file"
            name="profile_photo"
            accept="image/*"
            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
          <p class="mt-1 text-xs text-gray-500">
            Format gambar (JPG, PNG, WebP), maksimal 2MB.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
          <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $user->name) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm
                   focus:border-indigo-500 focus:ring-indigo-500"
            required
          >
        </div>

        
        <div>
          <label for="phone" class="block text-sm font-medium text-gray-700">No. Telepon</label>
          <input
            type="text"
            id="phone"
            name="phone"
            value="{{ old('phone', $user->phone) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm
                   focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="+62..."
          >
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
          <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email', $user->email) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm
                   focus:border-indigo-500 focus:ring-indigo-500"
            required
          >
        </div>

        
        <div>
          <label class="block text-sm font-medium text-gray-700">Role</label>
          <input
            type="text"
            value="{{ ucfirst(str_replace('_', ' ', $user->role)) }}"
            class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 text-sm"
            disabled
          >
        </div>
      </div>

      
      <div class="border-t border-gray-100 pt-4">
        <h2 class="text-sm font-semibold text-gray-800 mb-1">Ubah Password</h2>
        <p class="text-xs text-gray-500 mb-3">
          Kosongkan jika tidak ingin mengganti password.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
            <input
              type="password"
              id="password"
              name="password"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm
                     focus:border-indigo-500 focus:ring-indigo-500"
              autocomplete="new-password"
              placeholder="Minimal 8 karakter"
            >
          </div>

          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
            <input
              type="password"
              id="password_confirmation"
              name="password_confirmation"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm
                     focus:border-indigo-500 focus:ring-indigo-500"
              autocomplete="new-password"
            >
          </div>
        </div>
      </div>

      
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <a
          href="{{ url()->previous() }}"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm
                 text-gray-700 bg-white hover:bg-gray-50"
        >
          Batal
        </a>
        <button
          type="submit"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm
                 font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none
                 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Simpan Perubahan
        </button>
      </div>

    </form>
  </div>
</div>
@endsection
