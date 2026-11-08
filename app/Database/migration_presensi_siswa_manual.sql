-- ============================================================
-- Migration Manual: UpdatePresensiSiswaAddMasukKeluar
-- Jalankan di phpMyAdmin atau MySQL client jika php spark
-- tidak bisa diakses dari CLI.
-- ============================================================

-- 1. Tambah kolom jam_masuk (jika belum ada)
ALTER TABLE `presensi_siswa`
    ADD COLUMN IF NOT EXISTS `jam_masuk` TIME NULL AFTER `tgl`;

-- 2. Tambah kolom jam_keluar (jika belum ada)
ALTER TABLE `presensi_siswa`
    ADD COLUMN IF NOT EXISTS `jam_keluar` TIME NULL AFTER `jam_masuk`;

-- 3. Salin data kolom jam lama → jam_masuk (supaya data existing tidak hilang)
UPDATE `presensi_siswa`
    SET `jam_masuk` = `jam`
    WHERE `jam` IS NOT NULL AND `jam_masuk` IS NULL;

-- ============================================================
-- Verifikasi struktur tabel setelah migrasi:
-- ============================================================
-- DESCRIBE `presensi_siswa`;
