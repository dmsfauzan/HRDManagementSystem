<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kebijakan Cuti
    |--------------------------------------------------------------------------
    */

    // Jumlah hari minimal agar pengajuan cuti memerlukan persetujuan berjenjang
    // (Manager lalu HRD). Di bawah nilai ini cukup Manager saja.
    'multi_level_min_days' => (int) env('LEAVE_MULTI_LEVEL_MIN_DAYS', 3),

    // Batas maksimal saldo yang dibawa (carry-over) ke tahun berikutnya.
    // Batas per jenis cuti dapat diatur di kolom leave_types.carry_max_days.
    'carry_over_default_max_days' => 2,
];
