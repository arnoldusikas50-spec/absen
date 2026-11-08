<?php
/**
 * Halaman fallback untuk kode/status yang tidak punya template sendiri.
 *
 * Dipilih CI4 ketika error_{kode}.php tidak ada, jadi isinya sengaja umum.
 * $code dan $message dikirim BaseExceptionHandler::collectVars().
 */
?>
<?= view('errors/html/_shell', [
    'kode'       => is_numeric($code ?? null) ? (int) $code : 500,
    'judul'      => 'Terjadi Kesalahan',
    'pesanUmum'  => 'Permintaan belum bisa diproses. Silakan coba lagi beberapa saat.',
    'pesan'      => $message ?? null,
    'ref'        => ((int) ($code ?? 500)) >= 500 ? \App\Debug\ErrorResponder::ref() : '',
    'muatUlang'  => ((int) ($code ?? 500)) >= 500,
]) ?>
