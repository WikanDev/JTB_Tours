@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Guide & Driver List</h1>
        
        <form method="GET" action="{{ route('guides-drivers.index') }}" class="flex items-center gap-2 w-full md:w-auto">
            <select name="role" class="border-gray-300 rounded focus:ring-red-500 focus:border-red-500 text-sm">
                <option value="">All Roles</option>
                <option value="driver" {{ request('role') == 'driver' ? 'selected' : '' }}>Driver</option>
                <option value="guide" {{ request('role') == 'guide' ? 'selected' : '' }}>Guide</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/HP..." 
                   class="border-gray-300 rounded focus:ring-red-500 focus:border-red-500 text-sm w-full md:w-64">
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                <i class="ph ph-magnifying-glass"></i>
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($users as $user)
            <div class="bg-white rounded-lg shadow hover:shadow-md transition duration-200 overflow-hidden border-t-4 {{ $user->role == 'driver' ? 'border-blue-500' : 'border-green-500' }}">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-lg font-bold {{ $user->role == 'driver' ? 'text-blue-600' : 'text-green-600' }}">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 line-clamp-1">{{ $user->name }}</h3>
                                <span class="text-xs uppercase font-semibold tracking-wider {{ $user->role == 'driver' ? 'text-blue-500' : 'text-green-500' }}">
                                    {{ $user->role }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                             @php
                                $activeAssignment = $user->role == 'driver' ? $user->assignmentsAsDriver->first() : $user->assignmentsAsGuide->first();
                            @endphp
                            
                            @if($activeAssignment)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 animate-pulse">
                                    <span class="w-2 h-2 mr-1 bg-red-500 rounded-full"></span>
                                    Sibuk
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-2 h-2 mr-1 bg-green-500 rounded-full"></span>
                                    Available
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ph ph-phone w-5 text-gray-400"></i>
                            <span class="font-medium mr-2">{{ $user->phone ?? '-' }}</span>
                             @if($user->phone)
                                @php
                                    $wa = preg_replace('/[^0-9]/', '', $user->phone);
                                    if(substr($wa, 0, 1) == '0') {
                                        $wa = '62' . substr($wa, 1);
                                    }
                                @endphp
                                <a href="https://wa.me/{{ $wa }}" target="_blank" class="text-green-600 hover:text-green-800 text-xs border border-green-200 px-2 py-0.5 rounded ml-auto flex items-center gap-1">
                                    <i class="ph ph-whatsapp-logo"></i> WhatsApp
                                </a>
                            @endif
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ph ph-envelope-simple w-5 text-gray-400"></i>
                            <span class="truncate">{{ $user->email }}</span>
                        </div>
                    </div>

                    @if($activeAssignment)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Sedang Mengerjakan:</h4>
                            <div class="bg-gray-50 rounded p-3 text-sm">
                                <div class="font-medium text-gray-800 mb-1">
                                    {{ $activeAssignment->order?->customer_name ?? 'Customer' }}
                                </div>
                                <div class="text-gray-500 text-xs flex items-center gap-2 mb-1">
                                    <i class="ph ph-clock"></i>
                                    {{ $activeAssignment->order->pickup_time ? \Carbon\Carbon::parse($activeAssignment->order->pickup_time)->format('H:i') : '-' }} 
                                    ({{ $activeAssignment->order->estimated_duration_minutes }} min)
                                </div>
                                @if($user->role == 'driver' && $activeAssignment->vehicle)
                                    <div class="text-gray-500 text-xs flex items-center gap-2">
                                        <i class="ph ph-car"></i>
                                        {{ $activeAssignment->vehicle->brand }} ({{ $activeAssignment->vehicle->plate_number }})
                                    </div>
                                @endif
                                <div class="mt-2 text-right">
                                     <a href="{{ route('assignments.show', $activeAssignment->id) }}" class="text-xs text-indigo-600 font-medium hover:underline">
                                        Lihat Tugas &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else 
                         <div class="mt-4 pt-4 border-t border-gray-100 h-24 flex items-center justify-center text-gray-400 text-sm italic">
                            Tidak ada tugas aktif saat ini.
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500 bg-white rounded shadow">
                <i class="ph ph-users text-4xl mb-3 text-gray-300"></i>
                <p>Tidak ada data guide atau driver ditemukan.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
@endsection
