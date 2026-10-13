<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()->can('recruitment.manage'), 403);

        return view('settings.company', ['company' => CompanyProfile::current()]);
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $company = CompanyProfile::current();
        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('private')->exists($company->logo_path)) {
                Storage::disk('private')->delete($company->logo_path);
            }
            $ext = $request->file('logo')->getClientOriginalExtension() ?: 'png';
            $path = 'company/logo.'.$ext;
            Storage::disk('private')->put($path, file_get_contents($request->file('logo')->getRealPath()));
            $data['logo_path'] = $path;
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $company->update($data);

        AuditLogger::log('company_profile.updated', $company, [], ['name' => $company->name]);

        return back()->with('success', 'Profil perusahaan berhasil disimpan.');
    }

    public function logo(): StreamedResponse
    {
        $company = CompanyProfile::current();

        abort_unless($company->logo_path && Storage::disk('private')->exists($company->logo_path), 404);

        return Storage::disk('private')->response($company->logo_path);
    }
}
