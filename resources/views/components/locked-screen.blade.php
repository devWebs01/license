<div style="text-align:center;padding:2rem;">
    <div style="font-size:3rem;margin-bottom:1rem;">🔒</div>
    <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:0.5rem;">Akses Diblokir</h2>
    <p style="color:#6b7280;margin-bottom:1rem;">
        Status: <strong>{{ $label }}</strong>
    </p>
    <p style="color:#6b7280;margin-bottom:1.5rem;">
        Hubungi admin: <strong>{{ $adminContact }}</strong>
    </p>
    <div style="display:flex;gap:0.75rem;justify-content:center;">
        <a href="{{ route('licensing.activate') }}" style="padding:0.5rem 1rem;background:#3b82f6;color:white;border-radius:6px;text-decoration:none;font-weight:600;">Aktivasi Ulang</a>
    </div>
</div>
