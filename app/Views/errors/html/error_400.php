<?= view('errors/html/_shell', [
    'kode'       => 400,
    'judul'      => 'Permintaan Tidak Valid',
    'pesanUmum'  => 'Data yang dikirim tidak dapat diproses. Periksa kembali formulir atau tautan yang Anda gunakan.',
    'pesan'      => $message ?? null,
    'ref'        => '',
]) ?>
