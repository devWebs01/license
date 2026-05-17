<?php

declare(strict_types=1);

namespace DevWebs01\LicensingClient\Http\Controllers;

use DevWebs01\LicensingClient\Http\Requests\ActivateRequest;
use DevWebs01\LicensingClient\Services\LicenseClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class LicenseWizardController extends Controller
{
    public function __construct(
        private readonly LicenseClientService $licenseService,
    ) {}

    public function showActivate(): View
    {
        return view('licensing::activate');
    }

    public function activate(ActivateRequest $request): RedirectResponse|View
    {
        $result = $this->licenseService->activate($request->validated('license_key'));

        if ($result->success) {
            return redirect('/')->with('success', 'Aktivasi berhasil!');
        }

        return back()->withErrors(['license_key' => $result->message ?? 'Gagal aktivasi']);
    }

    public function showStatus(): View
    {
        return view('licensing::activate', [
            'status' => $this->licenseService->status(),
        ]);
    }

    public function showLocked(): View
    {
        return view('licensing::locked', [
            'reason' => request()->query('reason', 'unknown'),
        ]);
    }
}
