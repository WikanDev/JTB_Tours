@php
  $order = $a->order ?? null;
  $orderPickup = $order && $order->pickup_time
    ? \Carbon\Carbon::parse($order->pickup_time)->format('d M Y H:i')
    : '-';
  $productName = $order && $order->product ? $order->product->name : '-';

  $payload = [
    'id' => $a->id,
    'order' => [
      'customer' => $order?->customer_name ?? '-',
      'pickup' => $orderPickup,
      'from' => $order?->pickup_location ?? '-',
      'to' => $order?->destination ?? '-',
      'product' => $productName,
    ],
    'driver' => $a->driver ? ['id' => $a->driver->id, 'name' => $a->driver->name] : null,
    'guide' => $a->guide ? ['id' => $a->guide->id, 'name' => $a->guide->name] : null,
    'status' => $a->status,
    'note' => $a->note ?? null,
  ];
  $payloadJson = json_encode($payload);
@endphp

<div class="bg-white p-3 rounded shadow border-l-4 {{ match($a->status) {
    'pending' => 'border-yellow-400',
    'accepted' => 'border-green-400',
    'in_progress' => 'border-indigo-400',
    'completed' => 'border-blue-400',
    'declined' => 'border-red-400',
    default => 'border-gray-300',
} }}">
  <div class="flex items-start justify-between">
    <div class="flex-1">
      <div class="font-medium text-gray-900">#{{ $a->id }} — {{ $order?->customer_name ?? '-' }}</div>
      <div class="text-xs text-gray-500 mt-0.5">
        {{ $orderPickup }} · {{ $productName }}
      </div>
      <div class="mt-1">
        <span class="status-badge status-{{ $a->status }}">
          {{ ucfirst(str_replace('_', ' ', $a->status)) }}
        </span>
        @if($a->workstart && in_array($a->status, ['accepted', 'completed']))
          <span class="text-xs text-gray-500 ml-2">Mulai: {{ \Carbon\Carbon::parse($a->workstart)->format('H:i') }}</span>
        @endif
        @if($a->status === 'in_progress' && $a->started_at)
             <span class="text-xs font-mono bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded ml-2 live-timer" 
                   data-start="{{ \Carbon\Carbon::parse($a->started_at)->timestamp }}">
                   Loading...
             </span>
        @endif
      </div>
    </div>

    @if($showActions)
      <button
        type="button"
        onclick="openAssignmentModal({{ $payloadJson }})"
        class="inline-flex items-center px-2 py-1 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        Detail
      </button>
    @endif
  </div>
</div>