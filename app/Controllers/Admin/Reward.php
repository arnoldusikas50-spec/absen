<?php

namespace App\Controllers\Admin;

use App\Models\RewardModel;
use App\Models\RewardTierModel;
use App\Models\RewardRedemptionModel;
use App\Models\PoinHistoryModel;

class Reward extends \App\Controllers\BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $rewardModel = new RewardModel();
        $tierModel = new RewardTierModel();
        $redemptionModel = new RewardRedemptionModel();
        $poinHistoryModel = new PoinHistoryModel();
        $dashboardModel = new \App\Models\DashboardModel();

        $setting = $dashboardModel->getSetting();
        if (!$setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        $rewards = $rewardModel->orderBy('is_active', 'DESC')->orderBy('poin_dibutuhkan', 'ASC')->findAll();
        $tiers = $tierModel->orderBy('poin_min', 'ASC')->findAll();
        $pending = $redemptionModel->getPending();

        // Get all redemptions history with teacher and admin names
        $allRedemptions = $this->db->table('reward_redemptions rr')
            ->select('rr.*, r.nama as reward_nama, r.nominal, r.poin_dibutuhkan as reward_poin, 
                      u.first_name, u.last_name, g.nama_guru, g.nip,
                      adm.first_name as admin_first, adm.last_name as admin_last')
            ->join('rewards r', 'r.id = rr.reward_id', 'left')
            ->join('users u', 'u.id = rr.user_id', 'left')
            ->join('master_guru g', 'g.id_user = u.id', 'left')
            ->join('users adm', 'adm.id = rr.approved_by', 'left')
            ->orderBy('rr.created_at', 'DESC')
            ->limit(100)
            ->get()->getResult();

        // Get all active teachers and their points balance
        $teachersRaw = $this->db->table('users u')
            ->select('u.id as user_id, u.first_name, u.last_name, u.username, g.nama_guru, g.nip, g.foto')
            ->join('master_guru g', 'g.id_user = u.id', 'left')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->orderBy('g.nama_guru', 'ASC')
            ->get()->getResult();

        // Poin tahun berjalan per user (live SUM, bukan cache)
        $year = (int) date('Y');
        $yearlyRows = $this->db->table('poin_history')
            ->select('user_id, SUM(poin) as total')
            ->where('YEAR(created_at)', $year)
            ->groupBy('user_id')
            ->get()->getResult();
        $yearlyMap = [];
        foreach ($yearlyRows as $yr) {
            $yearlyMap[(int) $yr->user_id] = (float) $yr->total;
        }

        $teachers = [];
        $totalPoinBeredar = 0;
        $annualTotal = 0;

        foreach ($teachersRaw as $t) {
            $saldo = $poinHistoryModel->getSaldo($t->user_id);
            $poinTahunan = (float) ($yearlyMap[$t->user_id] ?? 0);
            $nama = !empty($t->nama_guru) ? $t->nama_guru : trim($t->first_name . ' ' . $t->last_name);
            if (empty($nama)) $nama = $t->username;

            // Tier via model (mendukung poin_max = 0 = tak terhingga + overlap-safe)
            $currentTier = $tierModel->getTierByPoin($saldo);

            if ($saldo > 0) {
                $totalPoinBeredar += $saldo;
            }
            $annualTotal += $poinTahunan;

            $teachers[] = (object) [
                'user_id' => $t->user_id,
                'nama' => $nama,
                'nip' => $t->nip ?? '-',
                'username' => $t->username,
                'foto' => $t->foto,
                'saldo' => $saldo,
                'poin_tahunan' => $poinTahunan,
                'tier' => $currentTier
            ];
        }

        // Peringkat tahun berjalan (kinerja disiplin)
        $sortedYearly = $teachers;
        usort($sortedYearly, function ($a, $b) {
            return $b->poin_tahunan <=> $a->poin_tahunan;
        });
        $rankMap = [];
        foreach ($sortedYearly as $i => $t) {
            $rankMap[$t->user_id] = $i + 1;
        }
        foreach ($teachers as $t) {
            $t->rank_tahunan = $rankMap[$t->user_id] ?? 0;
        }

        // Sort teachers by highest points for leaderboard ranking
        $leaderboard = $teachers;
        usort($leaderboard, function($a, $b) {
            return $b->saldo <=> $a->saldo;
        });

        // Summary Statistics
        $totalRewards = count($rewards);
        $activeRewards = count(array_filter($rewards, fn($r) => (int) ($r->is_active ?? 0) === 1));
        $pendingCount = count($pending);
        $approvedCount = $this->db->table('reward_redemptions')->where('status', 'APPROVED')->countAllResults();
        $claimedCount = $this->db->table('reward_redemptions')->where('status', 'CLAIMED')->countAllResults();
        $rejectedCount = $this->db->table('reward_redemptions')->where('status', 'REJECTED')->countAllResults();

        $stats = [
            'total_rewards' => $totalRewards,
            'active_rewards' => $activeRewards,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'claimed_count' => $claimedCount,
            'rejected_count' => $rejectedCount,
            'total_poin_beredar' => $totalPoinBeredar,
            'poin_tahunan_total' => $annualTotal,
            'poin_tahunan_avg' => $teachers ? $annualTotal / count($teachers) : 0,
            'top_guru' => array_slice($leaderboard, 0, 5)
        ];

        return view('admin/reward/index', [
            'title' => 'Reward & Poin Kedisiplinan',
            'judul' => 'Kelola Reward & Poin Guru',
            'user' => $this->getUser(),
            'setting' => $setting,
            'tp_active' => $dashboardModel->getTahunActive(),
            'smt_active' => $dashboardModel->getSemesterActive(),
            'rewards' => $rewards,
            'tiers' => $tiers,
            'pending' => $pending,
            'allRedemptions' => $allRedemptions,
            'teachers' => $teachers,
            'leaderboard' => $leaderboard,
            'stats' => $stats,
            'currentYear' => $year
        ]);
    }

    // Rewards CRUD
    public function saveReward()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $rewardModel = new RewardModel();
        $id = (int) ($this->request->getPost('id') ?? 0);
        $nama = trim((string) ($this->request->getPost('nama') ?? ''));
        $deskripsi = trim((string) ($this->request->getPost('deskripsi') ?? ''));
        $poin = (int) $this->request->getPost('poin_dibutuhkan');
        $nominal = (float) ($this->request->getPost('nominal') ?? 0);
        $stok = (int) $this->request->getPost('stok');
        $isActive = $this->request->getPost('is_active') ? 1 : 0;

        if (mb_strlen($nama) < 3 || mb_strlen($nama) > 255) {
            return $this->respond(false, 'Nama reward minimal 3 karakter dan maksimal 255 karakter');
        }
        if ($poin <= 0 || $poin > 100000) {
            return $this->respond(false, 'Poin dibutuhkan harus antara 1 – 100.000 (tidak boleh gratis)');
        }
        if ($stok < 0 || $stok > 100000) {
            return $this->respond(false, 'Stok harus antara 0 – 100.000');
        }
        if ($nominal < 0 || $nominal > 100000000) {
            return $this->respond(false, 'Nominal voucher tidak valid (0 – 100 jt)');
        }
        if (mb_strlen($deskripsi) > 2000) {
            return $this->respond(false, 'Deskripsi terlalu panjang (maks 2000 karakter)');
        }

        // Cegah duplikat nama (case-insensitive)
        $dup = $rewardModel->where('LOWER(nama)', strtolower($nama));
        if ($id > 0) $dup = $dup->where('id !=', $id);
        if ($dup->first()) {
            return $this->respond(false, 'Nama reward sudah digunakan. Gunakan nama lain.');
        }

        // Stok 0 tidak boleh aktif di katalog guru (hindari klaim gagal)
        if ($stok <= 0) $isActive = 0;

        $data = [
            'nama' => $nama,
            'deskripsi' => $deskripsi,
            'poin_dibutuhkan' => $poin,
            'nominal' => $nominal,
            'stok' => $stok,
            'is_active' => $isActive,
        ];

        if ($id > 0) {
            if (!$rewardModel->find($id)) {
                return $this->respond(false, 'Reward tidak ditemukan');
            }
            $tersimpan = $rewardModel->update($id, $data);
            if ($tersimpan === false) {
                log_message('error', '[Admin\Reward] update reward ' . $id . ' gagal: ' . json_encode($rewardModel->errors()));

                return $this->respond(false, 'Reward gagal diperbarui. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
            }

            return $this->respond(true, 'Reward berhasil diperbarui' . ($stok <= 0 ? ' (otomatis dinonaktifkan karena stok 0)' : ''));
        }

        $insertId = $rewardModel->insert($data);
        if (! $insertId) {
            log_message('error', '[Admin\Reward] insert reward gagal: ' . json_encode($rewardModel->errors()));

            return $this->respond(false, 'Reward gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
        }

        return $this->respond(true, 'Reward baru berhasil ditambahkan' . ($stok <= 0 ? ' (nonaktif karena stok 0)' : ''));
    }

    public function deleteReward($id)
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        $rewardModel = new RewardModel();
        $reward = $rewardModel->find($id);
        if (!$reward) {
            return $this->respond(false, 'Reward tidak ditemukan');
        }

        // Cegah orphan: tolak hapus jika masih ada riwayat penukaran
        $used = $this->db->table('reward_redemptions')->where('reward_id', $id)->countAllResults();
        if ($used > 0) {
            return $this->respond(false, "Reward tidak dapat dihapus karena sudah memiliki {$used} riwayat penukaran. Nonaktifkan saja agar tidak muncul di katalog guru.");
        }

        $terhapus = $rewardModel->delete($id);
        if ($terhapus === false) {
            log_message('error', '[Admin\Reward] delete reward ' . $id . ' ditolak DB');

            return $this->respond(false, 'Reward gagal dihapus. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
        }

        return $this->respond(true, 'Reward berhasil dihapus');
    }

    // Tiers CRUD
    public function saveTier()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $tierModel = new RewardTierModel();
        $id = (int) ($this->request->getPost('id') ?? 0);
        $nama = trim((string) ($this->request->getPost('nama_tier') ?? ''));
        $poinMin = (int) $this->request->getPost('poin_min');
        $poinMax = (int) $this->request->getPost('poin_max');
        $badge = trim((string) ($this->request->getPost('badge') ?: 'bi-star-fill'));
        $warna = trim((string) ($this->request->getPost('warna') ?: '#6b7280'));

        if (mb_strlen($nama) < 2 || mb_strlen($nama) > 50) {
            return $this->respond(false, 'Nama tier minimal 2 dan maksimal 50 karakter');
        }
        if ($poinMin < 0 || $poinMin > 1000000) {
            return $this->respond(false, 'Poin minimal tidak valid (0 – 1.000.000)');
        }
        if ($poinMax < 0 || $poinMax > 10000000) {
            return $this->respond(false, 'Poin maksimal tidak valid (0 = tak terhingga)');
        }
        if ($poinMax !== 0 && $poinMax <= $poinMin) {
            return $this->respond(false, 'Poin maksimal harus lebih besar dari poin minimal (atau 0 = tak terhingga)');
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $warna)) {
            return $this->respond(false, 'Kode warna harus format hex, contoh #eab308');
        }
        if (!preg_match('/^bi-[a-z0-9-]+$/', $badge)) {
            return $this->respond(false, 'Icon badge harus format Bootstrap Icons, contoh bi-trophy-fill');
        }

        // Cegah rentang tumpang tindih dengan tier lain
        $others = $tierModel->findAll();
        $newMax = $poinMax === 0 ? PHP_INT_MAX : $poinMax;
        foreach ($others as $o) {
            if ($id > 0 && (int) $o->id === $id) continue;
            $oMax = ((int) $o->poin_max === 0) ? PHP_INT_MAX : (int) $o->poin_max;
            $oMin = (int) $o->poin_min;
            if ($poinMin <= $oMax && $oMin <= $newMax) {
                return $this->respond(false, "Rentang {$poinMin}–" . ($poinMax === 0 ? '∞' : $poinMax) . " tumpang tindih dengan tier \"{$o->nama_tier}\" ({$oMin}–" . ((int) $o->poin_max === 0 ? '∞' : $o->poin_max) . ")");
            }
        }

        $data = [
            'nama_tier' => $nama,
            'poin_min' => $poinMin,
            'poin_max' => $poinMax,
            'badge' => $badge,
            'warna' => strtolower($warna),
        ];

        if ($id > 0) {
            if (!$tierModel->find($id)) {
                return $this->respond(false, 'Tier tidak ditemukan');
            }
            $tersimpan = $tierModel->update($id, $data);
            if ($tersimpan === false) {
                log_message('error', '[Admin\Reward] update tier ' . $id . ' gagal: ' . json_encode($tierModel->errors()));

                return $this->respond(false, 'Tier gagal diperbarui. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
            }

            return $this->respond(true, 'Tier berhasil diperbarui');
        }

        $insertId = $tierModel->insert($data);
        if (! $insertId) {
            log_message('error', '[Admin\Reward] insert tier gagal: ' . json_encode($tierModel->errors()));

            return $this->respond(false, 'Tier gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
        }

        return $this->respond(true, 'Tier baru berhasil ditambahkan');
    }

    public function deleteTier($id)
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        $tierModel = new RewardTierModel();
        if (!$tierModel->find($id)) {
            return $this->respond(false, 'Tier tidak ditemukan');
        }
        if (count($tierModel->findAll()) <= 1) {
            return $this->respond(false, 'Tier terakhir tidak dapat dihapus. Minimal 1 tier harus ada.');
        }

        $terhapus = $tierModel->delete($id);
        if ($terhapus === false) {
            log_message('error', '[Admin\Reward] delete tier ' . $id . ' ditolak DB');

            return $this->respond(false, 'Tier gagal dihapus. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
        }

        return $this->respond(true, 'Tier berhasil dihapus');
    }

    // Redemptions Approval
    public function approveRedemption($id)
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID klaim tidak valid');
        }

        $this->db->transBegin();

        try {
            // Kunci baris: hanya PENDING yang bisa diproses (cegah double-approve antar admin)
            $r = $this->db->table('reward_redemptions')->where('id', $id)->where('status', 'PENDING')->get()->getRow();
            if (!$r) {
                $this->db->transRollback();
                return $this->respond(false, 'Permintaan klaim tidak valid atau sudah diproses admin lain');
            }

            if ($this->adalahPemilikKlaim($r)) {
                $this->db->transRollback();
                return $this->respond(false, 'Anda tidak dapat memproses klaim reward milik sendiri. Mintakan admin lain.', null, null, 403);
            }

            $this->db->table('reward_redemptions')->where('id', $id)->where('status', 'PENDING')->update([
                'status' => 'APPROVED',
                'approved_by' => $this->getUserId(),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($this->db->affectedRows() !== 1) {
                throw new \DomainException('Klaim sudah diproses (race condition). Muat ulang halaman.');
            }

            $this->catatAudit(
                'Menyetujui klaim reward',
                $id,
                (int) $r->user_id,
                ['status' => $r->status],
                ['status' => 'APPROVED', 'reward_id' => $r->reward_id, 'poin_digunakan' => $r->poin_digunakan]
            );

            // Stok sudah dicadangkan (stok-1) saat guru melakukan redeem,
            // jadi di sini tidak perlu dikurangi lagi agar tidak dobel.
            $this->db->transCommit();

            return $this->respond(true, 'Penukaran reward berhasil disetujui, voucher siap dipakai guru');
        } catch (\DomainException $e) {
            $this->db->transRollback();
            return $this->respond(false, $e->getMessage());
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->gagalKlaim('approveRedemption#' . $id, $e);
        }
    }

    public function rejectRedemption($id)
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID klaim tidak valid');
        }

        $alasan = trim((string) $this->request->getPost('alasan'));
        if (mb_strlen($alasan) < 5 || mb_strlen($alasan) > 500) {
            return $this->respond(false, 'Alasan penolakan wajib diisi (5–500 karakter)');
        }

        $this->db->transBegin();

        try {
            $r = $this->db->table('reward_redemptions')->where('id', $id)->where('status', 'PENDING')->get()->getRow();
            if (!$r) {
                $this->db->transRollback();
                return $this->respond(false, 'Permintaan klaim tidak valid atau sudah diproses admin lain');
            }

            if ($this->adalahPemilikKlaim($r)) {
                $this->db->transRollback();
                return $this->respond(false, 'Anda tidak dapat memproses klaim reward milik sendiri. Mintakan admin lain.', null, null, 403);
            }

            $this->db->table('reward_redemptions')->where('id', $id)->where('status', 'PENDING')->update([
                'status' => 'REJECTED',
                'keterangan' => $alasan,
                'approved_by' => $this->getUserId(),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($this->db->affectedRows() !== 1) {
                throw new \DomainException('Klaim sudah diproses (race condition). Muat ulang halaman.');
            }

            // Refund poin ke guru (tipe REFUND agar mudah diaudit, bukan BONUS)
            $poinHistory = new PoinHistoryModel();
            $refundOk = (bool) $poinHistory->insert([
                'user_id' => $r->user_id,
                'poin' => abs((int) $r->poin_digunakan),
                'tipe' => 'REFUND',
                'keterangan' => 'Refund penukaran ditolak: ' . $alasan,
                'ref_id' => 'refund-' . $r->id . '-' . time(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Kembalikan stok yang dicadangkan saat guru redeem (hanya jika reward masih ada)
            $this->db->table('rewards')
                ->where('id', $r->reward_id)
                ->set('stok', 'stok+1', false)
                ->update();
            $stokOk = $this->db->affectedRows() === 1;

            // Update total points cache in master_guru
            $poinHistory->updateTotalPoin($r->user_id);

            $this->catatAudit(
                'Menolak klaim reward',
                $id,
                (int) $r->user_id,
                ['status' => $r->status, 'poin_digunakan' => $r->poin_digunakan],
                ['status' => 'REJECTED', 'refund_poin' => $refundOk ? abs((int) $r->poin_digunakan) : 0, 'stok_dipulihkan' => $stokOk],
                $alasan
            );

            $this->db->transCommit();

            // Pesan harus menggambarkan hasil tulis sebenarnya: klaim boleh
            // tertolak sementara refund/stok tidak sempat tersimpan.
            if (!$refundOk && !$stokOk) {
                return $this->respond(true, 'Penukaran ditolak, tetapi poin refund dan stok TIDAK berhasil dipulihkan. Periksa saldo guru dan stok reward secara manual.');
            }
            if (!$refundOk) {
                return $this->respond(true, 'Penukaran ditolak dan stok dipulihkan, tetapi poin refund TIDAK berhasil dicatat. Kembalikan poin guru lewat Penyesuaian Poin.');
            }
            if (!$stokOk) {
                return $this->respond(true, 'Penukaran ditolak dan poin dikembalikan, tetapi stok reward tidak dipulihkan (reward mungkin sudah dihapus).');
            }

            return $this->respond(true, 'Penukaran ditolak, poin dikembalikan, dan stok dipulihkan');
        } catch (\DomainException $e) {
            $this->db->transRollback();
            return $this->respond(false, $e->getMessage());
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->gagalKlaim('rejectRedemption#' . $id, $e);
        }
    }

    // Manual Point Adjustment (Bonus / Penalty)
    public function adjustPoin()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $userId = (int) $this->request->getPost('user_id');
        $poin = (int) $this->request->getPost('poin');
        $tipe = strtoupper(trim((string) ($this->request->getPost('tipe') ?? 'BONUS')));
        $keterangan = trim((string) ($this->request->getPost('keterangan') ?? ''));

        if (!in_array($tipe, ['BONUS', 'PENALTY'], true)) {
            return $this->respond(false, 'Tipe penyesuaian harus BONUS atau PENALTY');
        }
        if ($userId <= 0) {
            return $this->respond(false, 'Data guru tidak valid');
        }
        if ($poin < 1 || $poin > 1000) {
            return $this->respond(false, 'Nominal poin harus antara 1 – 1.000 per transaksi');
        }
        if (mb_strlen($keterangan) < 5 || mb_strlen($keterangan) > 255) {
            return $this->respond(false, 'Keterangan wajib diisi (5–255 karakter) sebagai jejak audit');
        }

        // Pastikan guru aktif
        $target = $this->db->table('users u')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('u.id', $userId)->where('ug.group_id', 2)->where('u.active', 1)
            ->get()->getRow();
        if (!$target) {
            return $this->respond(false, 'Guru tidak ditemukan atau tidak aktif');
        }

        $poinHistory = new PoinHistoryModel();
        $saldoLama = $poinHistory->getSaldo($userId);
        $finalPoin = $tipe === 'PENALTY' ? -abs($poin) : abs($poin);
        $saldoBaru = $saldoLama + $finalPoin;

        // Cegah saldo minus ekstrem (indikasi kesalahan input)
        if ($saldoBaru < -1000) {
            return $this->respond(false, 'Penyesuaian ditolak: saldo akan menjadi ' . number_format($saldoBaru) . ' pts (batas minus -1.000). Periksa kembali.');
        }

        $this->db->transBegin();
        $catat = false;
        try {
            $catat = (bool) $poinHistory->insert([
                'user_id' => $userId,
                'poin' => $finalPoin,
                'tipe' => $tipe === 'PENALTY' ? 'PENALTI' : 'BONUS',
                'keterangan' => $keterangan . ' (oleh admin #' . $this->getUserId() . ')',
                'ref_id' => 'manual-adj-' . $userId . '-' . uniqid(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->catatAudit(
                'Penyesuaian poin manual',
                0,
                $userId,
                ['saldo' => $saldoLama],
                ['poin' => $finalPoin, 'tipe' => $tipe, 'keterangan' => $keterangan, 'tersimpan' => $catat],
                'admin#' . ((int) ($this->getUserId() ?? 0))
            );

            $poinHistory->updateTotalPoin($userId);
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->gagalKlaim('adjustPoin user#' . $userId, $e);
        }

        if (!$catat) {
            return $this->respond(false, 'Penyesuaian poin gagal disimpan — mutasi tidak masuk ke riwayat poin. Silakan coba lagi.');
        }

        $newSaldo = $poinHistory->getSaldo($userId);

        return $this->respond(true, 'Poin berhasil disesuaikan. Saldo baru: ' . number_format($newSaldo) . ' pts', [
            'user_id' => $userId,
            'new_saldo' => $newSaldo
        ]);
    }

    /**
     * Pesan kegagalan untuk layar admin.
     *
     * Teks exception (SQL, path vendor, kredensial koneksi) pernah langsung
     * dikirim ke response JSON di endpoint ini — client tidak boleh melihatnya.
     */
    private function gagalKlaim(string $konteks, \Throwable $e)
    {
        log_message('error', '[Admin\Reward] ' . $konteks . ': ' . $e->getMessage());

        return $this->respond(false, 'Terjadi kesalahan pada sistem saat memproses klaim. Data belum berubah — coba lagi, dan bila tetap gagal lapor ke pengembang.');
    }

    private function adalahPemilikKlaim($klaim): bool
    {
        $aktor = (int) ($this->getUserId() ?? 0);

        return $aktor > 0 && (int) ($klaim->user_id ?? 0) === $aktor;
    }

    /**
     * Jejak audit keputusan reward/poin. Kegagalan menulis log tidak boleh
     * membatalkan aksi admin yang sudah benar, jadi dibungkus try/catch.
     */
    private function catatAudit(string $aksi, int $klaimId, int $targetUserId, $lama, $baru, ?string $alasan = null): void
    {
        try {
            (new \App\Models\AuditLogModel())->log(
                $klaimId > 0 ? 'reward_redemptions' : 'poin_history',
                $klaimId > 0 ? $klaimId : null,
                $aksi,
                $targetUserId,
                (int) ($this->getUserId() ?? 0),
                $lama,
                $baru,
                $alasan
            );
        } catch (\Throwable $e) {
            log_message('error', '[Admin\Reward] gagal tulis audit (' . $aksi . '): ' . $e->getMessage());
        }
    }

    // Get Point Transaction History for a Teacher
    public function getTeacherHistory($userId)
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $userId = (int) $userId;
        if ($userId <= 0) {
            return $this->respond(false, 'ID guru tidak valid');
        }

        $limit = min(100, max(10, (int) ($this->request->getGet('limit') ?? 50)));

        $history = $this->db->table('poin_history')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()->getResult();

        $poinModel = new PoinHistoryModel();
        $saldo = $poinModel->getSaldo($userId);

        return $this->respond(true, 'Data riwayat poin berhasil dimuat', [
            'saldo' => $saldo,
            'history' => $history
        ]);
    }

    // Rincian kinerja tahunan per guru (pengganti halaman /admin/poinguru/detail)
    public function getTeacherAnnual($userId)
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $userId = (int) $userId;
        if ($userId <= 0) {
            return $this->respond(false, 'ID guru tidak valid');
        }

        $year = (int) ($this->request->getGet('tahun') ?? date('Y'));
        $curYear = (int) date('Y');
        if ($year < 2020 || $year > $curYear + 1) {
            return $this->respond(false, 'Tahun tidak valid');
        }

        // Daftar yang ditampilkan tetap dipotong 200 baris terakhir, tapi
        // agregatnya wajib dari SQL atas SELURUH tahun. Menjumlahkan $history
        // membuat guru dengan mutasi > 200/tahun menunjukkan saldo bulanan dan
        // total per tipe yang lebih kecil dari kenyataannya — tanpa pesan.
        $history = $this->db->table('poin_history')
            ->where('user_id', $userId)
            ->where('YEAR(created_at)', $year)
            ->orderBy('created_at', 'DESC')
            ->limit(200)
            ->get()->getResult();

        $aggRows = $this->db->table('poin_history')
            ->select('MONTH(created_at) AS bulan, tipe, SUM(poin) AS total_poin, COUNT(*) AS jumlah', false)
            ->where('user_id', $userId)
            ->where('YEAR(created_at)', $year)
            ->groupBy(['MONTH(created_at)', 'tipe'], false)
            ->get()->getResult();

        $saldoTahunan = 0.0;
        $monthly     = array_fill(0, 12, 0);
        $typeSummary = [];
        $totalMutasi = 0;

        foreach ($aggRows as $a) {
            $poin = (float) $a->total_poin;
            $totalMutasi += (int) $a->jumlah;
            $saldoTahunan += $poin;

            $bln = (int) $a->bulan;
            if ($bln >= 1 && $bln <= 12) {
                $monthly[$bln - 1] += $poin;
            }

            $t = (string) ($a->tipe ?? '(kosong)');
            if (!isset($typeSummary[$t])) {
                $typeSummary[$t] = ['poin' => 0, 'count' => 0];
            }
            $typeSummary[$t]['poin'] += $poin;
            $typeSummary[$t]['count'] += (int) $a->jumlah;
        }

        return $this->respond(true, 'Data rincian tahunan berhasil dimuat', [
            'tahun' => $year,
            'saldo_tahunan' => $saldoTahunan,
            'history' => $history,
            'monthly' => $monthly,
            'type_summary' => $typeSummary,
            'total_mutasi' => $totalMutasi,
            // UI memakai flag ini untuk jujur: daftar di bawah hanya 200
            // mutasi terbaru, angka ringkasnya sudah dihitung penuh.
            'dibatasi' => count($history) < $totalMutasi,
        ]);
    }

    // Seed Point History from Existing Attendance
    public function seedPoin()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $userId = (int) $this->request->getPost('user_id');
        $bulan = str_pad((int) ($this->request->getPost('bulan') ?? date('m')), 2, '0', STR_PAD_LEFT);
        $tahun = (int) ($this->request->getPost('tahun') ?? date('Y'));

        if ($userId <= 0) {
            return $this->respond(false, 'ID guru tidak valid');
        }
        if ((int) $bulan < 1 || (int) $bulan > 12 || $tahun < 2020 || $tahun > ((int) date('Y') + 1)) {
            return $this->respond(false, 'Periode sinkronisasi tidak valid');
        }

        $poinHistory = new PoinHistoryModel();
        $count = $poinHistory->seedFromAttendance($userId, $bulan, $tahun);
        $poinHistory->updateTotalPoin($userId);

        return $this->respond(true, "{$count} mutasi poin berhasil disinkronkan dari data presensi.");
    }

    public function seedPoinAll()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $tahun = (int) ($this->request->getPost('tahun') ?? date('Y'));
        if ($tahun < 2020 || $tahun > ((int) date('Y') + 1)) {
            return $this->respond(false, 'Tahun sinkronisasi tidak valid');
        }

        // Sinkron massal 12 bulan × N guru bisa lama — longgarkan batas eksekusi
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $poinHistory = new PoinHistoryModel();
        $users = $this->db->table('users u')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->select('u.id')
            ->get()->getResult();

        $totalGenerated = 0;
        $failed = 0;
        foreach ($users as $u) {
            // Sinkronkan seluruh semester/tahun berjalan (Januari–Desember)
            try {
                for ($m = 1; $m <= 12; $m++) {
                    $totalGenerated += $poinHistory->seedFromAttendance($u->id, $m, $tahun);
                }
                $poinHistory->updateTotalPoin($u->id);
            } catch (\Throwable $e) {
                $failed++;
                log_message('error', 'seedPoinAll user ' . $u->id . ': ' . $e->getMessage());
            }
        }

        $msg = "Total {$totalGenerated} mutasi poin berhasil disinkronkan untuk seluruh guru pada tahun {$tahun}.";
        if ($failed > 0) $msg .= " ({$failed} guru gagal, cek log)";
        return $this->respond(true, $msg);
    }
}
