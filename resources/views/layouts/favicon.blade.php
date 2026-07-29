{{-- Favicon Configuration --}}
{{-- Place this in your layout head section --}}

{{--
    Only declare icons that actually exist in public/theme/assets/images/.
    Previously this partial advertised 13 files that were never committed
    (favicon-32x32.png, favicon-16x16.png, every apple-touch-icon-*, mstile-*,
    android-chrome-512x512.png). Chrome would request the declared 32x32 PNG,
    receive the HTML 404 page, and fall back to /favicon.ico — which is a
    0-byte file — leaving the tab with the browser's blank/default icon.
--}}

{{-- Standard Favicon (SVG scales to every tab size) --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('theme/assets/images/img_logo.svg') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('theme/assets/images/android-chrome-192x192.png') }}">

{{-- Apple Touch Icon (falls back to the 192px PNG; no dedicated 180px asset exists) --}}
<link rel="apple-touch-icon" href="{{ asset('theme/assets/images/android-chrome-192x192.png') }}">

{{-- Web App Manifest --}}
<link rel="manifest" href="{{ asset('theme/assets/images/site.webmanifest') }}">

{{-- Theme Color --}}
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="theme-color" content="#ffffff">
