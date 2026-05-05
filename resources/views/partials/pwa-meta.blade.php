{{-- Meta tags PWA: manifest, theme-color, iOS support, service worker registration --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#4f46e5">
<meta name="application-name" content="{{ config('app.name') }}">
<meta name="mobile-web-app-capable" content="yes">

<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Tool Crib">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

<link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
<link rel="alternate icon" type="image/png" href="{{ asset('icons/apple-touch-icon.png') }}">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register(@json(asset('sw.js')), { scope: @json(url('/').'/') })
                .then((reg) => console.info('[PWA] SW listo:', reg.scope))
                .catch((err) => console.warn('[PWA] SW falló:', err));
        });
    }
</script>
