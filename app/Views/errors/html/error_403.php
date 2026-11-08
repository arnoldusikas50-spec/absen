<?= view('errors/html/_shell', [
    'kode'       => 403,
    'judul'      => 'Akses Ditolak',
    'pesanUmum'  => 'Anda tidak memiliki hak akses ke halaman ini. Silakan masuk dengan akun yang berwenang atau hubungi administrator.',
    'pesan'      => $message ?? null,
    'ref'        => '',
]) ?>
