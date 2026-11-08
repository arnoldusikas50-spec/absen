<?php

namespace App\Controllers\Guru;

use App\Models\PoinHistoryModel;
use App\Models\RewardModel;
use App\Models\RewardTierModel;
use App\Models\RewardRedemptionModel;

class Reward extends \App\Controllers\BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');

        $poinHistory = new PoinHistoryModel();
        $tierModel = new RewardTierModel();
        $rewardModel = new RewardModel();
        $redemptionModel = new RewardRedemptionModel();

        // Sync monthly discipline score → bonus points
        $bulan = date('m');
        $tahun = date('Y');
        $scoreResult = $poinHistory->syncMonthlyScoreBonus($user->id, $bulan, $tahun);
        if (isset($scoreResult['action']) && $scoreResult['action'] === 'created') {
            $poinHistory->updateTotalPoin($user->id);
        }
        $monthlyScore = $poinHistory->calculateMonthlyScore($user->id, $bulan, $tahun);

        $saldo = $poinHistory->getSaldo($user->id);
        $tier = $tierModel->getTierByPoin($saldo);
        $riwayat = $poinHistory->getRiwayat($user->id, 30);
        $rewards = $rewardModel->where('is_active', 1)->where('stok >', 0)->findAll();
        $redemptions = $redemptionModel->getRiwayat($user->id);

        // Fetch Denda info for teacher
        $dendaInfo = $this->getUserDendaInfo($user->id, $tahun);

        return view('members/guru/reward_mobile', [
            'title' => 'Poin & Reward',
            'saldo' => $saldo,
            'tier' => $tier,
            'riwayat' => $riwayat,
            'rewards' => $rewards,
            'redemptions' => $redemptions,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'monthlyScore' => $monthlyScore,
            'dendaInfo' => $dendaInfo,
        ]);
    }

    public function dendaInfo()
    {
        $user = $this->getUser();
        if (!$user) return $this->respond(false, 'Login required', null, null, 401);

        $tahun = (int) ($this->request->getGet('tahun') ?? date('Y'));
        $dendaInfo = $this->getUserDendaInfo($user->id, $tahun);

        return $this->respond(true, 'OK', $dendaInfo);
    }

    public function redeem()
    {
        $user = $this->getUser();
        if (!$user) return $this->respond(false, 'Login required', null, null, 401);
        if (!$this->isGuru() && !$this->isAdmin()) return $this->respond(false, 'Khusus guru', null, null, 403);

        $rewardId = (int) $this->request->getPost('reward_id');
        $autoApply = (int) ($this->request->getPost('auto_apply') ?? 0);

        $reward = (new RewardModel())->find($rewardId);
        if (!$reward || !$reward->is_active) return $this->respond(false, 'Reward tidak tersedia');
        if ($reward->stok <= 0) return $this->respond(false, 'Stok habis');

        $poinHistory = new PoinHistoryModel();
        $saldo = $poinHistory->getSaldo($user->id);
        if ($saldo < $reward->poin_dibutuhkan) return $this->respond(false, 'Poin tidak mencukupi');

        $nominal = (float) $reward->nominal;
        if ($nominal <= 0) {
            $nominal = $this->parseNominal($reward->nama, $reward->deskripsi);
        }

        $this->db->transBegin();

        try {
            // Deduct points (ref_id unik per percobaan agar ledger bisa diaudit;
            // cegah tabrakan ref antar redeem paralel user yang sama)
            $tercatat = $poinHistory->insert([
                'user_id' => $user->id,
                'poin' => (-1) * $reward->poin_dibutuhkan,
                'tipe' => 'REDEEM',
                'keterangan' => 'Redeem: ' . $reward->nama,
                'ref_id' => 'reward-' . $rewardId . '-' . $user->id . '-' . uniqid(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if ($tercatat === false) {
                $this->tulisGagal('mutasi poin REDEEM user#' . $user->id . ' reward#' . $rewardId);
            }

            // Deduct stock — atomik: hanya bila stok>0, cegah stok negatif
            // saat dua redeem paralel lolos cek di luar transaksi.
            $this->db->table('rewards')->where('id', $rewardId)->where('stok >', 0)->set('stok', 'stok-1', false)->update();
            if ($this->db->affectedRows() !== 1) {
                throw new \DomainException('Stok habis / diproses user lain. Muat ulang halaman.');
            }

            // If it is a fine deduction voucher and auto_apply is requested
            if ($nominal > 0 && $autoApply === 1) {
                // Guard sisa tunggakan: tolak bila tidak ada sisa atau nominal
                // melebihi sisa (mencegah voucher 20K hangus untuk sisa 10K,
                // dan mencegah tumpukan voucher detik-berurutan seperti temuan
                // audit 2026-10-09: 3x10K dalam 11 detik untuk denda 10K).
                $cek = $this->cekSisaUntukVoucher($user->id, $nominal);
                if ($cek !== null) {
                    throw new \DomainException($cek);
                }
                // Create redemption record as CLAIMED
                $redemptionId = (new RewardRedemptionModel())->insert([
                    'user_id' => $user->id,
                    'reward_id' => $rewardId,
                    'poin_digunakan' => $reward->poin_dibutuhkan,
                    'status' => 'CLAIMED',
                    'keterangan' => 'Diterapkan langsung untuk pemotongan denda',
                    'claimed_at' => date('Y-m-d H:i:s'),
                ]);
                if ($redemptionId === false) {
                    $this->tulisGagal('klaim voucher user#' . $user->id . ' reward#' . $rewardId);
                }

                // Insert into absensi_pembayaran_denda — distempel ke bulan
                // yang berutang, bukan bulan berjalan (lihat helper).
                $periodeVoucher = $this->periodePembayaranVoucher((int) $user->id, $nominal, (int) date('Y'));

                $tercatatBayar = $this->db->table('absensi_pembayaran_denda')->insert([
                    'user_id' => $user->id,
                    'bulan' => $periodeVoucher['bulan'],
                    'tahun' => $periodeVoucher['tahun'],
                    'total_denda' => $nominal,
                    'nominal_bayar' => $nominal,
                    'metode_bayar' => 'POIN',
                    'tanggal_bayar' => date('Y-m-d'),
                    'status' => 'lunas',
                    'keterangan' => 'Pemotongan denda via voucher: ' . $reward->nama,
                    'reward_redemption_id' => $redemptionId,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'paid_by' => $user->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if ($tercatatBayar === false) {
                    $this->tulisGagal('pembayaran denda voucher user#' . $user->id);
                }

                $poinHistory->updateTotalPoin($user->id);
                $this->db->transCommit();

                return $this->respond(true, 'Voucher berhasil ditukarkan dan denda periode ' . $periodeVoucher['bulan'] . '/' . $periodeVoucher['tahun'] . ' sebesar Rp ' . number_format($nominal, 0, ',', '.') . ' langsung dipotong!');
            } else {
                // Standard redemption (PENDING)
                $klaimBaru = (new RewardRedemptionModel())->insert([
                    'user_id' => $user->id,
                    'reward_id' => $rewardId,
                    'poin_digunakan' => $reward->poin_dibutuhkan,
                    'status' => 'PENDING',
                ]);
                if ($klaimBaru === false) {
                    $this->tulisGagal('pengajuan klaim user#' . $user->id . ' reward#' . $rewardId);
                }

                $poinHistory->updateTotalPoin($user->id);
                $this->db->transCommit();

                return $this->respond(true, 'Permintaan redeem berhasil dikirim.');
            }
        } catch (\DomainException $e) {
            $this->db->transRollback();
            return $this->respond(false, $e->getMessage());
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[Guru\Reward] redeem user#' . $user->id . ' reward#' . $rewardId . ': ' . $e->getMessage());
            return $this->respond(false, 'Penukaran gagal diproses. Poin Anda belum dikurangi — muat ulang halaman dan coba lagi.');
        }
    }

    public function potongDenda()
    {
        $user = $this->getUser();
        if (!$user) return $this->respond(false, 'Login required', null, null, 401);
        if (!$this->isGuru() && !$this->isAdmin()) return $this->respond(false, 'Khusus guru', null, null, 403);

        $rewardId = (int) $this->request->getPost('reward_id');
        $reward = (new RewardModel())->find($rewardId);
        if (!$reward || !$reward->is_active) return $this->respond(false, 'Reward tidak tersedia');

        $nominal = (float) $reward->nominal;
        if ($nominal <= 0) {
            $nominal = $this->parseNominal($reward->nama, $reward->deskripsi);
        }
        if ($nominal <= 0) return $this->respond(false, 'Reward ini bukan voucher pemotongan denda');
        if ($reward->stok <= 0) return $this->respond(false, 'Stok voucher habis');

        $poinHistory = new PoinHistoryModel();
        $saldo = $poinHistory->getSaldo($user->id);
        if ($saldo < $reward->poin_dibutuhkan) return $this->respond(false, 'Poin Anda (' . number_format($saldo) . ' pts) tidak mencukupi untuk voucher ini (' . number_format($reward->poin_dibutuhkan) . ' pts)');

        // Guard sisa tunggakan sebelum poin dipotong (fail-fast di luar transaksi).
        $cek = $this->cekSisaUntukVoucher($user->id, $nominal);
        if ($cek !== null) return $this->respond(false, $cek);

        $this->db->transBegin();

        try {
            // Deduct points
            $tercatat = $poinHistory->insert([
                'user_id' => $user->id,
                'poin' => (-1) * $reward->poin_dibutuhkan,
                'tipe' => 'REDEEM',
                'keterangan' => 'Potong Denda: ' . $reward->nama,
                'ref_id' => 'potong-denda-' . $rewardId . '-' . $user->id . '-' . uniqid(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if ($tercatat === false) {
                $this->tulisGagal('mutasi poin REDEEM user#' . $user->id . ' reward#' . $rewardId);
            }

            // Deduct stock — atomik seperti redeem()
            $this->db->table('rewards')->where('id', $rewardId)->where('stok >', 0)->set('stok', 'stok-1', false)->update();
            if ($this->db->affectedRows() !== 1) {
                throw new \DomainException('Stok voucher habis / diproses user lain. Muat ulang halaman.');
            }

            // Create redemption record as CLAIMED
            $redemptionId = (new RewardRedemptionModel())->insert([
                'user_id' => $user->id,
                'reward_id' => $rewardId,
                'poin_digunakan' => $reward->poin_dibutuhkan,
                'status' => 'CLAIMED',
                'keterangan' => 'Diterapkan langsung untuk pemotongan denda',
                'claimed_at' => date('Y-m-d H:i:s'),
            ]);
            if ($redemptionId === false) {
                $this->tulisGagal('klaim voucher user#' . $user->id . ' reward#' . $rewardId);
            }

            // Insert into absensi_pembayaran_denda — bulan yang berutang,
            // bukan bulan berjalan.
            $periodeVoucher = $this->periodePembayaranVoucher((int) $user->id, $nominal, (int) date('Y'));

            $tercatatBayar = $this->db->table('absensi_pembayaran_denda')->insert([
                'user_id' => $user->id,
                'bulan' => $periodeVoucher['bulan'],
                'tahun' => $periodeVoucher['tahun'],
                'total_denda' => $nominal,
                'nominal_bayar' => $nominal,
                'metode_bayar' => 'POIN',
                'tanggal_bayar' => date('Y-m-d'),
                'status' => 'lunas',
                'keterangan' => 'Pemotongan denda via voucher: ' . $reward->nama,
                'reward_redemption_id' => $redemptionId,
                'paid_at' => date('Y-m-d H:i:s'),
                'paid_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($tercatatBayar === false) {
                $this->tulisGagal('pembayaran denda voucher user#' . $user->id);
            }

            $poinHistory->updateTotalPoin($user->id);
            $this->db->transCommit();

            $newDendaInfo = $this->getUserDendaInfo($user->id, (int) date('Y'));
            $newSaldo = $poinHistory->getSaldo($user->id);

            return $this->respond(true, 'Sukses! Denda periode ' . $periodeVoucher['bulan'] . '/' . $periodeVoucher['tahun'] . ' sebesar Rp ' . number_format($nominal, 0, ',', '.') . ' berhasil dipotong.', [
                'saldo' => $newSaldo,
                'denda' => $newDendaInfo
            ]);
        } catch (\DomainException $e) {
            $this->db->transRollback();
            return $this->respond(false, $e->getMessage());
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[Guru\Reward] potongDenda user#' . $user->id . ' reward#' . $rewardId . ': ' . $e->getMessage());
            return $this->respond(false, 'Pemotongan denda gagal diproses. Poin Anda belum dikurangi — muat ulang halaman dan coba lagi.');
        }
    }

    public function applyVoucher()
    {
        $user = $this->getUser();
        if (!$user) return $this->respond(false, 'Login required', null, null, 401);
        if (!$this->isGuru() && !$this->isAdmin()) return $this->respond(false, 'Khusus guru', null, null, 403);

        $redemptionId = (int) $this->request->getPost('redemption_id');
        $redemptionModel = new RewardRedemptionModel();
        $redemption = $redemptionModel->find($redemptionId);
        if (!$redemption) return $this->respond(false, 'Redemption tidak ditemukan');
        if ((int) $redemption->user_id !== (int) $user->id) return $this->respond(false, 'Bukan milik Anda');
        if ($redemption->status !== 'APPROVED') return $this->respond(false, 'Voucher belum disetujui atau sudah dipakai');

        $reward = (new RewardModel())->find($redemption->reward_id);
        if (!$reward) return $this->respond(false, 'Reward tidak ditemukan');
        
        $nominal = (float) $reward->nominal;
        if ($nominal <= 0) {
            $nominal = $this->parseNominal($reward->nama, $reward->deskripsi);
        }
        if ($nominal <= 0) return $this->respond(false, 'Voucher ini tidak memiliki nominal pembayaran/pemotongan denda');

        // Guard sisa tunggakan (fail-fast): voucher hanya untuk tunggakan nyata.
        $cek = $this->cekSisaUntukVoucher($user->id, $nominal);
        if ($cek !== null) return $this->respond(false, $cek);

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Klaim atomik dulu: hanya APPROVED yang bisa jadi CLAIMED.
            // Cegah double-apply dua request paralel untuk redemption sama.
            $db->table('reward_redemptions')
                ->where('id', $redemptionId)->where('status', 'APPROVED')
                ->update([
                    'status' => 'CLAIMED',
                    'keterangan' => 'Diterapkan untuk pembayaran denda',
                    'claimed_at' => date('Y-m-d H:i:s'),
                ]);
            if ($db->affectedRows() !== 1) {
                throw new \DomainException('Voucher sudah dipakai / belum disetujui. Muat ulang halaman.');
            }

            // Create pembayaran_denda record (kolom lengkap agar laporan
            // by tanggal_bayar + rekap YTD konsisten, seperti redeem()).
            // Periode = bulan yang berutang, bukan bulan berjalan.
            $periodeVoucher = $this->periodePembayaranVoucher((int) $user->id, $nominal, (int) date('Y'));
            $tercatatBayar = $db->table('absensi_pembayaran_denda')->insert([
                'user_id' => $user->id,
                'bulan' => $periodeVoucher['bulan'],
                'tahun' => $periodeVoucher['tahun'],
                'total_denda' => $nominal,
                'nominal_bayar' => $nominal,
                'metode_bayar' => 'POIN',
                'tanggal_bayar' => date('Y-m-d'),
                'status' => 'lunas',
                'keterangan' => 'Pembayaran via voucher: ' . $reward->nama,
                'reward_redemption_id' => $redemptionId,
                'paid_at' => date('Y-m-d H:i:s'),
                'paid_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($tercatatBayar === false) {
                // Tanpa ini voucher tertandai CLAIMED sementara potongannya tidak
                // pernah masuk pembukuan denda.
                $this->tulisGagal('pembayaran denda voucher redemption#' . $redemptionId);
            }

            $db->transCommit();
            return $this->respond(true, 'Voucher berhasil diterapkan untuk memotong denda periode ' . $periodeVoucher['bulan'] . '/' . $periodeVoucher['tahun'] . ' Rp ' . number_format($nominal, 0, ',', '.'));
        } catch (\DomainException $e) {
            $db->transRollback();
            return $this->respond(false, $e->getMessage());
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Guru\Reward] applyVoucher user#' . $user->id . ' redemption#' . $redemptionId . ': ' . $e->getMessage());
            return $this->respond(false, 'Voucher gagal diterapkan. Voucher Anda belum terpakai — muat ulang halaman dan coba lagi.');
        }
    }

    /**
     * CI4 tidak melempar exception ketika DB menolak sebuah insert (kolom
     * terlalu panjang, nilai menabrak STRICT mode) — insert() cuma mengembalikan
     * false. Tanpa pemeriksaan ini layar guru bilang "berhasil" untuk penukaran
     * yang sebenarnya tidak pernah tersimpan.
     */
    private function tulisGagal(string $konteks): void
    {
        log_message('error', '[Guru\Reward] tulis ditolak DB: ' . $konteks);

        throw new \DomainException('Penukaran tidak dapat disimpan. Poin Anda belum berubah — muat ulang halaman lalu coba lagi. Bila berulang, hubungi admin.');
    }

    public function leaderboard()
    {
        $user = $this->getUser();
        if (!$user) return $this->respond(false, 'Login required', null, null, 401);
        // Isi leaderboard = nama, foto, dan skor seluruh guru. Jangan sampai
        // peran lain (siswa/panitia) membacanya lewat grup rute 'auth'.
        if (!$this->isGuru() && !$this->isAdmin()) return $this->respond(false, 'Khusus guru', null, null, 403);

        $bulan = (int) ($this->request->getGet('bulan') ?: date('m'));
        $tahun = (int) ($this->request->getGet('tahun') ?: date('Y'));
        if ($bulan < 1 || $bulan > 12) $bulan = (int) date('m');
        if ($tahun < 2000 || $tahun > (int) date('Y') + 1) $tahun = (int) date('Y');

        $poinHistory = new PoinHistoryModel();
        $data = $poinHistory->getLeaderboard($bulan, $tahun, 10);

        return $this->respond(true, 'OK', $data);
    }

    public function monthlyReport($bulan = null, $tahun = null)
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');

        $bulan = $bulan ? str_pad($bulan, 2, '0', STR_PAD_LEFT) : date('m');
        $tahun = $tahun ?? date('Y');
        $tglAwal = sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan);
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        $poinModel = new PoinHistoryModel();

        // Monthly score + bonus
        $monthlyScore = $poinModel->calculateMonthlyScore($user->id, $bulan, $tahun);

        // Poin breakdown by type
        $poinBreakdown = $this->db->table('poin_history')
            ->select('tipe, SUM(poin) as total, COUNT(*) as jumlah')
            ->where('user_id', $user->id)
            ->where('created_at >=', $tglAwal)
            ->where('created_at <=', $tglAkhir . ' 23:59:59')
            ->groupBy('tipe')
            ->orderBy('total', 'DESC')
            ->get()->getResult();

        // Attendance breakdown
        $attStats = $this->db->table('absensi_harian')
            ->select("
                COUNT(*) as total_hari,
                SUM(CASE WHEN status_masuk = 'ONTIME' THEN 1 ELSE 0 END) as ontime,
                SUM(CASE WHEN status_masuk = 'LATE' THEN 1 ELSE 0 END) as telat,
                SUM(CASE WHEN status_masuk = 'ALPHA' OR status_masuk = 'ALPA' THEN 1 ELSE 0 END) as alpha,
                SUM(CASE WHEN status_masuk IN ('IJIN','IZIN','SAKIT','CUTI') THEN 1 ELSE 0 END) as izin_sakit,
                SUM(CASE WHEN status_masuk LIKE 'DINAS%' THEN 1 ELSE 0 END) as dinas
            ")
            ->where('user_id', $user->id)
            ->where('tgl >=', $tglAwal)
            ->where('tgl <=', $tglAkhir)
            ->get()->getRow();

        // Fine total
        $dendaTotal = $this->db->table('absensi_harian')
            ->selectSum('denda')
            ->where('user_id', $user->id)
            ->where('tgl >=', $tglAwal)
            ->where('tgl <=', $tglAkhir)
            ->get()->getRow()->denda ?? 0;

        // Voucher payments this month
        $voucherTotal = 0;
        if ($this->db->tableExists('absensi_pembayaran_denda')) {
            $vRow = $this->db->table('absensi_pembayaran_denda')
                ->select('SUM(COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)) as total_denda')
                ->where('user_id', $user->id)
                ->where('bulan', (int)$bulan)
                ->where('tahun', (int)$tahun)
                ->where('status !=', 'batal')
                ->get()->getRow();
            $voucherTotal = (float) ($vRow->total_denda ?? 0);
        }

        // Previous month comparison
        $prevBulan = (int)$bulan - 1;
        $prevTahun = (int)$tahun;
        if ($prevBulan < 1) { $prevBulan = 12; $prevTahun--; }
        $prevBulanStr = str_pad($prevBulan, 2, '0', STR_PAD_LEFT);
        $prevTglAwal = "{$prevTahun}-{$prevBulanStr}-01";
        $prevTglAkhir = date('Y-m-t', strtotime($prevTglAwal));

        $prevPoin = $this->db->table('poin_history')
            ->selectSum('poin')
            ->where('user_id', $user->id)
            ->where('created_at >=', $prevTglAwal)
            ->where('created_at <=', $prevTglAkhir . ' 23:59:59')
            ->get()->getRow()->poin ?? 0;

        $currentPoin = $this->db->table('poin_history')
            ->selectSum('poin')
            ->where('user_id', $user->id)
            ->where('created_at >=', $tglAwal)
            ->where('created_at <=', $tglAkhir . ' 23:59:59')
            ->get()->getRow()->poin ?? 0;

        // Fetch guru info for profile
        $guru = $this->db->table('master_guru')
            ->where('id_user', $user->id)
            ->get()->getRow();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $dashboardModel = new \App\Models\DashboardModel();
        $setting = $dashboardModel->getSetting();
        $userData = $this->db->table('users')->where('id', session()->get('user_id'))->get()->getRow();

        return view('members/guru/rekap_bulanan', [
            'title' => 'Rekap Bulanan - ' . ($namaBulan[(int)$bulan] ?? $bulan) . ' ' . $tahun,
            'setting' => $setting,
            'user' => $userData,
            'guru' => $guru,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'namaBulan' => $namaBulan[(int)$bulan] ?? $bulan,
            'monthlyScore' => $monthlyScore,
            'poinBreakdown' => $poinBreakdown,
            'attStats' => $attStats,
            'dendaTotal' => (float) $dendaTotal,
            'voucherTotal' => $voucherTotal,
            'currentPoin' => (float) $currentPoin,
            'prevPoin' => (float) $prevPoin,
            'prevBulan' => $prevBulanStr,
            'prevTahun' => $prevTahun,
        ]);
    }

    /**
     * Guard voucher denda: kembalikan null bila boleh lanjut, atau pesan
     * penolakan bila tidak ada sisa tunggakan / nominal melebihi sisa.
     * Mencegah voucher hangus dan tumpukan klaim detik-berurutan.
     */
    protected function cekSisaUntukVoucher(int $userId, float $nominal): ?string
    {
        try {
            $info = $this->getUserDendaInfo($userId);
        } catch (\Throwable $e) {
            // Fail-open: bila hitung sisa gagal (DB down), jangan blokir;
            // duplikat tetap dijaga UNIQUE(reward_redemption_id).
            log_message('error', 'cekSisaUntukVoucher gagal: ' . $e->getMessage());
            return null;
        }
        $sisa = (float) ($info['sisa_denda'] ?? 0);
        if ($sisa <= 0) {
            return 'Tidak ada tunggakan denda tahun ini. Voucher tidak bisa dipakai.';
        }
        if ($nominal > $sisa) {
            return 'Sisa denda Rp ' . number_format($sisa, 0, ',', '.')
                . ', voucher Rp ' . number_format($nominal, 0, ',', '.')
                . ' melebihi sisa. Gunakan voucher senilai sisa atau bayar tunai selisihnya via admin.';
        }
        return null;
    }

    /**
     * Periode tempat pembayaran voucher ini dicatat.
     *
     * Dulu barisnya selalu distempel bulan berjalan, padahal denda yang dibayar
     * bisa berada di bulan lain (tunggakan Januari dilunasi November). Tabel
     * denda per bulan membaca baris ini menurut (bulan, tahun), akibatnya bulan
     * yang berutang tetap "Belum" dan bulan berjalan tampak kelebihan bayar.
     * Jalur admin sudah mendistribusikan pembayaran FIFO ke bulan tertua; voucher
     * dibatasi satu baris oleh UNIQUE(reward_redemption_id), jadi dipilih bulan
     * tertua yang sisanya menutupi nominal — dan tunggakan tertua bila nominalnya
     * lebih besar dari satu bulan.
     *
     * Cakupan sengaja tahun berjalan saja, sama dengan yang baru saja diperiksa
     * cekSisaUntukVoucher() sebelum baris ini ditulis.
     *
     * @return array{bulan:int,tahun:int}
     */
    private function periodePembayaranVoucher(int $userId, float $nominal, int $tahun): array
    {
        $fallback = ['bulan' => (int) date('m'), 'tahun' => $tahun];
        try {
            $perBulan = (new \App\Services\AttendancePenaltyService())->sisaDendaPerBulan($userId, $tahun);
        } catch (\Throwable $e) {
            log_message('error', 'periodePembayaranVoucher gagal: ' . $e->getMessage());
            return $fallback;
        }
        foreach ($perBulan as $b) { // sudah terurut tertua → terbaru
            if ((float) ($b['sisa'] ?? 0) >= $nominal) {
                return ['bulan' => (int) $b['bulan'], 'tahun' => (int) $b['tahun']];
            }
        }
        if ($perBulan !== []) {
            return ['bulan' => (int) $perBulan[0]['bulan'], 'tahun' => (int) $perBulan[0]['tahun']];
        }
        return $fallback;
    }

    protected function getUserDendaInfo($userId, $year = null)
    {
        // Hardening: keduanya diinterpolasi literal di query di bawah.
        $userId = (int) $userId;
        $year = (int) ($year ?? date('Y'));
        $startDate = $year . '-01-01';
        $endDate = date('Y-m-d');
        
        $totalYtd = 0.0;
        if ($this->db->tableExists('absensi_harian')) {
            $row = $this->db->table('absensi_harian')
                ->select('SUM(COALESCE(denda, 0)) as total')
                ->where('user_id', $userId)
                ->where('tgl >=', $startDate)
                ->where('tgl <=', $endDate)
                ->get()->getRow();
            $totalYtd += (float) ($row->total ?? 0);
        }

        // Alpha yang belum tercatat — estimasi kanonik via service
        // (recorded-aware, streak kontinu, tanpa hari ini) agar sama dengan
        // rekap-denda, denda-ytd, dan tabel admin.
        $settingModel = new \App\Models\AttendanceSettingModel();
        $setting = null;
        try {
            $setting = $settingModel->first();
        } catch (\Throwable $e) {}

        $alphaFine = (int) ($setting->denda_alpha_per_hari ?? 0);
        if ($alphaFine > 0 && $this->db->tableExists('absensi_harian')) {
            $penaltySvc = new \App\Services\AttendancePenaltyService();
            $miss = $penaltySvc->missingAlphaFines((int) $userId, $startDate, $endDate, $setting, false);
            $totalYtd += (float) ($miss['total'] ?? 0);
        }

        // Total Paid
        $totalDibayar = 0.0;
        if ($this->db->tableExists('absensi_pembayaran_denda')) {
            $payRow = $this->db->table('absensi_pembayaran_denda')
                ->select('SUM(COALESCE(NULLIF(nominal_bayar, 0), total_denda, 0)) as total')
                ->where('user_id', $userId)
                ->where('tahun', $year)
                ->where('status !=', 'batal')
                ->get()->getRow();
            $totalDibayar = (float) ($payRow->total ?? 0);
        }

        // Approved Unclaimed Vouchers — hanya yang BELUM masuk pembayaran
        // (sudah tercatat sebagai POIN tidak dihitung ulang agar sama dengan
        // rekap-denda, denda-ytd, dan tabel admin).
        $potonganReward = 0.0;
        if ($this->db->tableExists('reward_redemptions')) {
            $hasRedemptionLink = $this->db->tableExists('absensi_pembayaran_denda') && $this->db->fieldExists('reward_redemption_id', 'absensi_pembayaran_denda');
            $excludeLinked = $hasRedemptionLink
                ? " AND rr.id NOT IN (SELECT COALESCE(reward_redemption_id, 0) FROM absensi_pembayaran_denda WHERE user_id = {$userId} AND reward_redemption_id IS NOT NULL)"
                : "";
            $rewardRedemptions = $this->db->query("
                SELECT COALESCE(r.nominal, 0) as nominal
                FROM reward_redemptions rr
                JOIN rewards r ON rr.reward_id = r.id
                WHERE rr.user_id = {$userId} AND rr.status = 'APPROVED' AND YEAR(rr.updated_at) = '{$year}'
                {$excludeLinked}
            ")->getResult();
            foreach ($rewardRedemptions as $rr) {
                $potonganReward += (float)$rr->nominal;
            }
        }

        $sisaDenda = max(0, $totalYtd - $totalDibayar - $potonganReward);

        return [
            'total_denda' => $totalYtd,
            'total_dibayar' => $totalDibayar,
            'potongan_voucher' => $potonganReward,
            'sisa_denda' => $sisaDenda
        ];
    }

    protected function parseNominal($nama, $deskripsi = '')
    {
        $text = $nama . ' ' . $deskripsi;
        if (preg_match('/(\d+)\s*[kK]/', $text, $m)) {
            return (float) $m[1] * 1000;
        }
        if (preg_match('/(?:Rp|IDR)?\s*([\d\.,]+)/i', $text, $m)) {
            $val = str_replace(['.', ','], '', $m[1]);
            if (is_numeric($val) && (float)$val >= 1000) {
                return (float) $val;
            }
        }
        return 0.0;
    }
}
