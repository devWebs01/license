# Laravel Licensing Client

**Package:** `devwebs01/laravel-licensing-client`  
**Version:** 1.0.0  
**Filosofi:** *"Simple licensing that is difficult enough to abuse, not impossible to crack."*

---

## Daftar Isi

- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Middleware Protection](#middleware-protection)
- [Facade API](#facade-api)
- [Blade Directives](#blade-directives)
- [Blade Components](#blade-components)
- [Artisan Commands](#artisan-commands)
- [Activation Wizard](#activation-wizard)
- [Offline Grace Period](#offline-grace-period)
- [Security](#security)
- [Testing](#testing)
- [Error Handling](#error-handling)

---

## Instalasi

```bash
composer require devwebs01/laravel-licensing-client
```

Package akan auto-register `LicensingClientServiceProvider` via Laravel package discovery.

### Publish Konfigurasi

```bash
php artisan vendor:publish --provider="DevWebs01\LicensingClient\LicensingClientServiceProvider"
```

### Publish Views (opsional)

```bash
php artisan vendor:publish --tag=licensing-client-views
```

---

## Konfigurasi

### Environment Variables

```env
LICENSING_SERVER_URL=https://monitor.example.com
LICENSING_KEY=XXXX-XXXX-XXXX-XXXX
LICENSING_APP_NAME=MyApp
LICENSING_ENV=production
LICENSING_CACHE_TTL=3600
LICENSING_GRACE_DAYS=7
LICENSING_TIMEOUT=10
LICENSING_ADMIN_CONTACT=admin@company.com
LICENSING_DEV_BYPASS=false
```

### Config File

Default konfigurasi di `config/licensing-client.php`:

| Key | Default | Deskripsi |
|-----|---------|-----------|
| `server_url` | `LICENSING_SERVER_URL` | URL License Monitor server |
| `license_key` | `LICENSING_KEY` | License key aplikasi |
| `app_name` | `APP_NAME` | Nama aplikasi untuk display |
| `environment` | `APP_ENV` | Environment (`local` bypasses middleware) |
| `cache.store` | `CACHE_STORE` → `file` | Cache store untuk token |
| `cache.ttl_seconds` | 3600 | Interval revalidation online |
| `grace_days` | 7 | Grace period setelah last validation |
| `timeout` | 10 | HTTP client timeout (detik) |
| `route_prefix` | `licensing` | Prefix route licensing |
| `excluded_routes` | `login, register, password/*, licensing/*` | Routes yang dikecualikan dari middleware |
| `admin_contact` | `admin@company.com` | Kontak admin di lock screen |
| `dev_bypass` | `false` | Force bypass middleware di local |

---

## Middleware Protection

Daftarkan middleware di `app/Http/Kernel.php` atau di route:

```php
// Route group
Route::middleware('license')->group(function () {
    // protected routes...
});

// Atau di controller constructor
public function __construct()
{
    $this->middleware('license');
}
```

### 3-Step Lifecycle

```
Request → CheckLicenseMiddleware
    │
    ├── Step 1: CEK CACHE
    │     ├── Cache valid + offline_until future?
    │     │   └── ✅ LANJUTKAN
    │     └── Cache tidak ada/tidak valid?
    │         └── Step 2
    │
    ├── Step 2: VALIDASI ONLINE
    │     ├── POST /api/v1/validate
    │     │   ├── ✅ Sukses → simpan cache → LANJUTKAN
    │     │   ├── ❌ License invalid → 🚫 LOCK
    │     │   └── ❌ Network error → Step 3
    │
    └── Step 3: GRACE PERIOD
          ├── Cache ada + dalam grace period?
          │   └── ✅ LANJUTKAN + flash warning
          └── Cache expired/tidak ada
              └── 🚫 REDIRECT ke activation wizard
```

### Excluded Routes

Routes berikut dikecualikan secara default:

```
licensing/*
login
register
password/*
```

### Grace Warning Flash

Saat dalam grace period (≤ 3 hari), middleware menambahkan flash message:

```php
session('license_warning')
```

---

## Facade API

```php
use DevWebs01\LicensingClient\Facades\LicenseClient;
```

| Method | Return | Deskripsi |
|--------|--------|-----------|
| `isValid()` | `bool` | Cek apakah lisensi valid (cache) |
| `info()` | `LicenseInfo` | Informasi lisensi lengkap |
| `activate(string $key)` | `ActivationResult` | Aktivasi license key |
| `verifyActivation(string $code)` | `bool` | Verifikasi activation code |
| `refresh()` | `bool` | Force validasi online |
| `deactivate()` | `bool` | Deaktivasi device |
| `hasFeature(string $feature)` | `bool` | Cek feature flag |

### Contoh

```php
// Cek status
$info = LicenseClient::info();
if ($info->isValid) {
    echo "Lisensi aktif — {$info->product}";
}

// Aktivasi
$result = LicenseClient::activate('XXXX-XXXX-XXXX-XXXX');
if ($result->success) {
    // redirect ke dashboard
} elseif ($result->requiresApproval) {
    // tampilkan activation code: {$result->activationCode}
}

// Cek fitur
if (LicenseClient::hasFeature('reports')) {
    // tampilkan menu laporan
}

// Validasi paksa
LicenseClient::refresh();
```

### Value Objects

**`LicenseInfo`:**

| Property | Type | Deskripsi |
|----------|------|-----------|
| `isValid` | `bool` | Status validasi |
| `status` | `LicenseStatus` | Status enum (active/suspended/expired/revoked/not_activated) |
| `offlineUntil` | `?string` | Batas offline grace period |
| `isWithinGracePeriod` | `bool` | Dalam masa grace? |
| `graceDaysRemaining` | `int` | Sisa hari grace |
| `product` | `?string` | Nama produk |
| `cachedAt` | `?string` | Waktu cache terakhir |
| `requiresOnlineRefresh` | `bool` | Perlu refresh online? |

**`ActivationResult`:**

| Property | Type | Deskripsi |
|----------|------|-----------|
| `success` | `bool` | Berhasil? |
| `requiresApproval` | `bool` | Butuh approval admin? |
| `activationCode` | `?string` | Kode aktivasi (jika butuh approval) |
| `offlineUntil` | `?string` | Batas offline (jika auto-activated) |
| `message` | `?string` | Pesan dari server |

**`ValidationResult`:**

| Property | Type | Deskripsi |
|----------|------|-----------|
| `valid` | `bool` | Lisensi valid? |
| `status` | `LicenseStatus` | Status lisensi |
| `offlineUntil` | `?string` | Batas offline |
| `product` | `?string` | Nama produk |
| `expiresAt` | `?string` | Tanggal expired |
| `maxDevices` | `int` | Maksimal device |
| `devicesCount` | `int` | Jumlah device terdaftar |
| `message` | `?string` | Pesan server |

---

## Blade Directives

```blade
@licensed
    {{-- Konten hanya untuk lisensi aktif --}}
    <p>Selamat datang di aplikasi</p>
@endlicensed

@feature('reports')
    {{-- Konten untuk fitur tertentu --}}
    <flux:button>Lihat Laporan</flux:button>
@endfeature

@licenseWarning
    {{-- Grace period warning --}}
    <x-licensing::countdown-warning />
@endlicenseWarning
```

### Helper Functions

```php
license_is_valid()           // bool
license_has_feature('pos')   // bool
license_info()               // ?LicenseInfo
```

---

## Blade Components

```blade
{{-- Grace period countdown --}}
<x-licensing::countdown-warning />

{{-- Status badge di sidebar --}}
<x-licensing::status-badge />

{{-- Lock screen --}}
<x-licensing::locked-screen />
```

Customize tampilan komponen dengan publish views:

```bash
php artisan vendor:publish --tag=licensing-client-views
```

---

## Artisan Commands

### license:activate

```bash
php artisan license:activate XXXX-XXXX-XXXX-XXXX
```

Aktivasi license key dari command line. Berguna untuk headless server atau provisioning.

### license:status

```bash
php artisan license:status
```

Output:

```
License Status
──────────────
Status:         Active
Product:        Laravel POS
License Key:    XXXX-****-XXXX-XXXX
Offline Until:  2026-05-23
Cache Age:      2 hours
Device:         a3f2b8c1...64chars... (Server Production)
Features:       pos, reports, users
Grace Days:     7
```

---

## Activation Wizard

### Routes

| Method | URL | Deskripsi |
|--------|-----|-----------|
| GET | `/licensing/activate` | Wizard aktivasi |
| POST | `/licensing/activate` | Submit license key |
| POST | `/licensing/poll` | Polling approval status (AJAX) |
| GET | `/licensing/status` | Status lisensi saat ini |
| GET | `/licensing/locked` | Lock screen (expired/suspended/revoked) |
| POST | `/licensing/retry` | Force revalidation dari lock screen |

### Flow

1. **Welcome** — Informasi aplikasi butuh lisensi, tombol "Mulai Aktivasi"
2. **Device Detection** — Menampilkan fingerprint, OS, hostname untuk verifikasi
3. **License Key Input** — Form input dengan validasi format
4. **Processing** — Call server, simpan token
   - ✅ Auto-activated → redirect ke dashboard
   - ⏳ Butuh approval → polling tiap 5 detik
   - ❌ Error → tampilkan pesan error

---

## Offline Grace Period

Aplikasi tetap berjalan ketika server lisensi tidak reachable.

### Cara Kerja

```
offline_until = MAX(
    offline_until_dari_aktivasi,
    last_validate + grace_days
)
```

Setiap kali `validateOnline()` sukses, `offline_until` diperpanjang. Grace period default: **7 hari**.

### App States

| State | UI | Behavior |
|-------|----|----------|
| **Active** | Normal | Semua fitur berjalan |
| **Grace Warning** | Countdown banner (≤ 3 hari) | Semua fitur berjalan |
| **Grace Expired** | Lock screen | Hanya halaman licensing |
| **Suspended** | Lock screen + alasan | Blok total |
| **Revoked** | Lock screen + kontak admin | Blok total |
| **Not Activated** | Redirect ke wizard | Tidak ada akses |

---

## Security

### Encrypted Token

Token disimpan di cache dalam bentuk terenkripsi:

```php
$encrypted = Crypt::encryptString(json_encode($token));
```

### HMAC Integrity

Setiap token memiliki HMAC untuk deteksi korupsi:

```php
$payload = $token['license_key'] . $token['fingerprint'] . $token['offline_until'];
$hmac = hash_hmac('sha256', $payload, $this->getHmacSecret());
```

### Clock Tampering Protection

```php
$elapsedSinceCached = now()->diffInSeconds($cachedAt);
$elapsedSinceServer = $serverTime->diffInSeconds($cachedAt);
$clockDrift = abs($elapsedSinceCached - $elapsedSinceServer);

if ($clockDrift > 3600) {  // > 1 jam drift
    throw new ClockDriftDetectedException;
}
```

### Device Fingerprinting

Fingerprint dibuat dari kombinasi: hostname, OS + kernel, app path (resolved), database name, PHP version, dan hash APP_KEY.

Jika fingerprint berubah (misal server migration), device perlu diaktivasi ulang — proses yang sama dengan aktivasi pertama.

---

## Testing

### Unit Tests

```bash
vendor/bin/phpunit
```

### Test Structure

```
tests/
├── TestCase.php                              # Base test case (Orchestra Testbench)
├── Unit/
│   ├── LicenseClientServiceTest.php          # Service layer
│   ├── LicenseCacheServiceTest.php           # Cache + encryption + HMAC
│   ├── FingerprintCollectorTest.php          # Device fingerprint
│   └── ValueObjectsTest.php                  # Value objects
└── Feature/
    ├── CheckLicenseMiddlewareTest.php        # Middleware states
    ├── ActivationWizardTest.php              # Full activation flow
    └── LicenseActivateCommandTest.php        # Artisan commands
```

### Mock Strategy

```php
Http::fake([
    'monitor.test/api/v1/validate' => Http::response([
        'success' => true,
        'data' => [
            'valid' => true,
            'status' => 'active',
            'offline_until' => now()->addDays(7)->toIso8601String(),
            'features' => ['pos', 'reports'],
        ],
    ]),
]);
```

---

## Error Handling

| Error | Client Action | User Message |
|-------|--------------|--------------|
| Server 404 (invalid key) | Tampilkan error | "Kunci lisensi tidak valid" |
| Server 403 (suspended) | Hapus cache, lock | "Lisensi ditangguhkan, hubungi admin" |
| Server 403 (expired) | Hapus cache, lock | "Lisensi telah kedaluwarsa" |
| Network timeout | Fallback grace period | "Server lisensi tidak reachable" |
| Corrupted cache | Hapus cache, force online | (silent recovery) |
| Clock drift detected | Force online refresh | "Waktu server tidak sinkron" |

### Recovery Flow

```
Error terjadi
    ↓
Ada cache valid?
    ├── Ya → grace period → lanjut
    └── Tidak → redirect ke activation wizard
         ↓
User bisa:
    ├── Call admin untuk aktivasi manual
    ├── Cek koneksi internet
    └── Input ulang license key
```

---

## Package Structure

```
src/
├── Commands/
│   ├── LicenseActivateCommand.php       # license:activate
│   └── LicenseStatusCommand.php         # license:status
├── Components/
│   ├── CountdownWarning.php             # Blade component
│   ├── StatusBadge.php                  # Blade component
│   └── LockedScreen.php                 # Blade component
├── Enums/
│   └── LicenseStatus.php                # Status enum
├── Exceptions/
│   ├── LicenseNotActivatedException.php
│   ├── LicenseExpiredException.php
│   ├── LicenseSuspendedException.php
│   ├── ServerUnreachableException.php
│   ├── ClockDriftDetectedException.php
│   └── CorruptedTokenException.php
├── Facades/
│   └── LicenseClient.php                # Facade
├── Http/Middleware/
│   └── CheckLicenseMiddleware.php       # Middleware
├── Services/
│   ├── LicenseClientService.php         # Main service
│   ├── LicenseCacheService.php          # Cache + encryption
│   └── FingerprintCollector.php         # Device fingerprint
├── ValueObjects/
│   ├── ActivationResult.php
│   ├── ValidationResult.php
│   └── LicenseInfo.php
├── BladeDirectives.php                  # @licensed, @feature, @licenseWarning
├── LicensingClientServiceProvider.php   # Service provider
└── helpers.php                          # Helper functions
```

---

## Environment Support

| Dependency | Version |
|-----------|---------|
| PHP | ^8.1 |
| `laravel/framework` | ^10.0 \| ^11.0 \| ^12.0 \| ^13.0 |
| `guzzlehttp/guzzle` | ^7.0 |

Zero database migration — semua state disimpan di cache (encrypted token).

---

## License

Proprietary. Internal use.
