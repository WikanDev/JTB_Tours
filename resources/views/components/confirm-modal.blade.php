@props([
    'id' => 'confirm-modal',
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin?',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'danger' => false
])

<div 
    x-data="confirmModalData()" 
    x-show="open" 
    x-cloak 
    class="fixed inset-0 z-50 flex items-center justify-center"
    id="{{ $id }}"
>
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="open = false"></div>
    
    <!-- Modal Card -->
    <div 
        class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all"
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <!-- Header with Icon -->
        <div class="p-6 pb-4">
            <div class="flex items-start space-x-4">
                <!-- Warning Icon -->
                <div class="{{ $danger ? 'bg-red-100' : 'bg-yellow-100' }} rounded-full p-3 flex-shrink-0">
                    <svg class="w-6 h-6 {{ $danger ? 'text-red-600' : 'text-yellow-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                
                <!-- Title and Message -->
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="title"></h3>
                    <p class="mt-2 text-sm text-gray-600" x-text="message"></p>
                </div>
                
                <!-- Close Button -->
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Footer with Actions -->
        <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end space-x-3">
            <button 
                @click="open = false" 
                type="button"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
            >
                {{ $cancelText }}
            </button>
            
            <form x-ref="confirmForm" method="POST" class="inline">
                @csrf
                <input type="hidden" name="_method" x-bind:value="formMethod">
                <button 
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors {{ $danger ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500' }}"
                >
                    {{ $confirmText }}
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmModalData() {
        return {
            open: false, 
            title: '{{ $title }}',
            message: '{{ $message }}',
            formAction: '',
            formMethod: 'POST',
            init() {
                window.addEventListener('open-confirm-modal', (e) => {
                    this.title = e.detail.title || '{{ $title }}';
                    this.message = e.detail.message || '{{ $message }}';
                    this.formAction = e.detail.action || '';
                    this.formMethod = e.detail.method || 'POST';
                    
                    // Set form action dynamically
                    this.$nextTick(() => {
                        if (this.$refs.confirmForm) {
                            this.$refs.confirmForm.setAttribute('action', this.formAction);
                        }
                    });
                    
                    this.open = true;
                });
            }
        };
    }

    function confirmDelete(action, title = 'Hapus Data', message = 'Apakah Anda yakin ingin menghapus data ini?') {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', {
            detail: {
                title: title,
                message: message,
                action: action,
                method: 'DELETE'
            }
        }));
    }
    
    function confirmAction(action, title, message, method = 'POST') {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', {
            detail: {
                title: title,
                message: message,
                action: action,
                method: method
            }
        }));
    }
</script>
@endpush
