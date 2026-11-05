<?php

return [
    // Ambang (menit) agar pengajuan lembur perlu persetujuan berjenjang (Manager → HRD).
    'multi_level_min_minutes' => (int) env('OVERTIME_MULTI_LEVEL_MIN_MINUTES', 180),
];
