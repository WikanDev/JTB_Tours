@extends('layouts.app')

@section('title', 'Edit Assignment')

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Edit Assignment {{ $order->customer_name }}</h1>
        <a href="{{ route('assignments.index') }}" class="text-gray-500 hover:text-gray-700">Kembali</a>
    </div>

    @include('partials.flash-and-modal')

    <div class="bg-white rounded shadow overflow-hidden p-6">
        
        <div class="bg-blue-50 p-4 rounded border border-blue-100 mb-6">
            <h3 class="text-sm font-bold text-blue-800 uppercase mb-2">Info Order</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-gray-500 text-xs uppercase">Customer</span>
                    <span class="font-medium text-gray-900">{{ $order->customer_name }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 text-xs uppercase">Product</span>
                    <span class="font-medium text-gray-900">{{ optional($order->product)->name }}</span>
                </div>
                <div>
                     <span class="block text-gray-500 text-xs uppercase">Pickup Time</span>
                     <span class="font-medium text-gray-900">{{ $order->pickup_time }}</span>
                </div>
                <div>
                     <span class="block text-gray-500 text-xs uppercase">Route</span>
                     <span class="font-medium text-gray-900">{{ $order->pickup_location }} → {{ $order->destination }}</span>
                </div>
            </div>
        </div>

        <form action="{{ route('assignments.update', $assignment) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6">
                
                <div>
                    <label for="driver_id" class="block font-medium text-sm text-gray-700">Driver</label>
                    <select id="driver_id" name="driver_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" @selected(old('driver_id', $assignment->driver_id) == $d->id)>
                                {{ $d->name }} 
                                ({{ $d->workSchedules->first()->used_hours ?? 0 }}h used)
                            </option>
                        @endforeach
                    </select>
                    @error('driver_id')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                
                <div>
                    <label for="guide_id" class="block font-medium text-sm text-gray-700">Guide (Optional)</label>
                    <select id="guide_id" name="guide_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Tanpa Guide --</option>
                        @foreach($guides as $g)
                            <option value="{{ $g->id }}" @selected(old('guide_id', $assignment->guide_id) == $g->id)>
                                {{ $g->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('guide_id')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                
                <div>
                    <label for="vehicle_id" class="block font-medium text-sm text-gray-700">Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" @selected(old('vehicle_id', $assignment->vehicle_id) == $v->id)>
                                {{ $v->brand }} {{ $v->type }} - {{ $v->plate_number }} 
                                ({{ ucfirst(str_replace('_',' ', $v->status)) }})
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_id')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                
                <div>
                    <label for="note" class="block font-medium text-sm text-gray-700">Catatan (Note)</label>
                    <textarea id="note" name="note" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" rows="3">{{ old('note', $assignment->note) }}</textarea>
                </div>

                <div class="flex justify-end pt-4">
                    <x-secondary-button href="{{ route('assignments.index') }}" class="mr-2">Batal</x-secondary-button>
                    <x-primary-button>Simpan Perubahan</x-primary-button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
