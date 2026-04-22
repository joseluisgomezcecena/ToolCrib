@php
    $user = auth()->user();
    $subscribeAdmin = $user->hasRole('super_admin');
    $subscribeToolcrib = $user->hasAnyRole(['super_admin', 'toolcrib']);
    $subscribeSelf = $user->hasRole('cliente');
@endphp

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.Echo === 'undefined') return;

    const show = (data) => {
        const container = document.getElementById('realtime-toast-container');
        if (!container) return;

        const colors = {
            critical: 'border-red-500 bg-red-50 text-red-800',
            warning:  'border-amber-500 bg-amber-50 text-amber-800',
            info:     'border-blue-500 bg-blue-50 text-blue-800',
        };

        const el = document.createElement('div');
        el.className = `border-l-4 shadow-lg rounded-md px-4 py-3 ${colors[data.severity] || colors.info}`;
        el.innerHTML = `
            <div class="flex justify-between items-start gap-3">
                <div>
                    <div class="font-semibold text-sm">${data.title}</div>
                    <div class="text-sm mt-0.5">${data.message}</div>
                    <div class="text-xs opacity-70 mt-1">${new Date(data.created_at).toLocaleTimeString()}</div>
                </div>
                <button class="text-gray-500 hover:text-gray-700" onclick="this.closest('div.border-l-4').remove()">&times;</button>
            </div>
        `;
        container.appendChild(el);

        const badge = document.getElementById('nav-alert-badge');
        if (badge) {
            badge.classList.remove('hidden');
            badge.textContent = (parseInt(badge.textContent || '0', 10) || 0) + 1;
        }

        setTimeout(() => el.remove(), 12000);
    };

    @if($subscribeAdmin)
        window.Echo.private('alerts.admin').listen('.alert.created', show);
    @endif
    @if($subscribeToolcrib)
        window.Echo.private('alerts.toolcrib').listen('.alert.created', show);
    @endif
    @if($subscribeSelf)
        window.Echo.private('alerts.user.{{ $user->id }}').listen('.alert.created', show);
    @endif
});
</script>
