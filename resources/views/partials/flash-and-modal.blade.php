
{{-- Include the notification card component --}}
<x-notification-card />


{{-- Generic modal component (Tailwind + Alpine)
  Usage:
    <div x-data="{ open:false, payload: {} }" x-show="open"> ... </div>
  We provide a small helper modal layout below.
--}}
@push('scripts')
<script>
  // helper to open modal with data
  function openAssignmentModal(data) {
    const evt = new CustomEvent('open-assignment-modal', { detail: data });
    window.dispatchEvent(evt);
  }
</script>
@endpush
