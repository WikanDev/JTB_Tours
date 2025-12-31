<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Dashboard')</title>
  @vite('resources/css/style.css')

  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2"></script>

  @if(app()->environment('local'))
    
    @vite('resources/css/app.css')
  @else
    @vite('resources/css/app.css')
  @endif

  @stack('head')

  <style>
    .sidebar-scroll::-webkit-scrollbar { width: 8px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 8px; }
    [x-cloak] { display: none !important; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800" x-data="layout()" x-init="init()" @keydown.escape.window="closeSidebar()">
  {{-- Include flash notification card --}}
  <x-notification-card />
  
  @if(auth()->check())
    <div class="min-h-screen flex">

      
      <aside
        class="hidden md:flex md:flex-col w-64 bg-white border-r sidebar-scroll"
        aria-hidden="false"
        role="navigation"
      >
        @include('partials.sidebar')
      </aside>

      
      <div class="md:hidden" role="dialog" aria-modal="true" x-cloak>
        <div
          x-show="sidebarOpen"
          x-transition.opacity
          class="fixed inset-0 bg-black/40 z-40"
          @click="closeSidebar()"
          aria-hidden="true"
        ></div>

        <aside
          x-show="sidebarOpen"
          x-transition:enter="transform transition ease-in-out duration-200"
          x-transition:enter-start="-translate-x-full"
          x-transition:enter-end="translate-x-0"
          x-transition:leave="transform transition ease-in-out duration-150"
          x-transition:leave-start="translate-x-0"
          x-transition:leave-end="-translate-x-full"
          class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r sidebar-scroll p-4"
        >
          <div class="flex items-center justify-between mb-4">
            <div class="text-lg font-semibold">Menu</div>
            <button @click="closeSidebar()" aria-label="Tutup menu" class="p-1 rounded hover:bg-gray-100">✕</button>
          </div>

          @include('partials.sidebar')
        </aside>
      </div>

      
      <div class="flex-1 flex flex-col min-h-screen">
        
        @include('partials.topbar')

        
        <main class="p-4">
          <div class="max-w-7xl mx-auto w-full">
            @yield('content')
          </div>
        </main>
      </div>
    </div>
  @else
    
    <div class="min-h-screen flex items-center justify-center">
      <div class="w-full max-w-md p-6">
        @yield('content')
      </div>
    </div>
  @endif

  @stack('scripts')
  @include('partials.notification_script')

  <script>
    function layout(){
      return {
        sidebarOpen: false,
        init() {
          // If you want to open the sidebar by default on larger screens, set here.
        },
        openSidebar() {
          this.sidebarOpen = true;
          document.documentElement.style.overflow = 'hidden';
          document.body.style.overflow = 'hidden';
        },
        closeSidebar() {
          this.sidebarOpen = false;
          document.documentElement.style.overflow = '';
          document.body.style.overflow = '';
        },
        toggleSidebar() {
          this.sidebarOpen ? this.closeSidebar() : this.openSidebar();
        }
      }
    }
  </script>
</body>
</html>
