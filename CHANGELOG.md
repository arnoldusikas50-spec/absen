# Catatan Perubahan (Changelog)

Semua perubahan penting pada proyek **Sistem Presensi Digital & Kedisiplinan Sekolah** didokumentasikan di berkas ini.

Format berkas ini mengacu pada prinsip [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

---

## [Unreleased / v2.1.0] - 2026-11-09

### 🗺️ Peta Geofence Presensi (`/admin/presensi/lokasi`)
- **Penyelesaian Masalah Blokir Tile OpenStreetMap 403 (Tile Usage Policy)**:
  - Mengganti tile provider default menjadi **Google Maps Tiles (Roadmap & Satelit Hybrid)** yang bebas blokir, sangat detail, dan akurat di seluruh pelosok Indonesia.
  - Menghadirkan **Multi-Provider Tile Switcher** dengan menu dropdown mengambang:
    1. 🗺️ **Google Maps (Jalan)** (Default cepat & jernih)
    2. 🛰️ **Google Satelit (Hybrid)** (Foto udara tajam dengan label gedung & nama jalan)
    3. 🏛️ **Esri World Street** (Peta topografi & jalan Esri ArcGIS)
    4. 🌍 **OpenStreetMap (HOT)** (Tile server kemanusiaan OSM France, bebas blokir foundation)
  - Menyediakan mekanisme **Auto-Fallback**: jika suatu tile server mengalami gangguan atau timeout, peta otomatis beralih ke layer alternatif tanpa memunculkan ubin error kuning 403.
  - Menyimpan preferensi layer yang dipilih admin ke `localStorage` browser.
  - Memperbarui sistem pencarian lokasi pencarian gedung/kota menggunakan **Photon Komoot API** dengan fallback ke Nominatim agar pencarian selalu berhasil tanpa terbentur limit OpenStreetMap.

### 🏷️ Sistem Scanner Kiosk & Integrasi Multi-Metode RFID (`/scanner`)
- **Integrasi Penuh Kartu RFID & Reader Keyboard Wedge**:
  - Menyediakan fallback pencarian kartu RFID otomatis di backend (`findSiswaByQr` & `findGuruByQr`) melalui kolom `nomor_rfid`.
  - Mendukung input mode eksplisit RFID (`rfid_value`) maupun auto-detect dari barcode scanner/RFID reader USB.
  - Mengirimkan metadata `'metode' => 'rfid'|'qr'|'fp'` dalam payload respon presensi masuk dan pulang.
- **Penyempurnaan Antarmuka Kiosk Scanner (`scanner_siswa_desktop.php`)**:
  - Menghadirkan tombol saklar mode (*toggle*) RFID dan Fingerprint (*FP*) dengan status aktif visual bergelombang (`.active-scanner-tab`).
  - Menambahkan lencana dinamis identifikasi metode presensi di Hero Result Card: lencana hijau bercahaya untuk **RFID** (`.badge-metode-rfid`), lencana biru untuk **QR CODE** (`.badge-metode-qr`), dan ungu untuk **FINGERPRINT** (`.badge-metode-fp`).
  - Menambahkan animasi denyut cahaya kartu (*card glow animation* `.rfid-pulse`) pada bingkai foto profil saat presensi terverifikasi melalui kartu RFID.
  - Memperbarui teks standby dan instruksi pemindai menjadi *"Silakan Scan QR / RFID / Sidik Jari"*.
  - Menyelaraskan ikon pendukung menggunakan ikon Bootstrap Icons resmi (`bi-credit-card-2-front` dan `bi-fingerprint`).

### 🎨 Desain & UI/UX (Portal Siswa `/siswa/presensi`)
- **Modernisasi Shell Aplikasi & Layout (`mobile_siswa.php`)**:
  - Membatasi wadah aplikasi ke ukuran mobile standar (`max-width: 480px`) di tengah layar (*centered*) dengan latar warna slate halus (`#f8fafc`).
  - Menambahkan dukungan PWA (*Progressive Web App*) lengkap: `manifest.json`, `apple-touch-icon`, ikon 192x192 & 512x512, serta meta tag tema adaptif.
  - Memperbarui z-index modal ke `3500` dan mengadopsi tema warna aksen siswa (`--accent-600`) pada komponen SweetAlert2.
- **Navigasi Bawah Mengambang (*Floating Pill Bottom Nav*)**:
  - Mengubah bottom nav dari model bilah datar (*flat bar*) menjadi pil melayang berdesain *glassmorphism* (`backdrop-filter: blur(22px)`).
  - Menggunakan ikon SVG vektor inline dengan animasi warna ganda (*duotone*) saat menu aktif.
  - Menghadirkan tombol tengah melayang (*elevated center button*) khusus akses cepat Kartu QR Presensi.
  - Menyediakan 4 menu navigasi utama: **Beranda**, **Riwayat**, **Kartu QR**, dan **Keluar** (dengan konfirmasi).
- **Hero Section Gradien Dinamis**:
  - Mengganti header lama dengan gradien ungu aksen (`var(--accent-300)` s.d. `var(--accent-700)`).
  - Menambahkan elemen visual bergerak: partikel bintang berkilau (*sparkle*), gelembung mengapung (*bubble float*), pola titik-titik (*dot pattern*), dan gelombang SVG dekoratif.
  - Menambahkan *greeting* pintar berdasarkan waktu (Selamat Pagi / Siang / Sore / Malam) beserta nama panggilan siswa.
  - Menampilkan foto profil siswa berbingkai putih dengan inisial cadangan otomatis jika foto belum diunggah.
  - Menambahkan lencana status motivasi yang memuat NIS dan Kelas siswa.
- **Jam Digital WITA Real-Time & Kartu Status Melayang (*Floating Status Card*)**:
  - Mengintegrasikan jam server sinkron WITA (`wita-clock.js`) yang berdetik setiap detik secara presisi.
  - Dilengkapi indikator titik hijau berdenyut (*live pulse dot*) dan format tanggal bahasa Indonesia.
  - Menampilkan ringkasan presensi harian secara langsung: kotak mini **Jam Masuk** dan **Jam Pulang** lengkap dengan lencana status.
- **Restrukturisasi Grid Statistik Bulanan**:
  - Mengubah susunan grid menjadi 2 kolom simetris yang proporsional di seluruh resolusi layar ponsel.
  - Menghilangkan celah kosong dan ketidakseimbangan ukuran kartu (*Hadir, Izin, Sakit, Alpha*).
  - Menambahkan perilaku lencana adaptif 2 kolom (`s-stat-span-2`) untuk status khusus seperti *BDR* dan *Tanpa Keterangan*.
- **Pembersihan Kartu Informasi (Denda, Skor Disiplin, Presensi Hari Ini)**:
  - Menghilangkan duplikasi header kartu yang berulang ganda.
  - Memperbaiki ikon status denda menggunakan ikon dompet yang didukung (`bi-wallet2`) dan ikon centang (`bi-check-circle-fill`).
  - Merapikan lencana tagihan denda, nominal, serta rincian alpha & bolos.
  - Menyederhanakan kartu skor disiplin dan riwayat presensi harian agar tampak ringkas dan profesional.

### 🐛 Perbaikan Bug & Teknis
- **Siklus Hidup Modal QR Siswa**:
  - Memperbaiki penanganan `display: flex` dan `style.display = 'none'` pada saat menutup dan membuka kembali modal kartu QR.
  - Memastikan modal kartu memiliki latar putih solid, radius sudut 20px, dan elevasi z-index 9999 di atas seluruh elemen navigasi.
- **Penutupan Tag HTML**:
  - Memperbaiki ketidaksesuaian penutupan kontainer pembungkus utama (`.s-main-content` dan `.s-wrap`) sebelum blok modal.
- **Sinkronisasi AJAX Riwayat & Denda**:
  - Memperbaiki referensi ikon pada AJAX `loadRiwayat()` saat memperbarui status bebas denda.

---

## [v2.0.0] - Baseline Fitur Sebelumnya

### ✨ Fitur Pokok
- **Portal Guru & Pegawai (`/presensi`)**:
  - Verifikasi biometrik wajah (*FaceScan*) dengan validasi keaslian wajah (*liveness detection*).
  - Penguncian radius lokasi kerja presensi berbasis GPS (*Geofencing*).
  - Pengajuan izin, sakit, dinas luar, dan koreksi data presensi.
  - Perhitungan denda keterlambatan otomatis dan batas plafon bulanan.
- **Sistem Kiosk Scanner**:
  - Pemindai kamera QR Code publik untuk akses mandiri di lingkungan sekolah.
- **Panel Administrasi**:
  - Dasbor statistik harian, rekapitulasi kehadiran bulanan, dan ekspor PDF/Excel.
  - Manajemen master data siswa, kelas, guru, dan jadwal piket.
  - Pembuatan otomatis Surat Panggilan Orang Tua dan Surat Teguran Disiplin Pegawai (ekspor PDF via Dompdf).
