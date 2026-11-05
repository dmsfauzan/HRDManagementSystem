<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'HRIS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">
<div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
    <aside class="hidden lg:flex lg:flex-shrink-0">
        <div class="flex flex-col w-64 border-r border-slate-200 bg-white">
            <div class="flex items-center gap-3 h-16 px-6 border-b border-slate-100">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-600 text-white font-bold text-sm">HR</div>
                <div>
                    <p class="text-sm font-semibold leading-none text-slate-900">HRIS</p>
                    <p class="text-[11px] tracking-wide text-slate-500">HRD Management</p>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold tracking-widest text-slate-400">UTAMA</p>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>
                </div>
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold tracking-widest text-slate-400">ORGANISASI</p>
                    <div class="space-y-1">
                        @can('viewAny', \App\Models\Employee::class)
                            <a href="{{ route('employees.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('employees.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V4H2v16h5m10 0v-2a3 3 0 00-3-3H10a3 3 0 00-3 3v2m8 0H9m0 0V9a2 2 0 012-2h2a2 2 0 012 2v11z"/></svg>
                                Karyawan
                            </a>
                            <a href="{{ route('employees.org-chart') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('employees.org-chart') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l3-3 3 2 4-5"/></svg>
                                Struktur
                            </a>
                        @endcan
                        @can('viewAny', \App\Models\Department::class)
                            <a href="{{ route('departments.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('departments.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l7-3 7 3z"/></svg>
                                Departemen
                            </a>
                        @endcan
                        @can('viewAny', \App\Models\Position::class)
                            <a href="{{ route('positions.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('positions.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Jabatan
                            </a>
                        @endcan
                        @cannot('viewAny', \App\Models\Employee::class)
                            @cannot('viewAny', \App\Models\Department::class)
                                @cannot('viewAny', \App\Models\Position::class)
                                    @if(Auth::user()?->employee)
                                        <a href="{{ route('employees.show', Auth::user()->employee) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('employees.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            Profil Saya
                                        </a>
                                    @endif
                                @endcannot
                            @endcannot
                        @endcannot
                    </div>
                </div>
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold tracking-widest text-slate-400">KEHADIRAN & CUTI</p>
                    <div class="space-y-1">
                        @can('viewAny', \App\Models\AttendanceRecord::class)
                            <a href="{{ route('attendance.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('attendance.index') || request()->routeIs('attendance.show') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Absensi
                            </a>
                        @endcan
                        @can('viewAny', \App\Models\AttendanceCorrection::class)
                            <a href="{{ route('attendance.corrections.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('attendance.corrections.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Koreksi Absensi
                            </a>
                        @endcan
                        @can('viewAny', \App\Models\Overtime::class)
                            <a href="{{ route('overtime.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('overtime.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Lembur
                            </a>
                        @endcan
                        @if(auth()->user()->employee || auth()->user()->can('attendance.manage') || auth()->user()->hasRole('manager'))
                            <a href="{{ route('shift-swaps.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('shift-swaps.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                Tukar Shift
                            </a>
                        @endif
                        @can('viewAny', \App\Models\LeaveRequest::class)
                            <a href="{{ route('leave.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('leave.index') || request()->routeIs('leave.show') || request()->routeIs('leave.create') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z"/></svg>
                                Cuti
                            </a>
                            <a href="{{ route('leave.calendar') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('leave.calendar') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z"/></svg>
                                Kalender Cuti
                            </a>
                        @endcan
                        @if(auth()->user()->can('leave.approve') || auth()->user()->can('leave.manage'))
                            <a href="{{ route('leave.approvals') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('leave.approvals') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Persetujuan Cuti
                            </a>
                        @endif
                        @if(auth()->user()->can('leave.balances_manage') || auth()->user()->employee)
                            <a href="{{ route('leave.balances.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('leave.balances.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                Saldo Cuti
                            </a>
                        @endif
                        @can('leave.manage')
                            <a href="{{ route('leave.types.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('leave.types.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                Jenis Cuti
                            </a>
                        @endcan
                        @hasanyrole('manager|hrd_admin|super_admin')
                        <a href="{{ route('attendance.corrections.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('attendance.corrections.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Persetujuan Koreksi
                        </a>
                        @endhasanyrole
                    </div>
                </div>
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold tracking-widest text-slate-400">REKRUTMEN</p>
                    <div class="space-y-1">
                        @can('recruitment.view')
                            <a href="{{ route('recruitment.postings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('recruitment.postings.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Lowongan
                            </a>
                            <a href="{{ route('recruitment.applications.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('recruitment.applications.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V4H2v16h5m10 0v-2a3 3 0 00-3-3H10a3 3 0 00-3 3v2m8 0H9"/></svg>
                                Pelamar
                            </a>
                            <a href="{{ route('recruitment.applications.kanban') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('recruitment.applications.kanban') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h4v12H4zM10 6h4v8h-4zM16 6h4v6h-4z"/></svg>
                                Pipeline
                            </a>
                            @can('recruitment.manage')
                                <a href="{{ route('settings.company.edit') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('settings.company.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l7-3 7 3z"/></svg>
                                    Profil Perusahaan
                                </a>
                            @endcan
                        @endcan
                    </div>
                </div>
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold tracking-widest text-slate-400">PENGATURAN KEHADIRAN</p>
                    <div class="space-y-1">
                        @hasanyrole('hrd_admin|super_admin')
                        <a href="{{ route('work-schedules.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('work-schedules.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z"/></svg>
                            Jadwal Kerja
                        </a>
                        <a href="{{ route('roster.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('roster.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h10M4 18h7"/></svg>
                            Roster Shift
                        </a>
                        <a href="{{ route('holidays.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('holidays.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            Hari Libur
                        </a>
                        @endhasanyrole
                        <a href="{{ route('announcements.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('announcements.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            Pengumuman
                        </a>
                        <a href="{{ route('documents.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('documents.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Dokumen
                        </a>
                    </div>
                </div>
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold tracking-widest text-slate-400">LAINNYA</p>
                    <div class="space-y-1">
                        @hasanyrole('hrd_admin|super_admin')
                        <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('reports.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Laporan
                        </a>
                        <a href="{{ route('trash.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('trash.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            Arsip
                        </a>
                        @endhasanyrole
                        @role('super_admin')
                        <a href="{{ route('audit.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('audit.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Audit Log
                        </a>
                        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            Pengguna & Role
                        </a>
                        @endrole
                    </div>
                </div>
            </nav>
            <div class="p-3 border-t border-slate-100">
                <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-50">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-semibold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium truncate text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs truncate text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <div x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" x-transition.opacity>
        <div @click="sidebarOpen=false" class="absolute inset-0 bg-slate-900/40"></div>
        <div class="relative flex flex-col w-64 h-full bg-white border-r border-slate-200">
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">HR</div>
                    <span class="text-sm font-semibold">HRIS</span>
                </div>
                <button @click="sidebarOpen=false" class="p-2 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600' }}">Dashboard</a>
                @can('viewAny', \App\Models\AttendanceRecord::class)
                    <a href="{{ route('attendance.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('attendance.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600' }}">Absensi</a>
                @endcan
                @can('viewAny', \App\Models\AttendanceCorrection::class)
                    <a href="{{ route('attendance.corrections.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('attendance.corrections.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600' }}">Koreksi</a>
                @endcan
                @hasanyrole('hrd_admin|super_admin')
                    <a href="{{ route('work-schedules.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('work-schedules.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600' }}">Jadwal</a>
                    <a href="{{ route('holidays.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('holidays.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600' }}">Hari Libur</a>
                @endhasanyrole
                @can('viewAny', \App\Models\User::class)
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('users.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600' }}">Pengguna</a>
                @endcan
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-600">Profil</a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf<button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50">Keluar</button></form>
            </nav>
        </div>
    </div>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen=true" class="lg:hidden p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        @isset($header)
                            <div class="text-sm font-semibold text-slate-900">{{ $header }}</div>
                        @else
                            <div class="text-sm font-semibold text-slate-900">Dashboard</div>
                        @endisset
                        <p class="text-xs text-slate-500 hidden sm:block">{{ now()->timezone(tz())->translatedFormat('l, d F Y') }} &middot; WIB</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-search-palette />
                    @php($unreadCount = auth()->user()->unreadNotifications->count())
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open=!open" class="relative p-2 rounded-full hover:bg-slate-100" aria-label="Notifikasi">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1 right-1 flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-semibold text-white bg-red-500 rounded-full">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </button>
                        <div x-show="open" @click.outside="open=false" x-transition class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 py-2">
                            <div class="flex items-center justify-between px-4 pb-2 border-b border-slate-100">
                                <span class="text-sm font-semibold text-slate-800">Notifikasi</span>
                                @if($unreadCount > 0)
                                    <form method="POST" action="{{ route('notifications.readAll') }}">@csrf @method('PATCH')<button type="submit" class="text-xs text-emerald-700 hover:underline">Tandai semua dibaca</button></form>
                                @endif
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                @forelse(auth()->user()->notifications()->latest()->take(8)->get() as $notif)
                                    <a href="{{ route('notifications.index') }}" class="flex items-start gap-2 px-4 py-3 hover:bg-slate-50">
                                        <span class="mt-1.5 w-2 h-2 rounded-full {{ $notif->read_at ? 'bg-slate-300' : 'bg-emerald-500' }}"></span>
                                        <span class="min-w-0">
                                            <span class="block text-sm text-slate-700">{{ $notif->data['message'] ?? 'Notifikasi baru' }}</span>
                                            <span class="block text-xs text-slate-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                @empty
                                    <p class="px-4 py-6 text-center text-sm text-slate-500">Belum ada notifikasi</p>
                                @endforelse
                            </div>
                            <div class="px-4 pt-2 border-t border-slate-100">
                                <a href="{{ route('notifications.index') }}" class="block text-center text-xs text-emerald-700 hover:underline">Lihat semua notifikasi</a>
                            </div>
                        </div>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        {{ auth()->user()->getRoleNames()->first() ? \Illuminate\Support\Str::headline(auth()->user()->getRoleNames()->first()) : 'Pengguna' }}
                    </span>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open=!open" class="flex items-center gap-2 p-1.5 rounded-full hover:bg-slate-100">
                            @if(auth()->user()->avatar_path)
                                <img src="{{ route('profile.avatar') }}" alt="" class="w-8 h-8 rounded-full object-cover">
                            @else
                                <span class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-semibold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                            @endif
                            <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill="currentColor" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 py-1">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profil</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Keluar</button></form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        @if (session('success') || session('error'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                class="fixed bottom-4 right-4 z-50 w-full max-w-sm space-y-2" role="status" aria-live="polite">
                @if (session('success'))
                    <div class="flex items-start gap-2 px-4 py-3 rounded-lg bg-emerald-600 text-white shadow-lg text-sm">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="flex-1">{{ session('success') }}</span>
                        <button type="button" @click="show = false" class="opacity-70 hover:opacity-100" aria-label="Tutup">&times;</button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="flex items-start gap-2 px-4 py-3 rounded-lg bg-red-600 text-white shadow-lg text-sm">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="flex-1">{{ session('error') }}</span>
                        <button type="button" @click="show = false" class="opacity-70 hover:opacity-100" aria-label="Tutup">&times;</button>
                    </div>
                @endif
            </div>
        @endif

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>

        <footer class="px-6 py-4 border-t border-slate-200 bg-white text-xs text-slate-500">
            &copy; {{ date('Y') }} HRIS — HRD Management System. Zona waktu Asia/Jakarta.
        </footer>
    </div>
</div>
</body>
</html>
