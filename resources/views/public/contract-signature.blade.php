<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tanda Tangan Kontrak {{ $contract?->contract_number }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <style>
        :root { --blue: #1e3a8a; --border: #d7dde8; --muted: #5b6475; --bg: #f3f5f9; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: var(--bg); color: #1f2937; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 20px 16px 40px; }
        .head { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .head img { height: 44px; }
        .head h1 { font-size: 18px; margin: 0; color: var(--blue); }
        .head p { margin: 2px 0 0; color: var(--muted); font-size: 13px; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 18px; margin-bottom: 16px; }
        .card h2 { font-size: 15px; margin: 0 0 12px; color: var(--blue); }
        .meta { display: grid; grid-template-columns: 160px 1fr; gap: 6px 12px; font-size: 14px; }
        .meta div:nth-child(odd) { color: var(--muted); }
        .doc-frame { width: 100%; height: 520px; border: 1px solid var(--border); border-radius: 6px; background: #fafafa; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border-radius: 6px; padding: 10px 16px; font-size: 14px; font-weight: bold; cursor: pointer; border: 1px solid transparent; text-decoration: none; }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:disabled { opacity: .55; cursor: not-allowed; }
        .btn-light { background: #fff; color: var(--blue); border-color: var(--border); }
        label { font-size: 14px; display: block; margin-bottom: 6px; }
        input[type=text] { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        .pad-box { border: 2px dashed #9aa6bd; border-radius: 8px; background: #fff; position: relative; }
        .pad-box canvas { width: 100%; height: 200px; display: block; touch-action: none; }
        .pad-hint { position: absolute; left: 0; right: 0; top: 46%; text-align: center; color: #9aa6bd; font-size: 13px; pointer-events: none; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; margin-top: 10px; }
        .agree { display: flex; gap: 8px; align-items: flex-start; font-size: 14px; line-height: 1.45; margin: 14px 0; }
        .agree input { margin-top: 3px; }
        .alert { border-radius: 8px; padding: 14px 16px; font-size: 14px; line-height: 1.5; margin-bottom: 16px; }
        .alert-ok { background: #e8f6ee; border: 1px solid #9bd3b2; color: #14532d; }
        .alert-warn { background: #fff7e6; border: 1px solid #f3c77a; color: #7a4b00; }
        .alert-err { background: #fdecec; border: 1px solid #f2a7a7; color: #7f1d1d; display: none; }
        .small { font-size: 12px; color: var(--muted); }
        .mode-tabs { display: flex; margin: 14px 0 10px; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
        .mode-tabs button { flex: 1; padding: 10px 8px; font-size: 14px; font-weight: bold; border: 0; background: #fff; color: var(--muted); cursor: pointer; }
        .mode-tabs button + button { border-left: 1px solid var(--border); }
        .mode-tabs button.active { background: var(--blue); color: #fff; }
        .upload-box { border: 2px dashed #9aa6bd; border-radius: 8px; background: #fff; min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; padding: 16px; text-align: center; }
        .upload-box img { max-width: 100%; max-height: 170px; display: none; }
        .upload-box input[type=file] { max-width: 100%; font-size: 14px; }
        @media (max-width: 560px) {
            .meta { grid-template-columns: 1fr; }
            .meta div:nth-child(odd) { margin-top: 6px; }
            .doc-frame { height: 380px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" onerror="this.style.display='none'">
        <div>
            <h1>Tanda Tangan Kontrak</h1>
            <p>PT Pink Services Indonesia</p>
        </div>
    </div>

    <div class="card">
        <h2>Detail</h2>
        <div class="meta">
            <div>No. Kontrak</div><div><b>{{ $contract?->contract_number ?? '-' }}</b></div>
            <div>Customer</div><div>{{ $contract?->customer?->name ?? $contract?->customer?->customer_name ?? '-' }}</div>
            <div>Penandatangan</div><div>{{ $signatureRequest->signer_name }}@if($signatureRequest->signer_position) ({{ $signatureRequest->signer_position }})@endif — {{ $signatureRequest->roleLabel() }}</div>
            @if(! $signatureRequest->isSigned() && $signatureRequest->expires_at)
                <div>Berlaku sampai</div><div>{{ $signatureRequest->expires_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
            @endif
        </div>
    </div>

    @if(! empty($accessDenied))
        <div class="alert alert-warn">{{ $unavailableReason }}</div>
    @elseif($signatureRequest->isSigned())
        <div class="alert alert-ok">
            Dokumen ini sudah Anda tandatangani pada
            <b>{{ $signatureRequest->signed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</b>. Terima kasih.
        </div>
    @elseif($unavailableReason)
        <div class="alert alert-warn">{{ $unavailableReason }}</div>
    @endif

    @if(! $signatureRequest->isRevoked() && empty($accessDenied))
        <div class="card">
            <h2>Dokumen Kontrak</h2>
            <iframe class="doc-frame" src="{{ route('contract-signatures.public.document', $signatureRequest->token) }}" title="Dokumen kontrak"></iframe>
            <div class="row">
                <span class="small">Dokumen tidak tampil? Buka di tab baru.</span>
                <a class="btn btn-light" href="{{ route('contract-signatures.public.document', $signatureRequest->token) }}" target="_blank" rel="noopener noreferrer">Buka Dokumen</a>
            </div>
        </div>
    @endif

    @if(! $signatureRequest->isSigned() && ! $unavailableReason)
        <div class="card" id="signCard">
            <h2>Tanda Tangan</h2>
            <div class="alert alert-err" id="signError"></div>

            <label for="signedName">Nama lengkap</label>
            <input type="text" id="signedName" maxlength="255" value="{{ $signatureRequest->signer_name }}" autocomplete="name">

            <div class="mode-tabs" role="tablist">
                <button type="button" class="active" id="modeDraw" role="tab" aria-selected="true">Gores di layar</button>
                <button type="button" id="modeUpload" role="tab" aria-selected="false">Unggah gambar</button>
            </div>

            <div id="drawPanel">
                <label>Goreskan tanda tangan Anda di kotak berikut</label>
                <div class="pad-box">
                    <canvas id="signaturePad"></canvas>
                    <div class="pad-hint" id="padHint">Tanda tangan di sini</div>
                </div>
                <div class="row" style="justify-content: flex-end;">
                    <button type="button" class="btn btn-light" id="clearPad">Hapus</button>
                </div>
            </div>

            <div id="uploadPanel" style="display: none;">
                <label for="signatureFile">Unggah scan atau foto tanda tangan Anda (JPG/PNG, maks. 5 MB)</label>
                <div class="upload-box">
                    <img id="uploadPreview" alt="Pratinjau tanda tangan">
                    <span class="small">Gunakan gambar tanda tangan di atas kertas putih. Latar putih dibuat transparan otomatis.</span>
                    <input type="file" id="signatureFile" accept="image/png,image/jpeg">
                </div>
            </div>

            <label class="agree">
                <input type="checkbox" id="agree">
                <span>Saya telah membaca dokumen kontrak di atas dan menyetujui seluruh isinya. Setelah ditandatangani, kontrak dinyatakan sah dan tidak dapat diubah.</span>
            </label>

            <button type="button" class="btn btn-primary" id="submitSign" style="width: 100%;">Tanda Tangani Kontrak</button>
        </div>

        <div class="alert alert-ok" id="signDone" style="display: none;"></div>
    @endif

    <p class="small" style="text-align: center; margin-top: 24px;">Tautan ini bersifat pribadi. Jangan teruskan ke pihak lain.</p>
</div>

@if(! $signatureRequest->isSigned() && ! $unavailableReason)
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    const canvas = document.getElementById('signaturePad');
    const hint = document.getElementById('padHint');
    const errorBox = document.getElementById('signError');
    const submitButton = document.getElementById('submitSign');
    const pad = new SignaturePad(canvas, { backgroundColor: 'rgba(0,0,0,0)', penColor: '#0b1f5c' });
    const fileInput = document.getElementById('signatureFile');
    const uploadPreview = document.getElementById('uploadPreview');
    const modeDraw = document.getElementById('modeDraw');
    const modeUpload = document.getElementById('modeUpload');
    let mode = 'draw';
    let uploadedDataUrl = null;

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const data = pad.toData();
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
        pad.fromData(data);
        hint.style.display = pad.isEmpty() ? '' : 'none';
    }

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    pad.addEventListener('beginStroke', () => { hint.style.display = 'none'; });
    document.getElementById('clearPad').addEventListener('click', () => {
        pad.clear();
        hint.style.display = '';
    });

    function showError(message) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function setMode(next) {
        mode = next;
        modeDraw.classList.toggle('active', next === 'draw');
        modeUpload.classList.toggle('active', next === 'upload');
        modeDraw.setAttribute('aria-selected', String(next === 'draw'));
        modeUpload.setAttribute('aria-selected', String(next === 'upload'));
        document.getElementById('drawPanel').style.display = next === 'draw' ? '' : 'none';
        document.getElementById('uploadPanel').style.display = next === 'upload' ? '' : 'none';
        errorBox.style.display = 'none';
        if (next === 'draw') resizeCanvas();
    }

    modeDraw.addEventListener('click', () => setMode('draw'));
    modeUpload.addEventListener('click', () => setMode('upload'));

    // Gambar unggahan diperkecil dan diubah ke PNG di browser; piksel hampir putih dibuat
    // transparan supaya scan di atas kertas tidak menutupi garis tanda tangan di PDF.
    function imageToSignaturePng(img) {
        const scale = Math.min(1, 900 / img.naturalWidth, 360 / img.naturalHeight);
        const out = document.createElement('canvas');
        out.width = Math.max(1, Math.round(img.naturalWidth * scale));
        out.height = Math.max(1, Math.round(img.naturalHeight * scale));
        const ctx = out.getContext('2d');
        ctx.drawImage(img, 0, 0, out.width, out.height);
        const pixels = ctx.getImageData(0, 0, out.width, out.height);
        const d = pixels.data;
        let inked = 0;
        for (let i = 0; i < d.length; i += 4) {
            if (d[i + 3] === 0) continue;
            if (d[i] > 225 && d[i + 1] > 225 && d[i + 2] > 225) {
                d[i + 3] = 0;
            } else {
                inked++;
            }
        }
        ctx.putImageData(pixels, 0, 0);
        return inked > 0 ? out.toDataURL('image/png') : null;
    }

    function rejectFile(message) {
        fileInput.value = '';
        showError(message);
    }

    fileInput.addEventListener('change', () => {
        uploadedDataUrl = null;
        uploadPreview.style.display = 'none';
        errorBox.style.display = 'none';
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        if (!['image/png', 'image/jpeg'].includes(file.type)) return rejectFile('Format gambar harus JPG atau PNG.');
        if (file.size > 5 * 1024 * 1024) return rejectFile('Ukuran gambar maksimal 5 MB.');

        const reader = new FileReader();
        reader.onload = () => {
            const img = new Image();
            img.onload = () => {
                const png = imageToSignaturePng(img);
                if (!png) return rejectFile('Tanda tangan tidak terlihat pada gambar. Gunakan gambar yang lebih jelas.');
                uploadedDataUrl = png;
                uploadPreview.src = png;
                uploadPreview.style.display = 'block';
            };
            img.onerror = () => rejectFile('Gambar tidak bisa dibaca. Silakan pilih file lain.');
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    });

    submitButton.addEventListener('click', async () => {
        errorBox.style.display = 'none';
        const signedName = document.getElementById('signedName').value.trim();

        if (!signedName) return showError('Nama lengkap wajib diisi.');
        if (mode === 'draw' && pad.isEmpty()) return showError('Silakan tanda tangan di kotak yang tersedia.');
        if (mode === 'upload' && !uploadedDataUrl) return showError('Silakan unggah gambar tanda tangan Anda.');
        if (!document.getElementById('agree').checked) return showError('Centang pernyataan persetujuan terlebih dahulu.');

        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan...';

        try {
            const response = await fetch(@json(route('contract-signatures.public.sign', $signatureRequest->token)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    signature: mode === 'upload' ? uploadedDataUrl : pad.toDataURL('image/png'),
                    signature_method: mode,
                    signed_name: signedName,
                    agree: 1
                })
            });
            const result = await response.json().catch(() => ({}));

            if (response.ok && result.status === 'success') {
                document.getElementById('signCard').style.display = 'none';
                const done = document.getElementById('signDone');
                done.textContent = result.message || 'Terima kasih, tanda tangan Anda sudah tersimpan.';
                done.style.display = 'block';
                done.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const firstError = result.errors ? Object.values(result.errors)[0]?.[0] : null;
            showError(firstError || result.message || 'Gagal menyimpan tanda tangan. Silakan coba lagi.');
        } catch (e) {
            showError('Koneksi bermasalah. Silakan coba lagi.');
        }

        submitButton.disabled = false;
        submitButton.textContent = 'Tanda Tangani Kontrak';
    });
})();
</script>
@endif
</body>
</html>
