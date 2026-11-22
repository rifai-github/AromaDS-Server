<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pencairan Komisi Diproses</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; font-size: 14px; line-height: 1.5;">
    <p>Halo Tim Finance,</p>

    <p>Pencairan komisi marketing baru saja diproses di Aroma ERP (Finance &gt; Commission Payment).</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin: 12px 0;">
        <tr>
            <td style="color:#6b7280;">Diproses oleh</td>
            <td><strong>{{ $adminName }}</strong></td>
        </tr>
        <tr>
            <td style="color:#6b7280;">Tanggal pembayaran</td>
            <td>{{ $paymentDate }}</td>
        </tr>
        <tr>
            <td style="color:#6b7280;">Jumlah marketing</td>
            <td><strong>{{ $marketingCount }}</strong></td>
        </tr>
        <tr>
            <td style="color:#6b7280;">Total nominal komisi</td>
            <td><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    @if(count($marketingRows) > 0)
        <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; border: 1px solid #e5e7eb;">
            <thead>
                <tr style="background:#f3f4f6;">
                    <th align="left" style="border: 1px solid #e5e7eb;">Marketing</th>
                    <th align="right" style="border: 1px solid #e5e7eb;">Jumlah komisi</th>
                    <th align="right" style="border: 1px solid #e5e7eb;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($marketingRows as $row)
                    <tr>
                        <td style="border: 1px solid #e5e7eb;">{{ $row['name'] }}</td>
                        <td align="right" style="border: 1px solid #e5e7eb;">{{ $row['count'] }}</td>
                        <td align="right" style="border: 1px solid #e5e7eb;">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin-top: 16px;">Status pembayaran saat ini <strong>Pending</strong>. Tandai Processing/Completed di menu Commission Payment setelah transfer dilakukan.</p>

    <p style="color:#6b7280; font-size: 12px;">Email ini dikirim otomatis oleh Aroma ERP.</p>
</body>
</html>
