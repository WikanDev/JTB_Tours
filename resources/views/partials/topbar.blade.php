<nav class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between h-14">
            <div class="flex items-center space-x-3">
              
              <div class="md:hidden">
                <button 
                    @click="sidebarOpen = !sidebarOpen" 
                    class="flex items-center p-2 rounded hover:bg-gray-100 md:hidden"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
              </div>

                <a href="{{ url('/dashboard') }}" class="flex items-center space-x-2">
                    <img src="{{ asset('img/JTB_logo.png') }}" alt="" class="max-w-[50px]">
                </a>
            </div>

            @php
                $user = auth()->user();
            @endphp

            @if($user && in_array($user->role, ['driver','guide']))
                <div class="hidden md:flex items-center space-x-2">
                    <form action="{{ route('availability.toggle') }}" method="POST">
                        @csrf
                        <button 
                            type="submit" 
                            class="flex items-center gap-2 px-3 py-1 rounded {{ $user->status === 'online' ? 'bg-green-600 text-white' : 'bg-gray-200' }}"
                        >
                            <span class="w-2 h-2 rounded-full {{ $user->status === 'online' ? 'bg-white' : 'bg-gray-400' }}"></span>
                            <span class="text-sm">{{ $user->status === 'online' ? 'Online' : 'Offline' }}</span>
                        </button>
                    </form>
                </div>
            @endif

            <div class="flex items-center space-x-3">
                
                <div x-data="notificationHandler()" x-init="startPolling()" class="relative">
                    <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-400 hover:text-gray-600">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                         </svg>
                         <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full" style="display:none;"></span>
                    </a>
                </div>

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-gray-100">
                        <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center text-sm">
                            {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                        </div>
                        <div class="hidden sm:block text-sm">
                            <div>{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ auth()->user()->role }}</div>
                        </div>
                    </button>

                    <div 
                        x-show="open" 
                        @click.away="open=false" 
                        x-cloak 
                        class="absolute right-0 mt-2 w-48 bg-white rounded p-2 shadow z-50"
                    >
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-gray-50">
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button 
                                type="submit" 
                                class="w-full text-left px-4 py-2 text-sm bg-red-600 text-white hover:bg-red-900"
                            >
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>
