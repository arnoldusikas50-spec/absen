# 🏫 Sistem Presensi Digital & Manajemen Kedisiplinan Sekolah

Aplikasi presensi dan manajemen kedisiplinan sekolah modern berbasis web (**Mobile-First PWA**) untuk **Guru/Pegawai** dan **Siswa**, dibangun menggunakan **CodeIgniter 4** dan **Tailwind CSS**. Sistem ini dilengkapi dengan verifikasi biometrik wajah (*FaceScan*), validasi radius lokasi (*Geofencing GPS*), absensi mandiri via QR Code Kiosk, serta sistem rekap denda dan surat teguran otomatis.

---

## ✨ Fitur Utama

### 👨‍🏫 Portal Presensi Guru & Pegawai
- **Mobile-First PWA**: Tampilan responsif layaknya aplikasi native dengan navigasi *floating pill*, jam sinkron WITA real-time, dan gesture ramah sentuhan.
- **Biometric FaceScan**: Verifikasi kehadiran menggunakan kamera depan dengan deteksi wajah dan validasi liveness.
- **Geofencing GPS**: Penguncian radius absensi berdasarkan koordinat sekolah yang telah ditetapkan.
- **Izin, Sakit & Dinas Luar**: Pengajuan dispensasi mandiri yang terhubung langsung ke alur persetujuan admin/kepala sekolah.
- **Koreksi Presensi**: Alur permohonan koreksi kehadiran jika terjadi kendala teknis di lapangan.
- **Ledger Denda & Keterlambatan**: Perhitungan denda otomatis berbasis menit keterlambatan dengan batas plafon bulanan.

### 🎒 Portal Presensi Siswa
- **Kartu Pelajar & QR Digital**: Kartu presensi digital yang dapat dicetak atau diunduh sebagai gambar JPG resolusi tinggi via Canvas.
- **Kiosk Scanner Ready**: QR Code siswa siap dipindai di kamera gerbang/kiosk sekolah.
- **Statistik & Rekap Bulanan**: Pantau persentase kehadiran bulanan, status hari ini, serta riwayat presensi harian (Hadir, Izin, Sakit, Alpha, BDR).
- **Skor Kedisiplinan**: Sistem pemantauan poin kedisiplinan dan rekapitulasi tagihan denda siswa.

### ⚙️ Panel Administrasi & Pengaturan
- **Monitoring Real-Time**: Dasbor pemantauan kehadiran harian guru dan siswa secara langsung.
- **Pengaturan Jam Kerja & Geofence**: Konfigurasi fleksibel toleransi keterlambatan, jadwal piket, dan multi-titik radius lokasi.
- **Surat Otomatis (PDF via Dompdf)**:
  - Pembuatan dan ekspor Surat Panggilan Orang Tua untuk siswa bermasalah.
  - Surat Peringatan / Teguran kedisiplinan pegawai.
- **Laporan & Rekapitulasi**: Ekspor rekap kehadiran bulanan dan laporan denda ke format Excel/PDF.

---

## 🛠️ Tech Stack

- **Backend Framework**: [CodeIgniter 4.6+](https://codeigniter.com/) (PHP 8.1+)
- **Database**: MySQL / MariaDB
- **Frontend**: Tailwind CSS, Vanilla JS, Lucide Icons (SVG Mask), SweetAlert2
- **PDF Generator**: Dompdf
- **QR Code Engine**: Endroid QR Code & QRCode.js
- **PWA Capabilities**: Web App Manifest, Service Worker, Biometric Camera Stream

---

## 🚀 Panduan Instalasi Cepat

### Prasyarat
- PHP >= 8.1 (dengan ekstensi `intl`, `mbstring`, `gd`, `curl`, `json`)
- MySQL >= 5.7 atau MariaDB >= 10.3
- Composer

### Langkah Pemasangan

1. **Clone repositori**:
   ```bash
   git clone https://github.com/username-anda/absen.git
   cd absen
   ```

2. **Pasang dependensi**:
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**:
   Salin `env` menjadi `.env`:
   ```bash
   cp env .env
   ```
   Buka file `.env` dan sesuaikan pengaturan database serta URL aplikasi:
   ```env
   CI_ENVIRONMENT = development

   app.baseURL = 'http://localhost:8080/'

   database.default.hostname = localhost
   database.default.database = db_absen
   database.default.username = root
   database.default.password = 
   database.default.DBDriver = MySQLi
   ```

4. **Migrasi Database**:
   Import skema database default atau jalankan migrasi yang tersedia.

5. **Jalankan Server Lokal**:
   ```bash
   php spark serve
   ```
   Akses aplikasi di browser melalui `http://localhost:8080`.

---

## 🔒 Lisensi

Hak Cipta © 2026 Ikasmedia / Tim Pengembang. Seluruh hak cipta dilindungi undang-undang.
