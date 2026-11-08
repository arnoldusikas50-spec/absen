<?= view('errors/html/_shell', [
    'kode'      => 500,
    'judul'     => 'Oops, terjadi kesalahan.',
    'pesanUmum' => 'Server mengalami gangguan. Silakan coba beberapa saat lagi atau hubungi administrator.',
    'pesan'     => $message ?? null,
    'ref'       => \App\Debug\ErrorResponder::ref(),
    'muatUlang' => true,
]) ?>
