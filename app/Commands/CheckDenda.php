<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckDenda extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'check:denda';
    protected $description = 'Cek denda YTD seorang user (param: username, default: guruku6)';
    protected $usage       = 'check:denda [username]';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $username = trim((string) ($params[0] ?? 'guruku6'));
        if ($username === '') {
            CLI::error('Username tidak boleh kosong. Contoh: php spark check:denda guruku6');
            return;
        }
        $user = $db->table('users')->where('username', $username)->get()->getRow();
        if (!$user) {
            CLI::error("User {$username} not found");
            return;
        }
        CLI::write("User ID: {$user->id} ({$user->first_name} {$user->last_name})");

        // 1. Denda Harian
        $awalTahun = date('Y-01-01');
        $harian = $db->table('absensi_harian')
            ->where('user_id', $user->id)
            ->where('tgl >=', $awalTahun)
            ->where('tgl <=', date('Y-m-d'))
            ->select('SUM(COALESCE(denda, 0)) as total')
            ->get()->getRow();
        CLI::write("Total Denda Harian (recorded): " . ($harian->total ?? 0));

        // Denda Harian per bulan
        $harianPerBulan = $db->query("
            SELECT MONTH(tgl) as bln, SUM(denda) as total_denda, COUNT(*) as cnt
            FROM absensi_harian
            WHERE user_id = {$user->id} AND tgl >= '{$awalTahun}' AND denda > 0
            GROUP BY MONTH(tgl)
            ORDER BY bln ASC
        ")->getResult();
        CLI::write("Denda harian per bulan:");
        foreach ($harianPerBulan as $hp) {
            CLI::write("  Bulan {$hp->bln}: {$hp->total_denda} ({$hp->cnt} hari)");
        }

        // 2. Guru Attendance::dendaYtd logic
        $settingModel = new \App\Models\AttendanceSettingModel();
        $setting = $settingModel->first();
        $penaltySvc = new \App\Services\AttendancePenaltyService();
        $startDate = $awalTahun;
        $endDate = date('Y-m-d');
        $missYtd = $penaltySvc->missingAlphaFines((int)$user->id, $startDate, $endDate, $setting, false);
        
        CLI::write("\n--- Guru missingAlphaFines (rentang penuh Jan-today) ---");
        CLI::write("Missing count: " . $missYtd['count']);
        CLI::write("Missing total denda: " . $missYtd['total']);
        $totalYtdGuru = (float)($harian->total ?? 0) + (float)($missYtd['total'] ?? 0);
        CLI::write("Guru Total Denda YTD: " . $totalYtdGuru);

        // 3. Admin logic
        $admin = new \App\Controllers\Admin\PresensiAdmin();
        // Cek getYTDDendaStats via reflection
        $ref = new \ReflectionClass($admin);
        $method = $ref->getMethod('getYTDDendaStats');
        $method->setAccessible(true);
        $ytdStats = $method->invoke($admin, (int) date('Y'));

        $userYtdAdmin = $ytdStats[$user->id] ?? null;
        CLI::write("\n--- Admin getYTDDendaStats result ---");
        if ($userYtdAdmin) {
            CLI::write("Admin Total Denda YTD: " . $userYtdAdmin['total_denda']);
            CLI::write("Admin Dibayar Tunai:   " . $userYtdAdmin['dibayar_tunai']);
            CLI::write("Admin Potongan Poin:   " . $userYtdAdmin['potongan_poin']);
            CLI::write("Admin Potongan Saldo:  " . $userYtdAdmin['potongan_saldo']);
            CLI::write("Admin Total Dibayar:   " . $userYtdAdmin['total_dibayar']);
            CLI::write("Admin Sisa Denda:      " . $userYtdAdmin['sisa']);
        }

        // 4. Cek Denda Kotor Bulan Ini di Admin daftarDenda
        $blnIni = date('m');
        $thnIni = date('Y');
        CLI::write("\n--- Cek Bulan Ini ({$blnIni}/{$thnIni}) ---");
        $alphaDetailMap = $ref->getMethod('filteredAlphaMonthMap');
        $alphaDetailMap->setAccessible(true);
        $mMap = $alphaDetailMap->invoke($admin, date('Y-m-01'), date('Y-m-t'), $setting);
        $userSep = $mMap[$user->id] ?? null;
        if ($userSep) {
            CLI::write("Sep count: " . $userSep['count']);
            CLI::write("Sep missing count: " . $userSep['missing_count']);
            CLI::write("Sep total denda missing: " . $userSep['total_denda']);
        }

        // Denda harian bulan berjalan
        $harianBln = $db->table('absensi_harian')
            ->where('user_id', $user->id)
            ->where('MONTH(tgl)', (int) date('m'))
            ->where('YEAR(tgl)', (int) date('Y'))
            ->select('SUM(COALESCE(denda, 0)) as total')
            ->get()->getRow();
        CLI::write("Denda Harian Bln Ini: " . ($harianBln->total ?? 0));
        CLI::write("Denda Kotor Bln Ini Admin = Denda Harian (" . ($harianBln->total ?? 0) . ") + Denda Alpha (" . ($userSep['total_denda'] ?? 0) . ") = " . ((float)($harianBln->total ?? 0) + (float)($userSep['total_denda'] ?? 0)));
    }
}
