<?php

return [
    // Batas unggah bukti transfer (KB). PRD: 5 MB.
    'proof_max_kb' => 5120,
    'logo_max_kb' => 2048,

    // Zona waktu tampilan/penjadwalan (config/app.php starter kit terkunci UTC).
    'timezone' => env('SIRGA_TIMEZONE', 'Asia/Jakarta'),
];
