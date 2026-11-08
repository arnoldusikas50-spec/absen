# CRONTAB — Jadwal Otomatis Server

Dokumen ini berisi seluruh jadwal cron (crontab) untuk aplikasi absensi
`absensi.ikas.my.id`, beserta cara memasang dan memverifikasinya.

> **Penting:** semua stok cron **tidak tersimpan di dalam repo ini**. Bila
> crontab hilang (mis. panel aaPanel di-reset, reinstall OS, atau file
> `crontab` tidak ikut ter-backup), semua proses otomatis berhenti tanpa
> error. Tidak ada satu pun baris di bawah ini yang otomatis dibuat oleh
> `scripts/deploy.sh` — **wajib dipasang ulang secara manual setelah reinstall
> server.** Gunakan dokumen ini sebagai acuan.

---

## 1. Prasyarat

### 1.1 Secret key

Semua endpoint `/cron/*` dilindungi satu query param `key` yang nilainya dibaca
dari `.env`:

```ini
CRON_SECRET_KEY = <lihat-nilai-di-.env-server-jangan-commit>
```

Semua baris crontab memakai **nilai yang sama**. Kalau `CRON_SECRET_KEY` diubah
di `.env`, semua URL cron harus ikut diubah.

> PENTING: nilai di `.env` ini ada di dalam repository. Setelah deploy, ganti
> dengan nilai acak sendiri dan **jangan** commit `.env` yang baru. Endpoint
> cron ini menjalankan proses yang mengubah data (menandai ALPHA, menghitung
> poin), jadi penebakan key berarti siapa pun bisa mengubah data absensi.

> BILA `CRON_SECRET_KEY` kosong di `.env`, semua endpoint `/cron/*` menolak
> request (403) dan mencatat `critical` di log — fail-closed. Tidak ada lagi
> fallback ke nilai hardcoded, jadi pastikan key terisi sebelum memasang cron.

### 1.2 Lokasi aplikasi

```bash
APP_DIR=/home/ikas-absensi/htdocs/absensi.ikas.my.id
LOG="$APP_DIR/writable/logs/cron.log"
```

Sesuaikan bila path server berbeda. Pastikan PHP CLI punya izin tulis ke
`writable/logs/` — **semua cron ialah user `ikas-absensi`**, bukan www, jadi
yang harus punya izin tulis adalah user cron:

```bash
chown -R ikas-absensi:ikas-absensi "$APP_DIR/writable"
chmod -R 775 "$APP_DIR/writable"
```

> Jalankan `chown` ini SETELAH SETIAP deploy. Deploy yang dijalankan sebagai
> root menimpa berkas di bawah `writable/` **bersama kepemilikannya** — log
> `cron.log` kembali milik root, seluruh redirect `>> "$LOG"` gagal diam-diam
> (insiden 28 Okt – 1 Nov 2026, lihat §7.1).

---

## 2. Cara memasang

Sumber kebenaran crontab = file **`cronjob`** di root repo (salinan sinkron
dari `/var/spool/cron/crontabs/ikas-absensi`). Jangan edit crontab lewat
`crontab -e` langsung — edit file `cronjob`, lalu pasang:

```bash
sudo crontab -u ikas-absensi /home/ikas-absensi/htdocs/absensi.ikas.my.id/cronjob
sudo crontab -l -u ikas-absensi   # verifikasi baris tersimpan
```

Tempel blok di bagian 4 ke dalam `cronjob` bila file itu kosong, **lalu wajib**
tes ulang seperti di atas.

Crontab **tidak** memakai timezone server bila panel menyetelnya manual.
Jam di bawah memakai **WITA (UTC+8)**, mengikuti `appTimezone` di
`app/Config/App.php` (`Asia/Makassar`). Bila server memakai UTC, tambahkan
`CRONTAB_TZ=Asia/Makassar` di baris paling atas crontab, atau geser semua jam
pada tabel berikut (**kurangi 8 jam**).

> **Wajib sama dengan `appTimezone`.** Aplikasi menghitung "hari ini" memakai
> `Asia/Makassar`. Bila crontab memakai zona waktu lain, cron meleset satu jam
> dari tanggal yang dipakai aplikasi — pesan "rekap hari ini" bisa berisi rekap
> kemarin, dan tugas yang harus berjalan tengah malam bisa jalan lebih awal.

`CRONTAB_TZ` hanya berjalan di cron `vixie/cronie` (yang dipakai hampir semua
distro Linux modern, termasuk aaPanel). Bila tidak berfungsi, cek dulu timezone
server lalu sesuaikan jamnya secara manual:

```bash
date              # timezone server
timedatectl       # atau: cat /etc/timezone
```

---

## 3. Verifikasi

### 3.1 Cek log cron (paling cepat)

Setiap baris cron mengarahkan output ke `writable/logs/cron.log`:

```bash
tail -30 /home/ikas-absensi/htdocs/absensi.ikas.my.id/writable/logs/cron.log
```

Kalau file ini **tidak bertambah** setelah jadwalnya lewat, cron tidak jalan.
Contoh keluaran sehat:

```
OK: 2026-09-26 18:00:12
Alpha marked: 10 guru
Skip: bukan tanggal evaluasi surat teguran
Skip: notif kehadiran rendah dinonaktifkan
Daily recap sent
Forgot checkout reminder: 1 guru
```

> Baris `... dilewati: channel TG Grup + WA Grup dinonaktifkan ...` juga
> normal: itu berarti admin mematikan kedua channel grup untuk jenis
> tersebut di **Pengaturan → Channel Notifikasi** (`/admin/presensi/notif-config`).
> Sukses cron = salah satu kanal aktif berhasil, jadi konfigurasi
> WA-saja (TG mati) tetap `OK` selama WA terkirim.

### 3.2 Jalankan manual

Seluruh endpoint notifikasi bisa dipanggil langsung. Jalankan salah satu:

```bash
K=$(grep -E '^CRON_SECRET_KEY' /home/ikas-absensi/htdocs/absensi.ikas.my.id/.env | sed 's/.*= *//')

curl -i "https://absensi.ikas.my.id/cron/ucapan-pagi?key=$K"
curl -i "https://absensi.ikas.my.id/cron/ucapan-sore?key=$K"
curl -i "https://absensi.ikas.my.id/cron/daily-recap?key=$K"
curl -i "https://absensi.ikas.my.id/cron/rekap-piket?key=$K"
curl -i "https://absensi.ikas.my.id/cron/weekly-denda?key=$K"
curl -i "https://absensi.ikas.my.id/cron/monthly-pdf?key=$K&bulan=9&tahun=2026"
curl -i "https://absensi.ikas.my.id/cron/semester-recap?key=$K&tahun=2026&semester=1"
```

### Pengiriman ulang yang disengaja

Semua endpoint notifikasi di atas memakai penjaga idempotensi: kalau periode
yang sama **sudah terkirim**, permintaan kedua akan dilewati dan dijawab
pesan(skip) — bukan mengirim ulang. Ini mencegah rekap dobel saat cron 18:00
dan perintah manual berjalan berdekatan.

Untuk memang sengaja mengirim ulang, tambahkan `&force=1`:

```bash
curl "https://absensi.ikas.my.id/cron/rekap-piket?key=$K&tanggal=2026-11-06" # tanggal lampau
curl "https://absensi.ikas.my.id/cron/daily-recap?key=$K&force=1"
curl "https://absensi.ikas.my.id/cron/ucapan-pagi?key=$K&force=1"
curl "https://absensi.ikas.my.id/cron/rekap-piket?key=$K&force=1"
```

Hanya pengiriman yang **berhasil** yang mengunci periode. Kalau gateway
tolak, periode itu tetap boleh dicoba lagi — jadi memperbaiki kredensial
lalu mengulang perintahnya tetap efektif.

Riwayat pengiriman tersimpan di tabel `cron_notifikasi_log` (kolom
`jenis`, `periode`, `terkirim`, `percobaan`, `catatan`). Ada dua cara
membacanya:

- **Di browser** — **Pengaturan → Notifikasi → Status Notifikasi**, atau
  langsung ke `/admin/presensi/notifikasi-status`. Halaman itu juga
  menampilkan status gateway OpenWA/Telegram dan daftar DM guru-terawal
  yang gagal terkirim.
- **Di terminal** — untuk dipakai lewat SSH:

```bash
# notifikasi yang gagal belakangan, beserta alasannya
php spark cron:notif-log 30
```
> Semua perintah di atas **benar-benar mengirim pesan ke grup Telegram**. Kalau
> hanya ingin melihat hasil hitungannya tanpa mengirim, pakai
> `?tanggal=` pada `ucapan-pagi` untuk memeriksa kalimat per varian — jangan
> dijalankan berkali-kali di hari yang sama, karena tiap panggilan mengirim
> satu pesan baru.

### 3.3 Arti kode HTTP

| Kode | Arti | Tindakan |
|---|---|---|
| **200** | Terkirim | Tidak ada aksi |
| **403** | `Invalid key` — `CRON_SECRET_KEY` di `.env` ≠ key di URL | Samakan keduanya |
| **500** | Gagal kirim; body memuat alasannya | Baca body, perbaiki, lalu kirim ulang manual |
| Timeout / connection refused | Server mati, DNS, atau firewall | Periksa server; ini **bukan** masalah aplikasi |

Body HTTP 500 sengaja memuat alasan yang bisa ditindaklanjuti, contoh:

```
Rekap GAGAL: Bot Token kosong pada baris absensi_setting id=1 — isi di Pengaturan > Presensi.
```

---

## 4. Blok crontab

Salin seluruh blok ini ke `crontab -e`.

```cron
# ==========================================================================
# CRONTAB absensi.ikas.my.id
# Semua jam memakai WITA (UTC+8) — sama dengan appTimezone di app/Config/App.php.
#
# Ganti CRON_KEY di bawah bila CRON_SECRET_KEY di .env diganti.
# Ganti BASE_URL bila domain berubah.
# ==========================================================================

CRONTAB_TZ=Asia/Makassar
SHELL=/bin/bash

APP_DIR=/home/ikas-absensi/htdocs/absensi.ikas.my.id
LOG=/home/ikas-absensi/htdocs/absensi.ikas.my.id/writable/logs/cron.log
BASE_URL=https://absensi.ikas.my.id
CRON_KEY=<salin-dari-CRON_SECRET_KEY-di-.env-server>

> **Jangan pakai variabel di dalam nilai variabel cron** (mis. `LOG="$APP_DIR/..."`).
> Cron hanya mengembang satu lapis: `$APP_DIR` di dalam nilai `LOG` tidak
> akan pernah terurai, sehingga `>> "$LOG"` mengarah ke berkas harfiah
> `$APP_DIR/...` yang tidak ada — output semua job hilang diam-diam
> (persis insiden 9–10 Okt 2026: job jalan, log beku). Tulis path lengkap.

# --- 1. Ucapan pagi 07:00 --------------------------------------------------
# Kalimat menyesuaikan status hari: hari kerja sekolah / Minggu / hari libur.
0 7 * * * curl --silent --show-error "$BASE_URL/cron/ucapan-pagi?key=$CRON_KEY" >> "$LOG" 2>&1

# --- 2. Ucapan pulang 15:00 (hari kerja saja) --------------------------------
# Dijadwalkan setelah jam pulang terakhir (Jumat & Sabtu pulang 12:00/12:30,
# Senin-Kamis 14:00) sehingga tidak lebih dulu dari guru yang pulang.
# Di hari libur / Minggu TIDAK mengirim: tidak ada kegiatan yang selesai,
# jadi endpoint menjawab "dilewati" (HTTP 200, bukan gagal). `?force=1`
# tidak bisa melewatinya — ini aturan bisnis, bukan penjaga kirim-ulang.
0 15 * * * curl --silent --show-error "$BASE_URL/cron/ucapan-sore?key=$CRON_KEY" >> "$LOG" 2>&1

# --- 3. Proses harian 18:00 --------------------------------------------
# Menandai ALPHA, surat teguran, rekap presensi, pengingat lupa checkout.
# Menghasilkan 5 baris output sekaligus. Ini entry utama yang WAJIB ada.
0 18 * * * curl --silent --show-error "$BASE_URL/cron/daily?key=$CRON_KEY" >> "$LOG" 2>&1

# --- 4. Rekap denda mingguan, Sabtu 18:05 --------------------------------
# Dijadwalkan 18:05 (bukan 18:00) supaya selesai setelah proses harian #3.
# Rekap denda teks BULANAN sudah dihapus — versi PDF-nya (tgl 1, #5)
# yang dipakai, supaya tidak dobel dengan dokumen.
5 18 * * 6 curl --silent --show-error "$BASE_URL/cron/weekly-denda?key=$CRON_KEY" >> "$LOG" 2>&1

# --- 5. PDF rekap bulanan, tgl 1 jam 07:10 ---------------------------
# Mengirim DUA dokumen bulan lalu ke grup (Telegram + WA, sesuai
# channel di Pengaturan → Channel Notifikasi):
#   - PDF Rekap Presensi (switch 'rekap_bulanan')
#   - PDF Rekap Denda    (switch 'rekap_denda_bulanan')
# Dipanggil tgl 1 → periode = bulan sebelumnya (1 Okt → September).
# Idempoten per PDF per bulan; `&force=1` untuk kirim ulang, dan
# `&bulan=9&tahun=2026` untuk backfill bulan yang terlewat.
# Dipilih jam 07:10 (bukan 07:00) supaya tidak berebut dengan ucapan pagi —
# render dua PDF Dompdf itu berat.
10 7 1 * * curl --silent --show-error "$BASE_URL/cron/monthly-pdf?key=$CRON_KEY" >> "$LOG" 2>&1

# --- 6. Rekap semester, 1 Juli & 2 Januari jam 19:00 ---------------------
# Menghasilkan tabel absensi_rekap_semester. Menutup semester ganjil (1)
# dan genap (2). Semester ganjil biasanya ditutup akhir Juni, semester
# genap akhir Desember. Tanggal 1/2 dipilih (bukan tanggal 30/31) supaya
# tetap terjadwal di semua bulan dan di semua tahun.
#   1 Juli  -> rekap semester 1 (tahun berjalan)
#   2 Jan   -> rekap semester 2 (tahun sebelumnya)
0 19 1 7 * curl --silent --show-error "$BASE_URL/cron/semester-recap?key=$CRON_KEY&tahun=$(date +\%Y)&semester=1" >> "$LOG" 2>&1
0 19 2 1 * curl --silent --show-error "$BASE_URL/cron/semester-recap?key=$CRON_KEY&tahun=$(( $(date +\%Y) - 1 ))&semester=2" >> "$LOG" 2>&1

# --- 7. Sinkron poin siswa, harian 19:30 ----------------------------------
# Menghitung ulang poin siswa dari absensi_harian (recalcBulan).
30 19 * * * curl --silent --show-error "$BASE_URL/cron/poin-siswa?key=$CRON_KEY" >> "$LOG" 2>&1

# --- 8. Health check tiap 10 menit ----------------------------------------
# Memeriksa database / writable / disk. Tidak mengirim Telegram saat
# sehat — diam. Saat gagal, ia mengirim alert ke grup Telegram.
# Jalankan lewat CLI (bukan HTTP) supaya tidak bergantung pada web server.
*/10 * * * * cd $APP_DIR && php spark health:check >> "$LOG" 2>&1

# --- 9. Backup database, tiap hari 02:00 ----------------------------------
# mysqldump + gzip + retensi. Mengirim notifikasi Telegram bila gagal.
0 2 * * * cd $APP_DIR && php spark db:backup >> "$LOG" 2>&1
```

### 4.1 Catatan `\%` pada baris cron

Di dalam crontab, `%` berarti **penghenti baris**. Karena itu `$(date +\%Y)`
harus ditulis dengan backslash. Tanpa backslash, baris tersebut akan terpotong
dan cron gagal diam-diam.

---

## 5. Tabel jadwal

| # | Jadwal | Endpoint / perintah | Fungsi | Kirim Telegram |
|---|---|---|---|---|
| 1 | `0 6 * * *` | `GET /cron/ucapan-pagi` | Ucapan selamat pagi (status hari aware) | Ya |
| 2 | `0 15 * * *` | `GET /cron/ucapan-sore` | Ucapan pulang — hari kerja saja, libur dilewati | Ya (hari kerja) |
| 3 | `0 18 * * *` | `GET /cron/daily` | ALPHA, surat teguran, rekap presensi, lupa checkout | Ya |
| 3b | `30 18 * * *` | `GET /cron/rekap-piket` | Rekap laporan piket harian → grup WA/TG | Ya |
| 4 | `5 18 * * 6` | `GET /cron/weekly-denda` | Rekap denda mingguan (Sen–Sab) | Ya |
| 5 | `10 7 1 * *` | `GET /cron/monthly-pdf` | PDF presensi + denda bulan lalu | Ya |
| 6 | `0 19 1 7 *` | `GET /cron/semester-recap` | Rekap semester ganjil | Tidak |
| 7 | `0 19 2 1 *` | `GET /cron/semester-recap` | Rekap semester genap | Tidak |
| 8 | `30 19 * * *` | `GET /cron/poin-siswa` | Hitung ulang poin siswa | Tidak |
| 9 | `*/10 * * * *` | `php spark health:check` | Cek DB / writable / disk | Hanya saat gagal |
| 10 | `0 2 * * *` | `php spark db:backup` | Backup + retensi | Hanya saat gagal |

Keterangan tambahan:

- **Ucapan pagi menyesuaikan status hari.** Status ditentukan berurutan dari:
  (1) `absensi_setting.hari_libur_mingguan` — CSV nama hari, mis. `Sun`;
  (2) `absensi_jadwal_kerja.is_libur`; (3) tabel `absensi_hari_libur`.
  Kalimatnya berbeda untuk hari kerja sekolah, hari Minggu, dan hari libur.

  > **Sabtu adalah hari kerja sekolah** di sekolah ini (pulang 12:30), jadi
  > Sabtu mendapat ucapan "hari kerja sekolah", bukan "akhir pekan".

- **Kalender libur diisi manual.** Bila tabel `absensi_hari_libur` belum punya
  baris untuk tahun berjalan, hari libur nasional **tidak terdeteksi** dan hari
  tersebut akan mendapat kalimat "hari kerja sekolah". Endpoint membalas HTTP 200
  tetapi menyertakan peringatan di body — risalnya
  `PERINGATAN: Kalender libur untuk tahun <tahun> masih kosong`. **Periksa
  peringatan ini di `cron.log` setiap pergantian tahun.**

- **`?tanggal=YYYY-MM-DD` pada `ucapan-pagi`** untuk mencoba varian kalimat
  lain tanpa menunggu hari tersebut. Pesan yang dikirim tetap sungguhan.
  Contoh lengkap ketiga varian beserta hasilnya:

  ```bash
  K=<CRON_SECRET_KEY>
  curl "https://absensi.ikas.my.id/cron/ucapan-pagi?key=$K&tanggal=2026-09-30"  # -> (aktif)
  curl "https://absensi.ikas.my.id/cron/ucapan-pagi?key=$K&tanggal=2026-10-04"  # -> (akhir_pekan)
  curl "https://absensi.ikas.my.id/cron/ucapan-pagi?key=$K&tanggal=2026-08-17"  # -> (libur)
  ```

- **Parameter opsional lain** — `semester-recap` menerima `?tahun=&semester=`.
  Bila periode terlewat, rekap bisa dibuat ulang secara manual tanpa menunggu
  jadwal. Rekap denda teks bulanan (tgl 28) sudah dihapus — versi PDF-nya
  lewat `monthly-pdf` (lihat di bawah).
- **`monthly-pdf` memakai bulan lalu bila dipanggil tanpa parameter**
  (dipanggil 1 Okt → PDF September). Untuk backfill, isi eksplisit:
  `?bulan=9&tahun=2026`. Tiap PDF idempoten sendiri-sendiri per bulan,
  `&force=1` untuk mengirim ulang.
- **`poin-siswa` juga bisa dijalankan lewat CLI**: `php spark poin:siswa`. Ia
  menghormati pengaturan auto-sync masing-masing bulan.
- **`dapodik_sync`** (`GET /cron/dapodik_sync`, atau `php spark dapodik:sync`)
  sengaja **tidak** diberi jadwal di sini. Auto-sync Dapodik sedang NONAKTIF
  pada instalasi ini — endpoint membalas
  `{"status":false,"message":"Auto-sync Dapodik nonaktif."}` sehingga jadwal
  akan sia-sia. Aktifkan lewat pengaturan Dapodik lebih dulu, baru tambahkan
  cron bila dibutuhkan.

---

## 6. Memberi tahu jika ada kegagalan

Semua endpoint notifikasi (nomor 1, 2, 3) membalas **HTTP 500** plus alasan
jika Telegram gagal menerima. Manfaatkan itu untuk monitoring — jangan hanya
membaca `cron.log` secara manual:

```cron
# Contoh: kirim alert bila rekap harian gagal.
0 18 * * * curl --silent --fail -o /dev/null "$BASE_URL/cron/daily?key=$CRON_KEY" \
  || php spark health:check
```

`--fail` membuat curl keluar dengan kode selain 0 saat HTTP >= 400, sehingga
bagian `||` di arahan yang sama dieksekusi.

---

## 7. Contoh masalah & penyebabnya

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `cron.log` berhenti bertambah | Crontab hilang / server reinstall | Pasang ulang blok di §4 |
| Semua balas `403 Invalid key` | `CRON_SECRET_KEY` di `.env` ≠ key di crontab | Samakan keduanya |
| `Rekap GAGAL: Bot Token kosong` | Kolom `telegram_bot_token` kosong di `absensi_setting` | Isi lewat **Pengaturan → Presensi**, lalu `curl /cron/daily-recap` |
| `Rekap GAGAL: Chat ID tidak ditemukan` | Grup berubah jadi supergroup (id jadi `-100...`) | Ambil id baru, update `telegram_chat_id` |
| `Rekap GAGAL: Bot was blocked/kicked` | Bot dikeluarkan dari grup | Masukkan bot kembali & jadikan admin |
| `500` pada `semester-recap` | `?tahun=` di luar 2000–2100 atau `semester` bukan 1/2 | Perbaiki parameternya (`400` = parameter, `500` = kegagalan sistem) |
| Semua balas `200` padahal tidak ada pesan masuk | Notifikasi dimatikan, atau endpoint dilewati karena sudah terkirim | Buka **Status Notifikasi** di panel admin |
| Ucapan sore selalu `dilewati: bukan hari kerja` | Hari libur / Minggu — by design tidak dikirim | Bukan error. Cek `absensi_hari_libur` + `hari_libur_mingguan` bila hari kerja ikut dilewati |
| Baris `... dilewati: channel TG Grup + WA Grup dinonaktifkan ...` | Kedua channel grup jenis itu dimatikan di **Pengaturan → Channel Notifikasi** | Bukan error (HTTP 200). Nyalakan salah satu channel bila pesan memang harus terkirim |
| WA terkirim tapi cron tetap `GAGAL` (sudah tidak terjadi) | Perilaku lama: sukses hanya ikut Telegram | Sejak perbaikan channel-config, sukses = salah satu kanal aktif berhasil |
| Ucapan pagi selalu "hari kerja sekolah" di hari libur | `absensi_hari_libur` kosong untuk tahun berjalan | Isi tanggal merah; lihat baris peringatan di `cron.log` |
| Ucapan pagi "hari libur" di hari Minggu | `hari_libur_mingguan` diubah, `Sun` tidak ada di CSV | Kembalikan `Sun` ke `hari_libur_mingguan` |
| PDF bulanan tidak terkirim tgl 1 | Baris `monthly-pdf` belum dipasang (jadwal lama tgl 28 sudah dihapus) | Pasang baris §4 nomor 5; cek idempotensi `pdf_rekap_bulanan`/`pdf_denda_bulanan` di Status Notifikasi |
| Zona waktu meleset | Server UTC, jadwal ditulis dalam WITA | Tambah `CRONTAB_TZ=Asia/Makassar` |
| CLI `php spark` gagal "not found" | Path absolut tidak dipakai / cwd salah | Gunakan `cd $APP_DIR && php spark ...` seperti §4 nomor 7 |
| Semua job "jalan" di syslog tapi `cron.log` tidak bertambah | Redirect `>> "$LOG"` ditolak (Permission denied) — bash membatalkan command sebelum dieksekusi | `chown -R ikas-absensi:ikas-absensi "$APP_DIR/writable"`; cek `/var/mail/ikas-absensi` untuk mengetahui yang terlewat (§7.1) |

### 7.1 Arsip insiden: redirect cron.log "Permission denied", 28 Okt – 1 Nov 2026

Gejala yang terlihat lebih dulu: **hening total** — `cron.log` tidak
bertambah sedetik pun selama berhari-hari, padahal syslog (`/var/log/syslog`)
tetap menampilkan baris `(ikas-absensi) CMD (...)` tiap jadwal. Kronologinya:

1. **28 Okt** — deploy dijalankan sebagai root; `writable/logs/cron.log`
   berpindah milik root.
2. **28 Okt – 1 Nov** — setiap job gagal di tahap `>> "$LOG"`. Bash menjalankan
   redirect **sebelum** command, jadi job tidak pernah dieksekusi: rekap harian
   3 hari terlewat, `weekly-denda` 2 Sabtu terlewat, backup DB 4 hari terlewat,
   dan **PDF Oktober (`monthly-pdf`) tidak pernah terkirim** (di
   `cron_notifikasi_log` tidak ada catatan `pdf_rekap_bulanan`/`pdf_denda_bulanan`).
3. Stdout+stderr tiap job terbawa cron ke mailbox lokal
   `/var/mail/ikas-absensi`. Isinya satu kalimat yang seragam:
   `/bin/bash: line 1: .../writable/logs/cron.log: Permission denied`.
4. **1 Nov ±09:51** — kepemilikan `writable/` dikembalikan ke
   `ikas-absensi`; mulai 18:00 harian itu semua job sehat kembali.

Jejak apa yang terlewat ada di mailbox itu — tiap email berpola
`Subject: Cron <...> <baris cron>` dengan isi pesan berupa errornya —
sehingga bisa didaftar ulang dengan:

```bash
grep -a "^Subject: " /var/mail/ikas-absensi | sort | uniq -c | sort -rn
```

Yang diambil untuk pulih:

- **Perbaikan izin** (dilakukan): `chown -R ikas-absensi:ikas-absensi $APP_DIR/writable`.
- **Backfill PDF**: `curl "$BASE_URL/cron/monthly-pdf?key=$K&bulan=10&tahun=2026"`
  — idempotensi `pdf_rekap_bulanan`/`pdf_denda_bulanan` masih terbuka, jadi
  mengirim ulang tidak akan dobel. (Periode ini *tidak* di-backfill — dilewat.)
- **Tidak bisa di-backfill**: `daily` 18:00 (hanya `ucapan-pagi` yang menerima
  `?tanggal=`), `weekly-denda` (periode selalu dihitung relatif hari ini),
  `db:backup` untuk tanggal lewat.

Pengaman untuk ke depan:

1. **Setiap deploy** diakhiri `chown -R ikas-absensi:ikas-absensi $APP_DIR/writable`.
2. Pantau cron lewat **dua jalur**: ukuran/mtime `cron.log` **dan** jumlah email
   baru di `/var/mail/ikas-absensi` — hening `cron.log` dengan mailbox
   bertambah besar adalah penyakit yang sama (redirect gagal), bukan cron
   yang diam-diam sehat.

---

## 8. Berkas terkait

- `app/Controllers/CronController.php` — seluruh proses cron & endpoint
- `app/Config/Routes.php` baris 790–791 — route `weekly-denda` & `monthly-pdf` (dapodik_sync di baris 1009)
- `app/Models/JurnalGuruModel.php` — `detachStaleJadwalLinks()`: perapian tautan jurnal harian
- `app/Commands/HealthCheck.php` — `php spark health:check`
- `app/Commands/DbBackup.php` — `php spark db:backup`
- `app/Commands/DapodikSync.php` — `php spark dapodik:sync`
- `cronjob` — salinan sinkron crontab; sumber kebenaran pemasangan (§2)
- `writable/logs/cron.log` — log gabungan semua job; wajib milik `ikas-absensi`
  (chown setelah deploy, lihat §1.2/§7.1)
