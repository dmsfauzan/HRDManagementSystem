<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function index(): View
    {
        return view('docs.api', ['endpoints' => $this->endpoints()]);
    }

    public function openapi(): JsonResponse
    {
        $paths = [];

        foreach ($this->endpoints() as $endpoint) {
            $method = strtolower($endpoint['method']);
            $path = str_replace('/api', '', $endpoint['path']);

            $operation = [
                'summary' => $endpoint['title'],
                'description' => $endpoint['description'],
                'tags' => [$endpoint['group']],
                'responses' => [
                    '200' => ['description' => 'Berhasil'],
                    '401' => ['description' => 'Token tidak valid'],
                ],
            ];

            if (! empty($endpoint['auth'])) {
                $operation['security'] = [['bearerAuth' => []]];
            }

            if (! empty($endpoint['body'])) {
                $operation['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => ['schema' => ['type' => 'object', 'example' => $endpoint['body']]],
                    ],
                ];
            }

            $paths[$path][$method] = $operation;
        }

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'HRIS API',
                'version' => '1.0.0',
                'description' => 'REST API HRIS. Autentikasi: Bearer token dari /api/v1/login atau Profil → Token API.',
            ],
            'servers' => [['url' => url('/')]],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
                ],
            ],
            'tags' => array_map(fn ($g) => ['name' => $g], array_values(array_unique(array_column($this->endpoints(), 'group')))),
            'paths' => $paths,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function endpoints(): array
    {
        return [
            [
                'group' => 'Auth',
                'method' => 'POST',
                'path' => '/api/v1/login',
                'title' => 'Login & terbitkan token',
                'description' => 'Menukar email+password dengan Bearer token (64 hex).',
                'auth' => false,
                'body' => ['email' => 'karyawan@hris.test', 'password' => 'password', 'device_name' => 'Mobile'],
            ],
            [
                'group' => 'Profil',
                'method' => 'GET',
                'path' => '/api/v1/me',
                'title' => 'Profil pengguna',
                'description' => 'Data akun, role, permission, dan karyawan terkait.',
                'auth' => true,
            ],
            [
                'group' => 'Absensi',
                'method' => 'GET',
                'path' => '/api/v1/attendance',
                'title' => 'Riwayat absensi',
                'description' => 'Riwayat absensi pengguna (paginated).',
                'auth' => true,
            ],
            [
                'group' => 'Absensi',
                'method' => 'POST',
                'path' => '/api/v1/attendance/check-in',
                'title' => 'Check-in',
                'description' => 'Check-in menggunakan timestamp server.',
                'auth' => true,
            ],
            [
                'group' => 'Absensi',
                'method' => 'POST',
                'path' => '/api/v1/attendance/check-out',
                'title' => 'Check-out',
                'description' => 'Check-out sesi kerja aktif.',
                'auth' => true,
            ],
            [
                'group' => 'Cuti',
                'method' => 'GET',
                'path' => '/api/v1/leave-balances',
                'title' => 'Saldo cuti',
                'description' => 'Saldo cuti tahun berjalan.',
                'auth' => true,
            ],
            [
                'group' => 'Cuti',
                'method' => 'GET',
                'path' => '/api/v1/leaves',
                'title' => 'Riwayat pengajuan cuti',
                'description' => 'Daftar pengajuan cuti pengguna (paginated).',
                'auth' => true,
            ],
            [
                'group' => 'Cuti',
                'method' => 'POST',
                'path' => '/api/v1/leaves',
                'title' => 'Ajukan cuti',
                'description' => 'Membuat pengajuan cuti baru.',
                'auth' => true,
                'body' => ['leave_type_id' => 1, 'start_date' => '2026-11-10', 'end_date' => '2026-11-12', 'reason' => 'Keperluan keluarga'],
            ],
            [
                'group' => 'Pengumuman',
                'method' => 'GET',
                'path' => '/api/v1/announcements',
                'title' => 'Pengumuman',
                'description' => 'Pengumuman yang sudah terbit (paginated).',
                'auth' => true,
            ],
        ];
    }
}
