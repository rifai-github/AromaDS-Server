<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - Sales Invoice System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verification-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }
        .success-icon i {
            font-size: 50px;
            color: white;
        }
        .error-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .error-icon i {
            font-size: 50px;
            color: white;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .contact-info {
            background: #f3f4f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        .contact-info p {
            margin: 8px 0;
            color: #374151;
        }
        .contact-info strong {
            color: #1f2937;
        }
        .company-name {
            color: #667eea;
            font-weight: 600;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="verification-card">
        @if($success)
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Email Terverifikasi!</h1>
            <p class="subtitle">
                Terima kasih, <strong>{{ $contact->name }}</strong>!<br>
                Email Anda telah berhasil diverifikasi dan akan digunakan untuk menerima invoice dari <span class="company-name">Kami</span>.
            </p>
            
            <div class="contact-info">
                <p><strong>Nama:</strong> {{ $contact->name }}</p>
                <p><strong>Email:</strong> {{ $contact->email }}</p>
                <p><strong>Customer:</strong> {{ $contact->customer?->name ?? '-' }}</p>
                <p><strong>Diverifikasi pada:</strong> {{ $contact->email_verified_at->format('d/M/Y H:i') }} WIB</p>
            </div>
            
            <p class="text-muted small">
                Anda dapat menutup halaman ini.
            </p>
        @else
            <div class="error-icon">
                <i class="fas fa-times"></i>
            </div>
            <h1>Verifikasi Gagal</h1>
            <p class="subtitle">
                {{ $message ?? 'Link verifikasi tidak valid atau sudah kadaluarsa.' }}
            </p>
            <p class="text-muted small">
                Silakan hubungi Admin atau Sales untuk mendapatkan link verifikasi baru.
            </p>
        @endif
    </div>
</body>
</html>
