@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Detail Assignment {{ $assignment->order->customer_name ?? 'Assignment' }}</h1>
        <div class="flex gap-2">
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                Kembali
            </a>
            @if(in_array(auth()->user()->role, ['super_admin', 'staff']))
                <a href="{{ route('assignments.edit', $assignment->id) }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Edit
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        <p>{{ session('error') }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- Status Card --}}
        <div class="bg-white rounded-lg shadow p-6 md:col-span-2 flex items-center justify-between">
            <div>
                <span class="text-gray-500 text-sm uppercase tracking-wider font-semibold">Status Saat Ini</span>
                <div class="mt-1">
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                        {{ $assignment->status === 'completed' ? 'bg-blue-100 text-blue-800' : 
                           ($assignment->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                           ($assignment->status === 'accepted' ? 'bg-green-100 text-green-800' : 
                           ($assignment->status === 'declined' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))) }}">
                        {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                    </span>
                </div>
            </div>
            <div class="text-right text-sm text-gray-500">
                <div>Dibuat: {{ $assignment->created_at->format('d M Y H:i') }}</div>
                <div>Oleh: {{ $assignment->assignedBy->name ?? 'System' }}</div>
            </div>
        </div>

        {{-- Order Info --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-red-50 px-6 py-4 border-b border-red-100">
                <h3 class="text-lg font-bold text-red-800">Informasi Order</h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Order ID</span>
                    <span class="font-medium">#{{ $assignment->order->id }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Customer</span>
                    <span class="font-medium">{{ $assignment->order->customer_name }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Tanggal Pickup</span>
                    <span class="font-medium text-red-600">
                        {{ \Carbon\Carbon::parse($assignment->order->pickup_time)->format('d M Y H:i') }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500 block mb-1">Lokasi Pickup</span>
                    <p class="font-medium text-gray-800">{{ $assignment->order->pickup_location }}</p>
                </div>
                <div>
                    <span class="text-gray-500 block mb-1">Tujuan / Rute</span>
                    <p class="font-medium text-gray-800">{{ $assignment->order->drop_location ?? '-' }}</p>
                </div>
                <div>
                     <span class="text-gray-500 block mb-1">Catatan Order</span>
                     <p class="italic text-gray-600 bg-gray-50 p-2 rounded text-sm">{{ $assignment->order->special_requests ?? 'Tidak ada catatan.' }}</p>
                </div>
            </div>
        </div>

        {{-- Assignment Detail --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                <h3 class="text-lg font-bold text-blue-800">Detail Penugasan</h3>
            </div>
            <div class="p-6 space-y-4">
                
                {{-- Driver --}}
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="ph ph-steering-wheel text-xl"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 uppercase font-bold">Driver</span>
                        <div class="font-bold text-gray-900">{{ $assignment->driver->name ?? '-' }}</div>
                        <div class="text-sm text-gray-600">{{ $assignment->driver->phone ?? '-' }}</div>
                    </div>
                </div>

                {{-- Guide --}}
                @if($assignment->guide)
                <div class="flex items-start gap-4 pt-4 border-t border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                        <i class="ph ph-user text-xl"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 uppercase font-bold">Guide</span>
                        <div class="font-bold text-gray-900">{{ $assignment->guide->name }}</div>
                        <div class="text-sm text-gray-600">{{ $assignment->guide->phone ?? '-' }}</div>
                    </div>
                </div>
                @endif

                {{-- Vehicle --}}
                @if($assignment->vehicle)
                <div class="flex items-start gap-4 pt-4 border-t border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                        <i class="ph ph-car text-xl"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 uppercase font-bold">Kendaraan</span>
                        <div class="font-bold text-gray-900">{{ $assignment->vehicle->brand }} {{ $assignment->vehicle->type }}</div>
                        <div class="text-sm text-gray-600">{{ $assignment->vehicle->plate_number }}</div>
                    </div>
                </div>
                @endif

                <div class="pt-4 border-t border-gray-100">
                    <span class="text-gray-500 block mb-1 text-sm">Catatan Penugasan</span>
                    <p class="text-sm text-gray-700 bg-gray-50 p-2 rounded border border-gray-200">
                        {{ $assignment->note ?? 'Tidak ada catatan tambahan.' }}
                    </p>
                </div>

            </div>
        </div>

        {{-- Timeline / Logs (Optional but helpful) --}}
        <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Timeline</h3>
            <div class="flex flex-col md:flex-row gap-4 text-sm">
                <div class="flex-1 p-3 border rounded bg-gray-50">
                    <span class="block text-gray-500 text-xs uppercase">Ditugaskan</span>
                    <span class="font-medium">{{ $assignment->assigned_at ? $assignment->assigned_at->format('d M H:i') : '-' }}</span>
                </div>
                <div class="flex-1 p-3 border rounded bg-gray-50">
                    <span class="block text-gray-500 text-xs uppercase">Mulai Dikerjakan</span>
                    <span class="font-medium text-blue-600">{{ $assignment->started_at ? $assignment->started_at->format('d M H:i') : '-' }}</span>
                </div>
                <div class="flex-1 p-3 border rounded bg-gray-50">
                    <span class="block text-gray-500 text-xs uppercase">Selesai</span>
                    <span class="font-medium text-green-600">{{ $assignment->completed_at ? $assignment->completed_at->format('d M H:i') : '-' }}</span>
                </div>
                 @if($assignment->status == 'declined')
                <div class="flex-1 p-3 border rounded bg-red-50 border-red-200">
                    <span class="block text-red-500 text-xs uppercase">Ditolak Pada</span>
                    <span class="font-medium text-red-700">{{ $assignment->rejected_at ? $assignment->rejected_at->format('d M H:i') : '-' }}</span>
                    <div class="mt-1 text-xs text-red-600 italic">"{{ $assignment->rejection_reason }}"</div>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
