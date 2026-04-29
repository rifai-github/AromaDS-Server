@component('mail::message')
# Verifikasi Email untuk Penerimaan Invoice

Halo **{{ $contact->name }}**,

Email Anda telah didaftarkan sebagai alamat penerima invoice untuk **{{ $customerName }}** di sistem Kami.

Untuk mengkonfirmasi bahwa Anda bersedia menerima invoice melalui email ini, silakan klik tombol di bawah:

@component('mail::button', ['url' => $verificationUrl, 'color' => 'success'])
Verifikasi Email Saya
@endcomponent

Dengan mengklik tombol di atas, Anda menyetujui untuk menerima dokumen invoice dan dokumen terkait billing lainnya melalui alamat email ini.

---

**Detail Kontak:**
- Nama: {{ $contact->name }}
- Jabatan: {{ $contact->position ?? '-' }}
- Email: {{ $contact->email }}
- Customer: {{ $customerName }}

---

Jika Anda tidak merasa mendaftar untuk menerima invoice, silakan abaikan email ini.

Terima kasih,<br>
**Tim Sales & Teknisi PT Pink Service Indonesia**

@component('mail::subcopy')
Jika tombol tidak berfungsi, salin dan tempel URL berikut ke browser Anda: [{{ $verificationUrl }}]({{ $verificationUrl }})
@endcomponent
@endcomponent
