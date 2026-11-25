<?php

namespace App\Support;

use App\Models\Company;

/**
 * UAT 24 Nov 2026: semua printout PDF (survey, quotation, kontrak, IR/IF/CSR, invoice, tanda
 * terima) memakai kop dan footer yang seragam:
 *  - kiri atas logo ADS; kanan atas nama perusahaan + QR nomor dokumen (beda per dokumen);
 *  - kiri bawah logo, kanan bawah sertifikasi, tengah bawah = Master Company footer 1/2/3
 *    (footer 1 tebal) - bukan teks hardcode.
 * Gambar dibuat data-URI karena DomPDF tidak memuat URL lokal.
 */
class PdfLetterhead
{
    private static array $imageCache = [];

    public static function company(): ?Company
    {
        return once(fn () => Company::query()->first());
    }

    public static function companyName(): string
    {
        return strtoupper(trim((string) (self::company()?->name ?: 'PT. PINK SERVICES INDONESIA')));
    }

    /** @return list<string> footer 1..3 dari Master Company, yang kosong dibuang. */
    public static function footerLines(): array
    {
        $company = self::company();

        return array_values(array_filter(array_map(
            fn ($line) => trim((string) $line),
            [$company?->footer_line_1, $company?->footer_line_2, $company?->footer_line_3]
        ), fn ($line) => $line !== ''));
    }

    public static function logoSrc(): ?string
    {
        return self::imageSrc(public_path('images/logo.png'));
    }

    /** Gambar sertifikasi (ISO 14001 / IAS) di kanan bawah. */
    public static function certificationSrc(): ?string
    {
        return self::imageSrc(public_path('certified-company.png'));
    }

    /**
     * QR nomor dokumen sebagai PNG data-URI. DomPDF tidak merender SVG bacon-qr-code dan
     * imagick tidak tersedia, jadi matriksnya digambar dengan GD.
     */
    public static function qrSrc(?string $data, int $size = 78): ?string
    {
        $data = trim((string) $data);
        if ($data === '' || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        try {
            $matrix = \BaconQrCode\Encoder\Encoder::encode(
                $data,
                \BaconQrCode\Common\ErrorCorrectionLevel::valueOf('M'),
            )->getMatrix();

            $count = $matrix->getWidth();
            $scale = max(1, (int) floor($size / $count));
            $px = max($count * $scale, $count);

            $im = imagecreatetruecolor($px, $px);
            $white = imagecolorallocate($im, 255, 255, 255);
            $black = imagecolorallocate($im, 0, 0, 0);
            imagefilledrectangle($im, 0, 0, $px, $px, $white);
            for ($y = 0; $y < $count; $y++) {
                for ($x = 0; $x < $count; $x++) {
                    if ($matrix->get($x, $y)) {
                        imagefilledrectangle($im, $x * $scale, $y * $scale, ($x + 1) * $scale - 1, ($y + 1) * $scale - 1, $black);
                    }
                }
            }

            ob_start();
            imagepng($im);
            $png = ob_get_clean();
            imagedestroy($im);

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function imageSrc(string $path): ?string
    {
        if (array_key_exists($path, self::$imageCache)) {
            return self::$imageCache[$path];
        }

        $src = null;
        if (is_file($path)) {
            $src = 'data:image/'.pathinfo($path, PATHINFO_EXTENSION).';base64,'.base64_encode((string) file_get_contents($path));
        }

        return self::$imageCache[$path] = $src;
    }
}
