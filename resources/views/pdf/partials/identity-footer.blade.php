{{--
    UAT 24 Nov 2026: footer seragam semua printout PDF.
    Kiri = logo, tengah = Master Company footer 1 (tebal) / 2 / 3, kanan = sertifikasi.
    Dipakai di dalam elemen footer position:fixed milik tiap template.
--}}
@php
    $pdfFooterLogo = \App\Support\PdfLetterhead::logoSrc();
    $pdfFooterCert = \App\Support\PdfLetterhead::certificationSrc();
    $pdfFooterLines = \App\Support\PdfLetterhead::footerLines();
@endphp
<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <td style="width: 16%; vertical-align: bottom; text-align: left;">
            @if ($pdfFooterLogo)
                <img src="{{ $pdfFooterLogo }}" alt="Logo" style="width: 58px; height: auto;">
            @endif
        </td>
        <td style="width: 68%; vertical-align: bottom; text-align: center; font-size: 8px; color: #555; line-height: 1.35;">
            @foreach ($pdfFooterLines as $index => $line)
                <div style="{{ $index === 0 ? 'font-weight: bold; color: #222;' : '' }}">{{ $line }}</div>
            @endforeach
        </td>
        <td style="width: 16%; vertical-align: bottom; text-align: right;">
            @if ($pdfFooterCert)
                <img src="{{ $pdfFooterCert }}" alt="Sertifikasi" style="width: 92px; height: auto;">
            @endif
        </td>
    </tr>
</table>
