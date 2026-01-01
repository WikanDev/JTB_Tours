
@extends('layouts.app')

@section('title', 'Tugas Saya')

@section('content')
@include('partials.flash-and-modal')

<div class="max-w-4xl mx-auto p-4">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Tugas Saya</h1>
  </div>

  @php
    // Kelompokkan assignment berdasarkan status
    $pending = $assignments->where('status', 'pending');
    $accepted = $assignments->where('status', 'accepted');
    $running  = $assignments->where('status', 'in_progress');
    $history = $assignments->whereIn('status', ['completed', 'declined']);
  @endphp

  
  <div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Tugas Menunggu</h2>
      <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">
        {{ $pending->count() }}
      </span>
    </div>

    @if($pending->isNotEmpty())
      <div class="space-y-3">
        @foreach($pending as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => true])
        @endforeach
      </div>
    @else
      <div class="text-sm text-gray-500 italic">Tidak ada tugas menunggu.</div>
    @endif
  </div>

  
  <div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Tugas Berjalan</h2>
      <span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-0.5 rounded-full">
        {{ $running->count() }}
      </span>
    </div>

    @if($running->isNotEmpty())
      <div class="space-y-3">
        @foreach($running as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => true])
        @endforeach
      </div>
    @else
      <div class="text-sm text-gray-500 italic">Tidak ada tugas yang sedang berjalan.</div>
    @endif
  </div>

  
  <div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Tugas Diterima (Accepted)</h2>
      <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full">
        {{ $accepted->count() }}
      </span>
    </div>

    @if($accepted->isNotEmpty())
      <div class="space-y-3">
        @foreach($accepted as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => true])
        @endforeach
      </div>
    @else
      <div class="text-sm text-gray-500 italic">Tidak ada tugas yang diterima (menunggu dimulai).</div>
    @endif
  </div>

  
  <div>
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-lg font-medium text-gray-800">Riwayat Tugas</h2>
      <span class="bg-gray-100 text-gray-800 text-xs px-2 py-0.5 rounded-full">
        {{ $history->count() }}
      </span>
    </div>

    @if($history->isNotEmpty())
      <div class="space-y-3">
        @foreach($history as $a)
          @include('assignments._assignment-card', ['a' => $a, 'showActions' => false])
        @endforeach
      </div>

      
      @if($history->count() > 10)
        <div class="mt-4 text-center">
          <button type="button"
            class="text-sm text-blue-600 hover:underline"
            onclick="alert('Fitur riwayat lengkap akan dikembangkan di versi berikutnya.')">
            Lihat lebih banyak riwayat →
          </button>
        </div>
      @endif
    @else
      <div class="text-sm text-gray-500 italic">Belum ada riwayat tugas.</div>
    @endif
  </div>
</div>


<div
  x-data="assignmentModal()"
  x-init="init()"
  x-show="open"
  x-cloak
  class="fixed inset-0 z-50 flex items-center justify-center p-4"
  style="display: none;"
>
  <div class="absolute inset-0 bg-black/40" @click="close()" aria-hidden="true"></div>
  <div
    class="relative bg-white rounded shadow-lg max-w-2xl w-full z-50 p-4"
    x-transition
    @keydown.escape.window="close()"
    role="dialog"
    aria-modal="true"
    aria-label="Detail Assignment"
  >
    <div class="flex items-start justify-between border-b pb-3 mb-4">
      <h3 class="text-xl font-bold text-gray-900">Assignment #<span x-text="payload.id"></span></h3>
      <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>

    <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Customer</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.order && payload.order.customer ? payload.order.customer : '-'"></span>
          </div>
          <div class="bg-gray-50 p-3 rounded">
            <span class="block text-xs font-semibold text-gray-500 uppercase">Product</span>
            <span class="text-lg font-medium text-gray-900" x-text="payload.order && payload.order.product ? payload.order.product : '-'"></span>
          </div>
          <div class="bg-gray-50 p-3 rounded">
             <span class="block text-xs font-semibold text-gray-500 uppercase">Pickup Time</span>
             <span class="text-lg font-medium text-gray-900" x-text="payload.order && payload.order.pickup ? payload.order.pickup : '-'"></span>
          </div>
          <div class="bg-gray-50 p-3 rounded">
             <span class="block text-xs font-semibold text-gray-500 uppercase">Status</span>
             <span class="text-lg font-medium uppercase whitespace-nowrap" 
                   :class="{
                     'text-yellow-700': payload.status === 'pending',
                     'text-green-700': payload.status === 'accepted',
                     'text-indigo-700': payload.status === 'in_progress',
                     'text-blue-700': payload.status === 'completed',
                     'text-red-700': payload.status === 'declined'
                   }" 
                   x-text="payload.status ? payload.status.replace('_', ' ') : '-'"></span>
          </div>
      </div>

      
      <div class="bg-blue-50 p-3 rounded border border-blue-100">
         <span class="block text-xs font-semibold text-blue-600 uppercase mb-1">Rute Perjalanan</span>
         <div class="flex items-center text-sm text-gray-900 font-medium">
            <span x-text="payload.order && payload.order.from ? payload.order.from : '-'"></span>
            <span class="mx-2 text-gray-400">→</span>
            <span x-text="payload.order && payload.order.to ? payload.order.to : '-'"></span>
         </div>
      </div>

       
       <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
         <div>
            <span class="block text-sm font-semibold text-gray-700">Driver</span>
            <div class="mt-1 p-2 bg-gray-50 rounded text-sm text-gray-800" x-text="payload.driver && payload.driver.name ? payload.driver.name : '-'"></div>
         </div>
         <div>
            <span class="block text-sm font-semibold text-gray-700">Guide</span>
            <div class="mt-1 p-2 bg-gray-50 rounded text-sm text-gray-800" x-text="payload.guide && payload.guide.name ? payload.guide.name : '-'"></div>
         </div>
       </div>

      
      <div x-show="payload.note">
         <span class="block text-sm font-semibold text-gray-700 mb-1">Catatan (Note)</span>
         <div class="p-3 bg-yellow-50 rounded text-sm text-gray-800 border border-yellow-100 italic" x-text="payload.note"></div>
      </div>
    </div>

    <div class="mt-6 pt-4 border-t flex flex-col sm:flex-row items-center justify-between gap-3">
       
      <div class="w-full sm:w-auto flex items-center gap-2">
      @auth
      <template x-if="isCurrentPerformer()">
        <div class="flex items-center space-x-2 w-full">
            
          
          <template x-if="payload.status === 'pending'">
             <div class="flex space-x-2 w-full">
                <form x-bind:action="changeStatusUrl('accepted')" method="POST" x-ref="formAccept" class="inline-block">
                  <input type="hidden" name="_token" value="{{ csrf_token() }}">
                  <input type="hidden" name="status" value="accepted">
                  <button type="button" @click="confirmAndSubmit($refs.formAccept, 'Terima tugas ini?')" class="px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-700 transition-colors text-sm font-medium">Terima</button>
                </form>
                
                <button type="button" @click="showRejectReason = true" class="px-4 py-2 bg-red-600 text-white rounded shadow hover:bg-red-700 transition-colors text-sm font-medium" x-show="!showRejectReason">Tolak</button>
             </div>
          </template>

          
          <template x-if="payload.status === 'accepted'">
             <form x-bind:action="changeStatusUrl('in_progress')" method="POST" x-ref="formStart">
               <input type="hidden" name="_token" value="{{ csrf_token() }}">
               <input type="hidden" name="status" value="in_progress">
               <button type="button" @click="confirmAndSubmit($refs.formStart, 'Mulai kerjakan tugas (start job)?')" class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 transition-colors text-sm font-medium">Mulai Jalan</button>
             </form>
          </template>

          
          <template x-if="payload.status === 'in_progress'">
            <form x-bind:action="changeStatusUrl('completed')" method="POST" x-ref="formCompleted">
              <input type="hidden" name="_token" value="{{ csrf_token() }}">
              <input type="hidden" name="status" value="completed">
              <button type="button" @click="confirmAndSubmit($refs.formCompleted, 'Tugas sudah selesai?')" class="px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 transition-colors text-sm font-medium">Selesai</button>
            </form>
          </template>
        </div>
      </template>
      @endauth
      </div>

      
      <div x-show="showRejectReason" class="w-full absolute inset-x-0 bottom-0 bg-white p-4 border-t shadow-lg" style="display:none;" x-transition>
             <p class="text-sm font-bold text-red-800 mb-2">Alasan Penolakan:</p>
             <form x-bind:action="changeStatusUrl('declined')" method="POST" x-ref="formDecline">
               <input type="hidden" name="_token" value="{{ csrf_token() }}">
               <input type="hidden" name="status" value="declined">
               <textarea name="rejection_reason" class="w-full text-sm border-gray-300 rounded mb-3 focus:ring-red-500 focus:border-red-500" placeholder="Tulis alasan..." required rows="2"></textarea>
               <div class="flex space-x-2 justify-end">
                   <button type="button" @click="showRejectReason = false" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Batal</button>
                   <button type="button" @click="confirmAndSubmit($refs.formDecline, 'Yakin menolak tugas ini?')" class="px-3 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700">Kirim Penolakan</button>
               </div>
             </form>
      </div>

      <x-secondary-button type="button" @click="close()">Tutup</x-secondary-button>
    </div>
  </div>
</div>


@push('inline-styles')
<style>
  .status-badge {
    @apply px-2 py-0.5 text-xs font-medium rounded-full;
  }
  .status-pending { @apply bg-yellow-100 text-yellow-800; }
  .status-accepted { @apply bg-green-100 text-green-800; }
  .status-in_progress { @apply bg-indigo-100 text-indigo-800; }
  .status-completed { @apply bg-blue-100 text-blue-800; }
  .status-declined { @apply bg-red-100 text-red-800; }
</style>
@endpush

@push('scripts')
<script>
  function openAssignmentModal(payload) {
    const event = new CustomEvent('open-assignment-modal', { detail: payload });
    window.dispatchEvent(event);
  }

  function assignmentModal() {
    return {
      open: false,
      payload: {},
      showRejectReason: false,
      currentUserId: {!! json_encode(auth()->check() ? auth()->id() : null) !!},
      currentUserRole: {!! json_encode(auth()->check() ? auth()->user()->role : null) !!},

      init() {
        window.addEventListener('open-assignment-modal', (e) => {
          this.payload = e.detail || {};
          this.open = true;
          this.showRejectReason = false;
        });
      },
      close() {
        this.open = false;
        this.payload = {};
      },
      isCurrentPerformer() {
        if (!this.currentUserId) return false;
        if (this.currentUserRole === 'driver' && this.payload.driver && this.payload.driver.id == this.currentUserId) return true;
        if (this.currentUserRole === 'guide' && this.payload.guide && this.payload.guide.id == this.currentUserId) return true;
        return false;
      },
      changeStatusUrl(status) {
        return `/assignments/${this.payload.id}/status`;
      },
      confirmAndSubmit(formRef, msg = 'Yakin ingin melakukan aksi ini?') {
        if (!confirm(msg)) return;
        formRef.submit();
      }
    }
  }

  // Timer: Update live duration every second
  function updateTimers() {
      const timers = document.querySelectorAll('.live-timer');
      const now = Math.floor(Date.now() / 1000);
      
      timers.forEach(el => {
          const start = parseInt(el.getAttribute('data-start'));
          if (!start) return;
          
          let diff = now - start;
          if (diff < 0) diff = 0;
          
          const hours = Math.floor(diff / 3600);
          const minutes = Math.floor((diff % 3600) / 60);
          const seconds = diff % 60;
          
          el.innerText = 
             String(hours).padStart(2, '0') + ':' +
             String(minutes).padStart(2, '0') + ':' +
             String(seconds).padStart(2, '0');
      });
  }
  setInterval(updateTimers, 1000);
  document.addEventListener('DOMContentLoaded', updateTimers);
</script>
@endpush

@endsection