{{--
    UAT 24 Nov 2026: footer seragam semua printout PDF.
    Kiri = "Find Us More Info / Scan Below" + QR ke linktree (jawaban klien 25 Nov: kiri bawah
    bukan logo ADS melainkan QR, mengarah ke https://linktr.ee/aromadeliverysystem),
    tengah = Master Company footer 1 (tebal) / 2 / 3, kanan = sertifikasi.
    Dipakai di dalam elemen footer position:fixed milik tiap template.
--}}
@php
    $pdfFooterQr = \App\Support\PdfLetterhead::qrSrc(\App\Support\PdfLetterhead::FOOTER_QR_URL, 60);
    $pdfFooterCert = \App\Support\PdfLetterhead::certificationSrc();
    $pdfFooterLines = \App\Support\PdfLetterhead::footerLines();
@endphp
<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <td style="width: 16%; vertical-align: bottom; text-align: left; font-size: 7px; color: #222; line-height: 1.25;">
            <div style="font-weight: bold;">Find Us More Info</div>
            <div>Scan Below</div>
            @if ($pdfFooterQr)
                <img src="{{ $pdfFooterQr }}" alt="QR {{ \App\Support\PdfLetterhead::FOOTER_QR_URL }}" style="width: 46px; height: 46px; margin-top: 2px;">
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
