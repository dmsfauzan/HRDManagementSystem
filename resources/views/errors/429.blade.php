@include('errors.layout', [
    'code' => 429,
    'title' => 'Terlalu Banyak Permintaan',
    'message' => 'Anda mengirim terlalu banyak permintaan. Tunggu beberapa saat sebelum mencoba lagi.',
])
