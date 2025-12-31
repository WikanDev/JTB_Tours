{{-- Notification Card Component --}}
@if(session('success') || session('error'))
<div 
  x-data="{ show: true }" 
  x-show="show"
  x-init="setTimeout(() => show = false, 5000)"
  class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black bg-opacity-50 backdrop-blur-sm"
  x-transition:enter="transition ease-out duration-300"
  x-transition:enter-start="opacity-0"
  x-transition:enter-end="opacity-100"
  x-transition:leave="transition ease-in duration-200"
  x-transition:leave-start="opacity-100"
  x-transition:leave-end="opacity-0"
  style="display: none;"
>
  <div 
    class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-75"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-75"
  >
    @if(session('success'))
      <!-- Success Card -->
      <div class="p-8 text-center">
        <!-- Success Icon -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
          <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        
        <!-- Title -->
        <h3 class="text-2xl font-bold text-gray-900 mb-2">Success!</h3>
        
        <!-- Message -->
        <p class="text-gray-600 mb-6">{{ session('success') }}</p>
        
        <!-- Button -->
        <button 
          @click="show = false" 
          class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
        >
          OK, Got it!
        </button>
      </div>
    @endif

    @if(session('error'))
      <!-- Error Card -->
      <div class="p-8 text-center">
        <!-- Error Icon -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
          <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        
        <!-- Title -->
        <h3 class="text-2xl font-bold text-gray-900 mb-2">Error!</h3>
        
        <!-- Message -->
        <p class="text-gray-600 mb-6">{{ session('error') }}</p>
        
        <!-- Button -->
        <button 
          @click="show = false" 
          class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        >
          Close
        </button>
      </div>
    @endif
  </div>
</div>
@endif
