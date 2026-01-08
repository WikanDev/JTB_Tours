@php
  $isAuth = auth()->check();
  $role = $isAuth ? auth()->user()->role : null;
@endphp

<div class="h-full sidebar-scroll overflow-y-auto p-6 bg-linear-to-b from-red-600 via-red-700 to-red-800">
  
  <div class="mb-8 pb-4 border-b border-red-500/30">
    <h2 class="text-white font-bold text-lg tracking-wide">JTB Tours</h2>
    <p class="text-red-200 text-xs mt-1">Management System</p>
  </div>

  
  <div>
    <div class="text-xs text-red-200/70 uppercase mb-4 font-semibold tracking-wider">Navigation</div>

    <ul class="space-y-2">
      
      @if($isAuth)
        <li>
          <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-house text-lg" aria-hidden="true"></i>
            <span class="text-sm">Dashboard</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','admin','staff']))
        <li>
          <a href="{{ Route::has('users.index') ? route('users.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('users.*') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-users text-lg" aria-hidden="true"></i>
            <span class="text-sm">Users</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','admin','staff']))
        <li>
          <a href="{{ Route::has('orders.index') ? route('orders.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('orders.*') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-list text-lg" aria-hidden="true"></i>
            <span class="text-sm">Orders</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('assignments.index') ? route('assignments.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('assignments.index','assignments.create') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-clipboard-text text-lg" aria-hidden="true"></i>
            <span class="text-sm">Assignments</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['driver','guide']))
        <li>
          <a href="{{ Route::has('assignments.my') ? route('assignments.my') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('assignments.my') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-clipboard text-lg" aria-hidden="true"></i>
            <span class="text-sm">Tugas Saya</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('vehicles.index') ? route('vehicles.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('vehicles.*') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-car text-lg" aria-hidden="true"></i>
            <span class="text-sm">Vehicles</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('guides-drivers.index') ? route('guides-drivers.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('guides-drivers.*') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-steering-wheel text-lg" aria-hidden="true"></i>
            <span class="text-sm">Guide & Driver</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('products.index') ? route('products.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('products.*') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-package text-lg" aria-hidden="true"></i>
            <span class="text-sm">Products</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','staff']))
        <li>
          <a href="{{ Route::has('work-schedules.index') ? route('work-schedules.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('work-schedules.*') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-calendar-check text-lg" aria-hidden="true"></i>
            <span class="text-sm">Work Schedules</span>
          </a>
        </li>
      @endif

      @if($isAuth && in_array($role, ['super_admin','admin','staff','driver','guide']))
        <li>
          <a href="{{ Route::has('reports.index') ? route('reports.index') : '#' }}"
             class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('reports.*') ? 'bg-white text-red-700 shadow-lg font-semibold' : 'text-white hover:bg-red-900 hover:pl-5' }}">
            <i class="ph ph-chart-bar text-lg" aria-hidden="true"></i>
            <span class="text-sm">Reports</span>
          </a>
        </li>
      @endif

    </ul>
  </div>
</div>
