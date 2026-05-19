<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aktivasi Lisensi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 2rem; width: 100%; max-width: 480px; margin: 1rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        p { color: #6b7280; margin-bottom: 1.5rem; line-height: 1.5; }
        .device-info { background: #f9fafb; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .device-info dt { font-weight: 600; color: #374151; margin-top: 0.5rem; }
        .device-info dt:first-child { margin-top: 0; }
        .device-info dd { color: #6b7280; word-break: break-all; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; font-family: monospace; text-align: center; letter-spacing: 2px; }
        input:focus { outline: none; border-color: #3b82f6; ring: 2px solid #3b82f6; }
        .error { color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; }
        button { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 1rem; }
        button:hover { background: #2563eb; }
        .info-box { background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; font-size: 0.875rem; color: #1e40af; }
        .step { display: none; }
        .step.active { display: block; }
    </style>
</head>
<body>
    <div class="card">
        @php $hasConfig = config('licensing-client.github_raw_base'); @endphp

        @if (!$hasConfig)
            <div class="info-box" style="background:#fef3c7;border-color:#f59e0b;color:#92400e;margin-bottom:1rem;">
                <p><strong>Perhatian:</strong> Lisensi belum dikonfigurasi. Tambahkan <code style="background:#fef9c3;padding:0.125rem 0.375rem;border-radius:4px;font-size:0.75rem;">LICENSING_GITHUB_RAW</code> di file <code style="background:#fef9c3;padding:0.125rem 0.375rem;border-radius:4px;font-size:0.75rem;">.env</code>. Jalankan <code style="background:#fef9c3;padding:0.125rem 0.375rem;border-radius:4px;font-size:0.75rem;">php artisan license:check</code> untuk diagnose.</p>
            </div>
        @endif

        @if(isset($status))
            <h1>Status Lisensi</h1>
            <div class="info-box">
                <p><strong>Status:</strong> {{ $status->status->label() }}</p>
                <p><strong>Valid:</strong> {{ $status->isValid ? 'Ya' : 'Tidak' }}</p>
                @if($status->product)
                    <p><strong>Product:</strong> {{ $status->product }}</p>
                @endif
                @if($status->graceDaysRemaining > 0)
                    <p><strong>Grace Period:</strong> {{ $status->graceDaysRemaining }} hari</p>
                @endif
            </div>
            <button onclick="window.location='/'">Kembali ke Dashboard</button>

        @else
            <h1>Aktivasi Lisensi</h1>
            <p>Masukkan license key yang Anda dapatkan dari admin untuk mengaktifkan aplikasi.</p>

            <dl class="device-info">
                <dt>Hostname</dt>
                <dd>{{ php_uname('n') }}</dd>
                <dt>Sistem Operasi</dt>
                <dd>{{ php_uname('s') }} {{ php_uname('r') }}</dd>
                <dt>Fingerprint</dt>
                <dd style="font-family:monospace;font-size:0.75rem;">{{ app(\DevWebs01\LicensingClient\Services\FingerprintCollector::class)->fingerprint() }}</dd>
            </dl>

            @if(isset($errors) && $errors->any())
                <div class="error">{{ $errors->first('license_key') }}</div>
            @endif

            <form method="POST" action="{{ route('licensing.activate') }}">
                @csrf
                <label for="license_key">License Key</label>
                <input type="text" id="license_key" name="license_key" placeholder="XXXX-XXXX-XXXX-XXXX" pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}" maxlength="19" autocomplete="off" required>
                <button type="submit">Aktivasi</button>
            </form>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var input = document.getElementById('license_key');
            if (input) {
                input.addEventListener('input', function(e) {
                    var val = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                    var parts = [];
                    for (var i = 0; i < val.length && parts.length < 4; i += 4) {
                        parts.push(val.substr(i, 4));
                    }
                    this.value = parts.join('-');
                });
            }
        });
    </script>
</body>
</html>
