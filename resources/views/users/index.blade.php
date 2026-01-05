@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Users</h1>

    <div class="flex items-center space-x-2">
      <a href="{{ route('users.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Tambah User</a>
    </div>
  </div>

  
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
    <div>
      <label class="block text-xs text-gray-600">Role</label>
      <select name="role" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        @foreach($roles as $r)
          <option value="{{ $r }}" @if(request('role') == $r) selected @endif>{{ ucfirst(str_replace('_',' ', $r)) }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs text-gray-600">Cari</label>
      <input name="search" value="{{ request('search') }}" placeholder="nama / email / telepon" class="mt-1 block w-full rounded border-gray-200" />
    </div>

    <div class="col-span-2 flex items-end gap-2">
      <x-primary-button>Filter</x-primary-button>
      <x-secondary-button :href="route('users.index')">Reset</x-secondary-button>
    </div>
  </form>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">ID</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Nama</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Email / Phone</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Role</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Jam Bulanan</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($users as $u)
          @php
            // Siapkan payload aman untuk modal
            $payload = [
              'id' => $u->id,
              'name' => $u->name,
              'email' => $u->email,
              'phone' => $u->phone,
              'role' => $u->role,
              'join_date' => $u->join_date ? \Carbon\Carbon::parse($u->join_date)->format('d M Y') : '-',
              'monthly_work_limit' => $u->monthly_work_limit,
              'used_hours' => $u->used_hours,
            ];
            $payload_b64 = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
          @endphp

          <tr>
            <td class="px-4 py-3 text-sm">{{ $u->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $u->name }}</div>
              <div class="text-xs text-gray-500">Join: {{ $u->join_date ? \Carbon\Carbon::parse($u->join_date)->format('d M Y') : '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">
              <div>{{ $u->email ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $u->phone ?? '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ ucfirst(str_replace('_',' ', $u->role)) }}</td>
            <td class="px-4 py-3 text-sm">
              @if(in_array($u->role, ['driver','guide']))
                {{ $u->used_hours ?? 0 }} / {{ $u->monthly_work_limit ?? '—' }}
              @else
                -
              @endif
            </td>
            <td class="px-4 py-3 text-sm text-right">
              
              <button
                type="button"
                class="inline-flex items-center px-2 py-1 bg-indigo-600 text-white rounded text-xs"
                data-payload-b64="{{ $payload_b64 }}"
                onclick="openUserModal(this)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Detail
              </button>

              <x-edit-button :href="route('users.edit', $u)">Edit</x-edit-button>

              <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus user ini?')">
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
          <tr><td colspan="6" class="p-4 text-center text-gray-500">Belum ada user.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $users->links() }}
    </div>
  </div>
</div>

<div x-data="userModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded-lg shadow-xl max-w-xl w-full p-6 z-50 transform transition-all">
    <div class="flex items-start justify-between border-b pb-3 mb-4">
      <h3 class="text-xl font-bold text-gray-900">User #<span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="space-y-4">
      
      <div class="bg-blue-50 p-4 rounded border border-blue-100 mb-2">
         <span class="block text-xs font-semibold text-blue-600 uppercase">User Info</span>
         <div class="flex flex-col">
            <span class="text-xl font-bold text-blue-900" x-text="payload.name"></span>
            <span class="text-base text-blue-700 font-medium capitalize" x-text="payload.role ? payload.role.replace('_', ' ') : '-'"></span>
         </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Email</span>
            <span class="text-sm font-medium text-gray-900 wrap-break-word" x-text="payload.email || '-'"></span>
         </div>
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Phone</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.phone || '-'"></span>
         </div>
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Join Date</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.join_date || '-'"></span>
         </div>
         
         <div class="bg-gray-50 p-3 rounded" x-show="payload.role === 'driver' || payload.role === 'guide'">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Jam Kerja Bulan Ini</span>
            <div class="flex items-baseline space-x-1">
               <span class="text-lg font-bold text-gray-900" x-text="payload.used_hours || 0"></span>
               <span class="text-sm text-gray-500">/</span>
               <span class="text-sm text-gray-500" x-text="payload.monthly_work_limit || '-'"></span>
               <span class="text-xs text-gray-400">Jam</span>
            </div>
         </div>
      </div>
    </div>

    <div class="mt-6 pt-4 border-t flex justify-end space-x-3">
      <a :href="'/users/' + payload.id + '/edit'" class="px-4 py-2 bg-yellow-400 text-white rounded shadow hover:bg-yellow-500 transition-colors font-medium">Edit</a>
      <button @click="close()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded shadow hover:bg-gray-300 transition-colors font-medium">Tutup</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function userModal() {
    return {
      open: false,
      payload: {},
      init() {
        window.addEventListener('open-user-modal', (e) => {
          this.payload = e.detail || {};
          this.open = true;
        });
      },
      close() { this.open = false; this.payload = {}; }
    }
  }

  // Terima element tombol, baca data-payload-b64, decode lalu dispatch event
  function openUserModal(el) {
    try {
      const raw = el.getAttribute('data-payload-b64');
      if (!raw) return;
      const json = atob(raw);
      const payload = JSON.parse(json);
      window.dispatchEvent(new CustomEvent('open-user-modal', { detail: payload }));
    } catch (err) {
      console.error('openUserModal error', err);
    }
  }
</script>
@endpush

@endsection
