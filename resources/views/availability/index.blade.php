
@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-7xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Availability</h1>
      <div class="text-sm text-gray-500">Kelola status online/offline driver & guide.</div>
    </div>

    <div class="flex items-center gap-2">
      @if(auth()->check() && in_array(auth()->user()->role, ['driver','guide']))
        
        <form id="self-toggle-form" action="{{ route('availability.toggle') }}" method="POST">
          @csrf
          <button type="submit" class="px-3 py-2 rounded {{ auth()->user()->status === 'online' ? 'bg-red-600 text-white' : 'bg-green-600 text-white' }}">
            {{ auth()->user()->status === 'online' ? 'Go Offline' : 'Go Online' }}
          </button>
        </form>
      @endif

      
      <a href="{{ route('work-schedules.index') }}" class="px-3 py-2 bg-gray-100 rounded">Work Schedules</a>
    </div>
  </div>

  
  @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin','staff']))
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
      <div>
        <label class="text-xs text-gray-500">Cari nama / email</label>
        <input name="q" value="{{ request('q') }}" class="mt-1 block w-full rounded border-gray-200 px-3 py-2" placeholder="cari..." />
      </div>

      <div>
        <label class="text-xs text-gray-500">Role</label>
        <select name="role" class="mt-1 block w-full rounded border-gray-200 px-3 py-2">
          <option value="">Semua</option>
          <option value="driver" @if(request('role')=='driver') selected @endif>Driver</option>
          <option value="guide" @if(request('role')=='guide') selected @endif>Guide</option>
        </select>
      </div>

      <div>
        <label class="text-xs text-gray-500">Status</label>
        <select name="status" class="mt-1 block w-full rounded border-gray-200 px-3 py-2">
          <option value="">Semua</option>
          <option value="online" @if(request('status')=='online') selected @endif>Online</option>
          <option value="offline" @if(request('status')=='offline') selected @endif>Offline</option>
        </select>
      </div>

      <div class="flex items-end">
        <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
        <a href="{{ route('availability.index') }}" class="ml-2 px-3 py-2 bg-gray-200 rounded">Reset</a>
      </div>
    </form>
  @endif

  
  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">ID</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Name</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Role</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Phone / Email</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Work Hours (used/total)</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100">
        @forelse($users as $u)
          <tr>
            <td class="px-4 py-3 text-sm">{{ $u->id }}</td>
            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $u->name }}</div>
              <div class="text-xs text-gray-500">{{ $u->join_date ?? '' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ ucfirst($u->role) }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ $u->phone ?? '-' }} · {{ $u->email ?? '-' }}</td>
            <td class="px-4 py-3 text-sm">
              @if($u->status === 'online')
                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-green-100 text-green-800">Online</span>
              @else
                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">Offline</span>
              @endif
            </td>
            <td class="px-4 py-3 text-sm">
              @php
                $ws = $schedules[$u->id] ?? null;
                $total = $ws ? $ws->total_hours : ($u->monthly_work_limit ?? null);
                $used = $ws ? $ws->used_hours : ($u->used_hours ?? 0);
              @endphp
              <div class="text-sm">
                {{ $used ?? 0 }} / {{ $total ?? '—' }}
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-right">
              
              @if(auth()->check() && in_array(auth()->user()->role, ['super_admin','admin','staff']))
                
                <form action="{{ route('availability.force', $u) }}" method="POST" class="inline-flex items-center" onsubmit="return confirm('Set status untuk {{ $u->name }}?')">
                  @csrf
                  <select name="status" class="px-2 py-1 rounded border-gray-200 text-sm">
                    <option value="online" @if($u->status=='online') selected @endif>Online</option>
                    <option value="offline" @if($u->status=='offline') selected @endif>Offline</option>
                  </select>
                  <button type="submit" class="ml-2 px-2 py-1 bg-blue-600 text-white rounded text-sm">Set</button>
                </form>

                
                <button @click="showDetail({{ $u->toJson() }})" class="ml-2 px-2 py-1 bg-gray-200 rounded text-sm">Detail</button>
              @elseif(auth()->check() && auth()->user()->id === $u->id)
                
                <form action="{{ route('availability.toggle') }}" method="POST" class="inline-block">
                  @csrf
                  <button type="submit" class="px-3 py-1 rounded text-sm {{ $u->status === 'online' ? 'bg-red-600 text-white' : 'bg-green-600 text-white' }}">
                    {{ $u->status === 'online' ? 'Go Offline' : 'Go Online' }}
                  </button>
                </form>

                <button @click="showDetail({{ $u->toJson() }})" class="ml-2 px-2 py-1 bg-gray-200 rounded text-sm">Detail</button>
              @else
                <button @click="showDetail({{ $u->toJson() }})" class="px-2 py-1 bg-gray-200 rounded text-sm">Detail</button>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="p-4 text-center text-gray-500">Tidak ada user yang sesuai filter.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $users->links() }}
    </div>
  </div>
</div>

<div x-data="availabilityModal()" x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded shadow-lg max-w-lg w-full p-4 z-50">
    <div class="flex items-start justify-between">
      <h3 class="text-lg font-semibold">User Detail</h3>
      <button @click="close()" class="text-gray-500">✕</button>
    </div>

    <div class="mt-3 space-y-2 text-sm">
      <div><strong>Name:</strong> <span x-text="user.name"></span></div>
      <div><strong>Role:</strong> <span x-text="user.role"></span></div>
      <div><strong>Email:</strong> <span x-text="user.email || '-'"></span></div>
      <div><strong>Phone:</strong> <span x-text="user.phone || '-'"></span></div>
      <div><strong>Status:</strong> <span x-text="user.status"></span></div>
      <div><strong>Join Date:</strong> <span x-text="user.join_date || '-'"></span></div>
    </div>

    <div class="mt-4 text-right">
      <button @click="close()" class="px-3 py-2 bg-gray-200 rounded">Close</button>
      <template x-if="user.role && (user.role === 'driver' || user.role === 'guide')">
        <a :href="`/work-schedules?user_id=${user.id}`" class="ml-2 px-3 py-2 bg-gray-800 text-white rounded">View Schedule</a>
      </template>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function availabilityModal(){
    return {
      open: false,
      user: {},
      init(){},
      show(u){
        // u may be a JS object or JSON string
        this.user = (typeof u === 'string') ? JSON.parse(u) : u;
        this.open = true;
      },
      close(){ this.open = false; this.user = {}; }
    }
  }

  function showDetail(payload){
    // payload expected as JSON object (Blade passed with toJson())
    const modal = document.querySelector('[x-data="availabilityModal()"]');
    // dispatch event for Alpine instance
    window.dispatchEvent(new CustomEvent('alpine:init', { detail: payload }));
    // simpler: find the Alpine component by selecting closest element and calling its data
    // but easiest is to use a global event
    window.dispatchEvent(new CustomEvent('open-availability-detail', { detail: payload }));
  }

  // listen and open modal
  window.addEventListener('open-availability-detail', (e) => {
    // find alpine component root and call show
    const root = document.querySelector('[x-data="availabilityModal()"]');
    if (!root) return;
    // use Alpine to access component
    if (window.Alpine && Alpine.reactive) {
      // trigger by setting inner event; simplest: call the show attribute via dispatch on window and let Alpine init handle it
    }
    // fallback: set inner html with custom event to Alpine component using global function
    try {
      const data = e.detail;
      // find the Alpine component instance via closest element and set properties using DOM
      // A simple approach is to programmatically open modal by setting global variable and toggling x-show via attribute
      // But in this file, we can trigger a custom event that the Alpine component listens to. Let's implement that:
      const evt = new CustomEvent('open-availability-modal', { detail: data });
      window.dispatchEvent(evt);
    } catch (err) {
      console.error(err);
    }
  });

  // Alpine component listens to global event to open
  document.addEventListener('alpine:init', () => {
    Alpine.data('availabilityModal', () => ({
      open: false,
      user: {},
      init(){
        window.addEventListener('open-availability-modal', (ev) => {
          this.user = ev.detail;
          this.open = true;
        });
      },
      show(u){ this.user = u; this.open = true; },
      close(){ this.open = false; this.user = {}; }
    }));
  });
</script>
@endpush

@endsection
