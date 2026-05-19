<?php

declare(strict_types=1);

namespace DevWebs01\LicensingClient\Commands;

use DevWebs01\LicensingClient\Services\FingerprintCollector;
use DevWebs01\LicensingClient\Services\LicenseCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

final class LicenseCheckCommand extends Command
{
    protected $signature = 'license:check';

    protected $description = 'Periksa konfigurasi dan konektivitas lisensi';

    public function __construct(
        private readonly LicenseCacheService $cacheService,
        private readonly FingerprintCollector $fingerprint,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $passed = true;

        $this->components->twoColumnDetail('<fg=yellow>Memeriksa Konfigurasi</>', '');

        $githubRawBase = config('licensing-client.github_raw_base');
        if ($githubRawBase) {
            $this->components->twoColumnDetail('GitHub Raw Base URL', $githubRawBase);
        } else {
            $this->components->twoColumnDetail('GitHub Raw Base URL', '<fg=red>TIDAK DIKONFIGURASI</>');
            $passed = false;
        }

        $licenseKey = config('licensing-client.license_key');
        if ($licenseKey) {
            $this->components->twoColumnDetail('License Key', substr($licenseKey, 0, 8).'...');
        } else {
            $this->components->twoColumnDetail('License Key', '<fg=red>TIDAK DIKONFIGURASI</>');
            $passed = false;
        }

        $this->components->twoColumnDetail('Environment', config('licensing-client.environment', 'production'));

        $this->components->twoColumnDetail('', '');

        $this->components->twoColumnDetail('<fg=yellow>Memeriksa Koneksi GitHub</>', '');

        if ($githubRawBase && $licenseKey) {
            try {
                $hash = sha1($licenseKey);
                $url = rtrim($githubRawBase, '/')."/licenses/{$hash}.json";
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    $status = $data['status'] ?? 'unknown';
                    $this->components->twoColumnDetail('License Status', "<fg=green>{$status}</>");
                } elseif ($response->status() === 404) {
                    $this->components->twoColumnDetail('License File', '<fg=yellow>Tidak ditemukan (404)</>');
                } else {
                    $this->components->twoColumnDetail('GitHub Response', '<fg=red>Gagal ('.$response->status().')</>');
                    $passed = false;
                }
            } catch (\Throwable $e) {
                $this->components->twoColumnDetail('GitHub Connection', '<fg=red>Tidak dapat dijangkau</>');
                $this->components->twoColumnDetail('Error', $e->getMessage());
                $passed = false;
            }
        }

        $this->components->twoColumnDetail('', '');

        $this->components->twoColumnDetail('<fg=yellow>Memeriksa Cache</>', '');

        $hasToken = $this->cacheService->hasToken();
        $this->components->twoColumnDetail('Token Tersimpan', $hasToken ? '<fg=green>Ya</>' : '<fg=yellow>Tidak</>');

        if ($hasToken) {
            $token = $this->cacheService->retrieveToken();
            if ($token) {
                $offlineUntil = $token['offline_until'] ?? null;
                $cachedAt = $token['cached_at'] ?? null;
                $this->components->twoColumnDetail('Offline Until', $offlineUntil ?? '-');
                $this->components->twoColumnDetail('Cached At', $cachedAt ?? '-');
            } else {
                $this->components->twoColumnDetail('Token Integrity', '<fg=red>Rusak</>');
                $passed = false;
            }
        }

        $this->components->twoColumnDetail('', '');

        $this->components->twoColumnDetail('<fg=yellow>Memeriksa Perangkat</>', '');
        $fingerprint = $this->fingerprint->fingerprint();
        $this->components->twoColumnDetail('Fingerprint', $fingerprint);
        $this->components->twoColumnDetail('Hostname', php_uname('n'));

        $this->line('');

        if ($passed) {
            $this->components->success('Semua pemeriksaan berhasil.');

            return self::SUCCESS;
        }

        $this->components->error('Beberapa pemeriksaan gagal. Perbaiki konfigurasi dan coba lagi.');

        return self::FAILURE;
    }
}
