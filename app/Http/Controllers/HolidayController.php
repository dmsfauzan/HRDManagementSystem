<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\Holiday;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HolidayController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        abort_unless(auth()->user()->can('leave.manage'), 403);

        $holidays = Holiday::orderByDesc('date')->paginate(20);

        return view('holidays.index', compact('holidays'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('leave.manage'), 403);

        return view('holidays.create');
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        $holiday = Holiday::create($request->validated() + ['is_national' => $request->boolean('is_national', true)]);
        AuditLogger::log('holiday.created', $holiday, [], ['date' => $holiday->date->toDateString(), 'name' => $holiday->name]);

        return redirect()->route('holidays.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(Holiday $holiday): View
    {
        abort_unless(auth()->user()->can('leave.manage'), 403);

        return view('holidays.edit', compact('holiday'));
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated() + ['is_national' => $request->boolean('is_national', true)]);
        AuditLogger::log('holiday.updated', $holiday, [], ['date' => $holiday->date->toDateString(), 'name' => $holiday->name]);

        return redirect()->route('holidays.index')->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        abort_unless(auth()->user()->can('leave.manage'), 403);

        AuditLogger::log('holiday.deleted', $holiday, ['date' => $holiday->date->toDateString(), 'name' => $holiday->name], []);
        $holiday->delete();

        return redirect()->route('holidays.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}
