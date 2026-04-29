@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $logoPath = public_path('images/logo.png');
    $hasLogo = file_exists($logoPath);
@endphp
@if($hasLogo)
<img src="{{ asset('images/logo.png') }}" class="logo" alt="{{ config('app.name') }}" style="max-height: 60px;">
@else
<span style="font-size: 32px;">📧</span>
<span style="display: block; font-weight: bold; font-size: 18px; color: #3d4852;">{{ config('app.name') }}</span>
@endif
</a>
</td>
</tr>
