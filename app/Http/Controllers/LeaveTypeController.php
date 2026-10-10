<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        abort_unless(auth()->user()->can('leave.manage'), 403);

        $types = LeaveType::orderBy('name')->paginate(20);

        return view('leave.types', compact('types'));
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_paid'] = $request->boolean('is_paid', true);
        $data['tracks_balance'] = $request->boolean('tracks_balance', true);
        $data['requires_attachment'] = $request->boolean('requires_attachment', false);
        $data['is_active'] = $request->boolean('is_active', true);

        LeaveType::create($data);

        return back()->with('success', 'Jenis cuti berhasil ditambahkan.');
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $type): RedirectResponse
    {
        $data = $request->validated();
        $data['is_paid'] = $request->boolean('is_paid', true);
        $data['tracks_balance'] = $request->boolean('tracks_balance', true);
        $data['requires_attachment'] = $request->boolean('requires_attachment', false);
        $data['is_active'] = $request->boolean('is_active', true);

        $type->update($data);

        return back()->with('success', 'Jenis cuti berhasil diperbarui.');
    }
}
