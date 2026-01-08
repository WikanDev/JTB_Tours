{{-- Confirmation Modal --}}
<x-confirm-modal danger />

@push('scripts')
<script>
  // helper to open modal with data
  function openAssignmentModal(data) {
    const evt = new CustomEvent('open-assignment-modal', { detail: data });
    window.dispatchEvent(evt);
  }
</script>
@endpush
