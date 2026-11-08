<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PoinHistoryModel;

class PoinGuru extends BaseController
{
    public function index()
    {
        $this->ensureTableExists();
        $db = \Config\Database::connect();
        $year = date('Y');

        // Read teachers with poin_tahunan (now synced from poin_history)
        $teachers = $db->table('master_guru mg')
            ->select('mg.id_guru, mg.nama_guru, mg.nip, mg.poin_tahunan, mg.foto, u.foto as user_foto')
            ->join('users u', 'u.id = mg.id_user', 'left')
            ->orderBy('mg.poin_tahunan', 'DESC')
            ->get()
            ->getResult();

        $dashboardModel = new \App\Models\DashboardModel();
        $setting = $dashboardModel->getSetting();
        if (!$setting) {
            $setting = (object) ['nama_aplikasi' => 'CBT AKM', 'logo_kiri' => null];
        }

        $absensiSetting = $db->table('absensi_setting')->orderBy('id', 'ASC')->get()->getRow();
        $user = $db->table('users')->where('id', session()->get('user_id'))->get()->getRow();

        // Summary stats from poin_history
        $stats = $db->table('poin_history')
            ->selectSum('poin', 'total')
            ->where("YEAR(created_at)", $year)
            ->get()->getRow();
        $annualTotalPoin = (float) ($stats->total ?? 0);

        $data = [
            'teachers' => $teachers,
            'title' => 'Poin Tahunan Guru',
            'setting' => $setting,
            'user' => $user,
            'absensiSetting' => $absensiSetting,
            'currentYear' => $year,
            'annualTotalPoin' => $annualTotalPoin,
        ];

        return view('admin/poin_guru/index', $data);
    }

    public function recalculate()
    {
        $this->ensureTableExists();
        $db = \Config\Database::connect();
        $year = date('Y');
        $poinModel = new PoinHistoryModel();

        // PoinHistoryModel::seedFromAttendance()/updateTotalPoin() sudah
        // memastikan tabelnya sendiri lewat safeQuery(). Memanggil method
        // private model dari sini menghasilkan Fatal error begitu halaman
        // ini dirutekan ulang.

        // 1. Backfill poin_history from attendance data for all teachers
        $teachers = $db->table('master_guru')
            ->select('id_guru, id_user')
            ->where('id_user IS NOT NULL')
            ->get()
            ->getResult();

        $seedCount = 0;
        foreach ($teachers as $guru) {
            for ($m = 1; $m <= 12; $m++) {
                $seedCount += $poinModel->seedFromAttendance($guru->id_user, $m, $year);
            }
        }

        // 2. Sync master_guru.poin_tahunan = SUM(poin_history.poin) per user
        $subQuery = $db->table('poin_history')
            ->select('user_id, SUM(poin) as total_poin')
            ->where("YEAR(created_at)", $year)
            ->groupBy('user_id')
            ->getCompiledSelect();

        $rows = $db->table('master_guru mg')
            ->select('mg.id_guru, ph.total_poin')
            ->join("({$subQuery}) ph", 'ph.user_id = mg.id_user', 'left')
            ->get()
            ->getResult();

        $count = 0;
        $gagal = 0;
        foreach ($rows as $row) {
            $total = (float) ($row->total_poin ?? 0);
            $tersimpan = $db->table('master_guru')
                ->where('id_guru', $row->id_guru)
                ->update(['poin_tahunan' => $total]);
            if ($tersimpan === false) {
                $gagal++;
            } else {
                $count++;
            }
        }

        $msg = "Berhasil menyinkronkan poin {$year} untuk {$count} guru dari poin_history (seed: {$seedCount} entri baru).";
        if ($gagal > 0) {
            $msg = "Sinkronisasi {$year} baru berhasil untuk {$count} guru; {$gagal} guru gagal ditulis. Muat ulang dan ulangi, atau gunakan Sinkron Poin di layar Reward.";
        }
        return redirect()->to('admin/poinguru')->with('message', $msg);
    }

    public function detail($id_guru)
    {
        $db = \Config\Database::connect();

        $teacher = $db->table('master_guru mg')
            ->select('mg.*, u.username, u.email, u.foto as user_foto')
            ->join('users u', 'u.id = mg.id_user', 'left')
            ->where('mg.id_guru', $id_guru)
            ->get()
            ->getRow();

        if (!$teacher) {
            return redirect()->to('admin/poinguru')->with('error', 'Data guru tidak ditemukan.');
        }

        $year = date('Y');

        // Fetch poin_history for this teacher this year
        $poinHistory = $db->table('poin_history')
            ->where('user_id', $teacher->id_user)
            ->where("YEAR(created_at)", $year)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResult();

        // Group by month for chart
        $monthly_stats = [];
        foreach ($poinHistory as $row) {
            $month = date('F', strtotime($row->created_at));
            if (!isset($monthly_stats[$month])) {
                $monthly_stats[$month] = ['points' => 0, 'count' => 0];
            }
            $monthly_stats[$month]['points'] += (float) $row->poin;
            $monthly_stats[$month]['count']++;
        }

        // Totals by type
        $typeSummary = [];
        foreach ($poinHistory as $row) {
            $t = $row->tipe;
            if (!isset($typeSummary[$t])) {
                $typeSummary[$t] = ['poin' => 0, 'count' => 0];
            }
            $typeSummary[$t]['poin'] += (float) $row->poin;
            $typeSummary[$t]['count']++;
        }

        $dashboardModel = new \App\Models\DashboardModel();
        $setting = $dashboardModel->getSetting();
        $user = $db->table('users')->where('id', session()->get('user_id'))->get()->getRow();

        $data = [
            'teacher' => $teacher,
            'poinHistory' => $poinHistory,
            'monthly_stats' => $monthly_stats,
            'typeSummary' => $typeSummary,
            'title' => 'Detail Poin: ' . $teacher->nama_guru,
            'setting' => $setting,
            'user' => $user,
            'currentYear' => $year,
        ];

        return view('admin/poin_guru/detail', $data);
    }

    private function ensureTableExists()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if (!$db->fieldExists('poin_tahunan', 'master_guru')) {
            $forge->addColumn('master_guru', [
                'poin_tahunan' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                    'after' => 'foto',
                ],
            ]);
        }
    }
}
