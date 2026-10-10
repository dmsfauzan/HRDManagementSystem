<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOwnProfileRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SelfServiceController extends Controller
{
    public function edit(): View
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 403);

        return view('self-service.edit', compact('employee'));
    }

    public function update(UpdateOwnProfileRequest $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        $before = $employee->only(['phone', 'address', 'birth_date', 'gender']);
        $employee->update($request->validated());

        AuditLogger::log('employee.self_updated', $employee, $before, $employee->only(['phone', 'address', 'birth_date', 'gender']));

        return back()->with('success', 'Data pribadi berhasil diperbarui.');
    }
}
