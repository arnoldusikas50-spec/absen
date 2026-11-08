<?= view('errors/html/_shell', [
    'kode'      => 404,
    'judul'     => 'Halaman tidak ditemukan',
    'pesanUmum' => 'Alamat yang Anda tuju sudah berpindah, salah ketik, atau memang tidak pernah ada.',
    'pesan'     => $message ?? null,
    'ref'       => '',
]) ?>
