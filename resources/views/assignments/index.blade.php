@extends('layouts.app')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Assignments</h1>
    <div class="space-x-2">
      <a href="{{ route('assignments.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Buat Assignment</a>
    </div>
  </div>

  
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
    <div>
      <label class="block text-sm">Status</label>
      <select name="status" class="mt-1 block w-full rounded border-gray-200">
        <option value="">Semua</option>
        <option value="pending" @if(request('status')=='pending') selected @endif>Pending</option>
        <option value="accepted" @if(request('status')=='accepted') selected @endif>Accepted</option>
        <option value="in_progress" @if(request('status')=='in_progress') selected @endif>In Progress</option>
        <option value="declined" @if(request('status')=='declined') selected @endif>Declined</option>
        <option value="completed" @if(request('status')=='completed') selected @endif>Completed</option>
      </select>
    </div>

    <div>
      <label class="block text-sm">Driver</label>
      <x-text-input name="driver_id" value="{{ request('driver_id') }}" placeholder="driver id" />
    </div>

    <div>
      <label class="block text-sm">From</label>
      <x-text-input name="from" type="date" value="{{ request('from') }}" />
    </div>

    <div class="flex items-end">
      <button type="submit" class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
      <a href="{{ route('assignments.index') }}" class="ml-2 px-3 py-2 bg-gray-200 rounded">Reset</a>
    </div>
  </form>

  
  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium">No</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Order</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Driver / Guide</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Assigned At</th>
          <th class="px-4 py-2 text-left text-sm font-medium">Status</th>
          <th class="px-4 py-2 text-right text-sm font-medium">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($assignments as $a)
          
          @php
            $modalPayload = [
              'id' => $a->id,
              'order' => [
                'customer' => $a->order->customer_name ?? '-',
                'pickup' => $a->order->pickup_time ? \Carbon\Carbon::parse($a->order->pickup_time)->format('d M Y H:i') : '-',
                'from' => $a->order->pickup_location ?? '-',
                'to' => $a->order->destination ?? '-',
                'product' => $a->order->product?->name ?? '-'
              ],
              'driver' => $a->driver ? [
                'id' => $a->driver->id,
                'name' => $a->driver->name,
                'phone' => $a->driver->phone ?? null,
              ] : null,
              'guide' => $a->guide ? [
                'id' => $a->guide->id,
                'name' => $a->guide->name,
                'phone' => $a->guide->phone ?? null,
              ] : null,
              'status' => $a->status,
              'note' => $a->note,
            ];
          @endphp

          <tr>
            
            <td class="px-4 py-3 text-sm">
              {{ $assignments->firstItem() ? $assignments->firstItem() + $loop->index : $loop->iteration }}
            </td>

            <td class="px-4 py-3 text-sm">
              <div class="font-medium">{{ $a->order->customer_name ?? '—' }}</div>
              <div class="text-xs text-gray-500">{{ $a->order->pickup_time ? \Carbon\Carbon::parse($a->order->pickup_time)->format('d M Y H:i') : '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">
              <div>{{ $a->driver->name ?? '-' }}</div>
              <div class="text-xs text-gray-500">{{ $a->guide->name ?? '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm">{{ $a->assigned_at ? \Carbon\Carbon::parse($a->assigned_at)->format('d M Y H:i') : '-' }}</td>
            <td class="px-4 py-3 text-sm">
              @php
                // safe badge mapping
                $badge = match($a->status) {
                  'pending' => 'bg-yellow-100 text-yellow-800',
                  'accepted' => 'bg-green-100 text-green-800',
                  'in_progress' => 'bg-blue-50 text-blue-600 border border-blue-200',
                  'declined' => 'bg-red-100 text-red-800',
                  'completed'=> 'bg-blue-100 text-blue-800',
                  default => 'bg-gray-100 text-gray-800'
                };
              @endphp
              <span class="px-2 py-1 rounded text-xs whitespace-nowrap {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $a->status ?? '—')) }}</span>
            </td>
            <td class="px-4 py-3 text-sm text-right">
              
              <button
                onclick='openAssignmentModal(@json($modalPayload))'
                class="inline-flex items-center px-2 py-1 bg-indigo-600 text-white rounded text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>  
                Detail
              </button>

              <x-edit-button :href="route('assignments.edit', $a)">Edit</x-edit-button>

              <form action="{{ route('assignments.destroy', $a) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus assignment?')">
                @csrf @method('DELETE')
                <button class="inline-flex items-center px-2 py-1 ml-2 bg-red-600 text-white rounded text-xs">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Hapus
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-4 text-center text-sm text-gray-500">Belum ada assignment.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $assignments->links() }}
    </div>
  </div>
</div>

<div x-data="assignmentModal()" x-init="init()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="fixed inset-0 bg-black/40" @click="close()"></div>
  <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 z-50 transform transition-all overflow-y-auto max-h-[90vh]">
    <div class="flex items-start justify-between border-b pb-3 mb-4">
      <h3 class="text-xl font-bold text-gray-900">Assignment #<span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="space-y-4">
      
      <div class="bg-blue-50 p-4 rounded border border-blue-100 mb-2">
         <span class="block text-xs font-semibold text-blue-600 uppercase">Customer Info</span>
         <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <span class="text-lg font-bold text-blue-900" x-text="payload.order.customer"></span>
            <span class="text-base text-blue-800 font-medium" x-text="payload.order.product"></span>
         </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
         
         <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Pickup Time</span>
            <span class="text-base font-medium text-gray-900" x-text="payload.order.pickup"></span>
         </div>
         <div class="bg-gray-50 p-3 rounded">
             <span class="block text-xs font-semibold text-gray-500 uppercase">Status Assignment</span>
             <span class="inline-block px-2 py-0.5 rounded text-sm font-medium uppercase" 
                   :class="{
                     'bg-yellow-100 text-yellow-800': payload.status === 'pending',
                     'bg-green-100 text-green-800': payload.status === 'accepted',
                     'bg-blue-50 text-blue-600 border border-blue-200': payload.status === 'in_progress',
                     'bg-blue-100 text-blue-800': payload.status === 'completed',
                     'bg-red-100 text-red-800': payload.status === 'declined'
                   }"
                   x-text="payload.status ? payload.status.replace('_',' ') : '-'"></span>
         </div>
      </div>

      <div class="bg-gray-50 p-3 rounded border border-gray-100">
         <span class="block text-xs font-semibold text-gray-500 uppercase mb-1">Rute Perjalanan</span>
         <div class="flex items-center text-sm font-medium text-gray-900">
            <span x-text="payload.order.from"></span>
            <span class="mx-2 text-gray-400">→</span>
            <span x-text="payload.order.to"></span>
         </div>
      </div>

       
       <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
         <div>
            <span class="block text-sm font-semibold text-gray-700">Driver</span>
            <div class="mt-1 p-3 bg-gray-50 rounded border border-gray-100">
               <div class="font-bold text-gray-900" x-text="payload.driver ? payload.driver.name : '-'"></div>
               <div class="text-xs text-gray-500" x-show="payload.driver && payload.driver.phone" x-text="payload.driver?.phone"></div>
            </div>
         </div>
         <div>
            <span class="block text-sm font-semibold text-gray-700">Guide</span>
            <div class="mt-1 p-3 bg-gray-50 rounded border border-gray-100">
               <div class="font-bold text-gray-900" x-text="payload.guide ? payload.guide.name : '-'"></div>
               <div class="text-xs text-gray-500" x-show="payload.guide && payload.guide.phone" x-text="payload.guide?.phone"></div>
            </div>
         </div>
       </div>

       
       <div x-show="payload.note && payload.note !== '-'">
         <span class="block text-sm font-semibold text-gray-700 mb-1">Catatan (Note)</span>
         <div class="p-3 bg-yellow-50 rounded text-sm text-gray-800 border border-yellow-100 italic" x-text="payload.note"></div>
       </div>
    </div>

    <div class="mt-6 pt-4 border-t flex justify-end">
      <button @click="close()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded shadow hover:bg-gray-300 transition-colors font-medium">Tutup</button>
    </div>
  </div>
</div>

<script>
  function openAssignmentModal(payload) {
    const event = new CustomEvent('open-assignment-modal', { detail: payload });
    window.dispatchEvent(event);
  }

  function assignmentModal() {
    return {
      open: false,
      payload: {},
      currentUserId: {{ auth()->check() ? auth()->id() : 'null' }},
      currentUserRole: "{{ auth()->check() ? auth()->user()->role : '' }}",
      init() {
        window.addEventListener('open-assignment-modal', (e) => {
          this.payload = e.detail;
          this.open = true;
        });
      },
      close() {
        this.open = false;
        this.payload = {};
      },
      isCurrentPerformer() {
        if (!this.currentUserId) return false;
        // check if current user is driver or guide for this payload
        if (this.currentUserRole === 'driver' && this.payload.driver && this.payload.driver.id == this.currentUserId) return true;
        if (this.currentUserRole === 'guide' && this.payload.guide && this.payload.guide.id == this.currentUserId) return true;
        return false;
      },
      changeStatusUrl(status) {
        // returns url like /assignments/{id}/status
        return `/assignments/${this.payload.id}/status`;
      },
      submitForm(form) {
        // simple confirmation
        if (!confirm('Yakin?')) return;
        form.submit();
      }
    }
  }
</script>

@endsection
