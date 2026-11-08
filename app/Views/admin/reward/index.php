<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .badge-purple { background: #f3e8ff; color: #7e22ce; font-weight: 700; }
    .num-tabular { font-variant-numeric: tabular-nums; }
    .tier-badge-preview {
        width: 36px; height: 36px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }
    .catalog-card {
        border: 1.5px solid #eef0f6; border-radius: 0.875rem; background: #fff;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        display: flex; flex-direction: column; height: 100%;
    }
    .catalog-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(15,23,42,.09); border-color: #cbd5e1; }
    .catalog-card.is-inactive { opacity: .75; background: #fafbfc; }
    .stock-bar { height: 6px; border-radius: 9999px; background: #eef2f7; overflow: hidden; }
    .stock-bar > span { display: block; height: 100%; border-radius: inherit; transition: width .3s ease; }
    .stat-attention { border-color: #fbbf24 !important; box-shadow: 0 0 0 3px rgba(251,191,36,.18); }
    .annual-bars { display: flex; align-items: flex-end; gap: 6px; height: 132px; }
    .annual-col { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .annual-val { font-size: 9px; font-weight: 700; line-height: 1; margin-bottom: 3px; }
    .annual-bar-pos, .annual-bar-neg { width: 100%; border-radius: 4px 4px 0 0; min-height: 3px; }
    .annual-bar-neg { border-radius: 0 0 4px 4px; }
    .annual-zero { width: 100%; border-top: 1px dashed #cbd5e1; }
    .annual-month { font-size: 9px; color: #94a3b8; margin-top: 3px; }
    .empty-state { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    .empty-state i { font-size: 2.25rem; display: block; margin-bottom: .5rem; }
    .preview-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .7rem; border-radius: 9999px; font-size: .75rem; font-weight: 700; }
    @media (max-width: 640px) {
        .content-area { padding: 0.5rem 0.75rem 1.25rem; }
        .card-tw-body { padding: 0.875rem; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$pendingCount = (int) ($stats['pending_count'] ?? 0);
$claimedCount = (int) ($stats['claimed_count'] ?? 0);
$rejectedCount = (int) ($stats['rejected_count'] ?? 0);
$approvedCount = (int) ($stats['approved_count'] ?? 0);
?>
<div class="fade-in-up p-4 md:p-6 space-y-6">
    <!-- Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-gift-fill"></i> Poin &amp; Kedisiplinan
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kelola Reward &amp; Poin Kedisiplinan Guru</h1>
            <p class="text-xs text-slate-500 mt-1">Katalog penukaran, persetujuan klaim voucher, dan manajemen saldo poin guru.</p>
        </div>
        <div class="flex gap-2 flex-wrap items-center">
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors" id="btnRewardRefresh" title="Muat ulang data">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <a href="<?= site_url('admin/presensi/setting') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-gear"></i> Pengaturan Poin
            </a>
            <a href="<?= site_url('admin/presensi/denda') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-cash-coin text-emerald-600"></i> Rekap Denda
            </a>
        </div>
    </div>

    <?php if ($pendingCount > 0): ?>
    <div class="alert-tw alert-warning">
        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
        <div>Ada <strong><?= $pendingCount ?> klaim menunggu persetujuan</strong>. Buka tab <button type="button" class="underline font-bold border-none bg-transparent cursor-pointer p-0" id="btnGotoKlaim">Klaim &amp; Penukaran</button> untuk memverifikasi sebelum voucher dipakai guru.</div>
    </div>
    <?php endif; ?>

    <!-- Kartu utama -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 pt-4" style="padding-bottom:0">
            <ul class="nav-tabs" role="tablist" aria-label="Navigasi reward">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" data-ui-toggle="tab" href="#tab-rewards" role="tab" aria-selected="true">
                        <i class="bi bi-grid-fill"></i> Katalog
                        <span class="badge-tw badge-secondary"><?= count($rewards) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-ui-toggle="tab" href="#tab-redemptions" role="tab" aria-selected="false">
                        <i class="bi bi-inbox-fill"></i> Klaim &amp; Penukaran
                        <?php if ($pendingCount > 0): ?><span class="badge-tw badge-danger"><?= $pendingCount ?></span><?php endif; ?>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-ui-toggle="tab" href="#tab-teachers" role="tab" aria-selected="false">
                        <i class="bi bi-people-fill"></i> Saldo Guru
                        <span class="badge-tw badge-secondary"><?= count($teachers) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-ui-toggle="tab" href="#tab-tiers" role="tab" aria-selected="false">
                        <i class="bi bi-award-fill"></i> Tier
                        <span class="badge-tw badge-secondary"><?= count($tiers) ?></span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-tw-body">
            <div class="tab-content">
                <!-- TAB 1: KATALOG -->
                <div class="tab-pane show active" id="tab-rewards" role="tabpanel">
                    <div class="flex flex-wrap justify-between items-end gap-2 mb-4">
                        <div class="min-w-0">
                            <h6 class="font-bold text-slate-800 mb-0">Daftar Item &amp; Voucher</h6>
                            <p class="text-slate-400 mb-0" style="font-size:.8rem">Guru menukar poin dengan voucher denda atau item. Stok 0 otomatis nonaktif.</p>
                        </div>
                        <button type="button" class="btn-tw btn-tw-sm btn-primary btn-add-reward">
                            <i class="bi bi-plus-circle-fill"></i> Tambah Reward
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2 mb-4 items-center">
                        <div class="input-group-tw" style="max-width:260px;flex:1;min-width:180px">
                            <span class="ig-prepend"><i class="bi bi-search"></i></span>
                            <input type="search" class="form-control-tw" id="rewardSearch" placeholder="Cari nama / deskripsi...">
                        </div>
                        <select class="form-select-tw form-select-sm" id="rewardFilterType" style="max-width:190px" aria-label="Filter jenis">
                            <option value="">Semua jenis</option>
                            <option value="voucher">Voucher potong denda</option>
                            <option value="item">Item / hadiah</option>
                        </select>
                        <select class="form-select-tw form-select-sm" id="rewardFilterStatus" style="max-width:170px" aria-label="Filter status">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        <select class="form-select-tw form-select-sm" id="rewardSort" style="max-width:190px" aria-label="Urutkan">
                            <option value="poin-asc">Termurah (poin)</option>
                            <option value="poin-desc">Termahal (poin)</option>
                            <option value="nama-asc">Nama A–Z</option>
                            <option value="stok-asc">Stok menipis</option>
                        </select>
                        <span class="text-xs text-slate-400 ml-auto" id="rewardCount"></span>
                    </div>

                    <?php if (empty($rewards)): ?>
                        <div class="empty-state">
                            <i class="bi bi-gift"></i>
                            <div class="font-bold text-slate-700">Belum ada reward</div>
                            <p class="text-sm mb-3">Buat voucher pemotongan denda atau item penukaran pertama.</p>
                            <button type="button" class="btn-tw btn-tw-sm btn-primary btn-add-reward"><i class="bi bi-plus-lg"></i> Tambah Reward Pertama</button>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3" id="rewardGrid">
                            <?php foreach ($rewards as $r):
                                $isVoucher = isset($r->nominal) && (float) $r->nominal > 0;
                                $stok = (int) $r->stok;
                                $stokPct = min(100, max(0, $stok >= 20 ? 100 : $stok * 5));
                                $stokColor = $stok <= 0 ? '#dc2626' : ($stok <= 5 ? '#d97706' : '#16a34a');
                                $ratio = ($isVoucher && (int) $r->poin_dibutuhkan > 0) ? ((float) $r->nominal / (int) $r->poin_dibutuhkan) : 0;
                            ?>
                            <div class="catalog-item" data-nama="<?= esc(strtolower($r->nama . ' ' . ($r->deskripsi ?? ''))) ?>" data-type="<?= $isVoucher ? 'voucher' : 'item' ?>" data-active="<?= (int) $r->is_active ?>" data-poin="<?= (int) $r->poin_dibutuhkan ?>" data-stok="<?= $stok ?>" data-nama-sort="<?= esc(strtolower($r->nama)) ?>">
                                <article class="catalog-card p-3 <?= (int) $r->is_active ? '' : 'is-inactive' ?>">
                                    <div class="flex justify-between items-start gap-2 mb-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="stat-icon" style="width:38px;height:38px;font-size:1.05rem;<?= $isVoucher ? 'background:#f3e8ff;color:#7e22ce' : 'background:var(--primary-50);color:var(--primary-600)' ?>">
                                                <i class="bi <?= $isVoucher ? 'bi-ticket-perforated-fill' : 'bi-gift-fill' ?>"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <h6 class="font-bold text-slate-800 mb-0 truncate" title="<?= esc($r->nama) ?>"><?= esc($r->nama) ?></h6>
                                                <span class="badge-tw <?= $isVoucher ? 'badge-purple' : 'badge-info' ?> rounded-full" style="font-size:.65rem"><?= $isVoucher ? 'Voucher Potong Denda' : 'Item / Hadiah' ?></span>
                                            </div>
                                        </div>
                                        <span class="badge-tw <?= (int) $r->is_active ? 'badge-success' : 'badge-secondary' ?> rounded-full"><?= (int) $r->is_active ? 'Aktif' : 'Nonaktif' ?></span>
                                    </div>
                                    <p class="text-slate-500 mb-3" style="min-height:36px;font-size:.78rem"><?= esc($r->deskripsi ?: 'Tidak ada deskripsi.') ?></p>
                                    <div class="p-2 rounded-lg mb-2 border bg-slate-50">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs text-slate-400 font-bold">HARGA</span>
                                            <span class="font-bold text-amber-600 text-sm num-tabular"><i class="bi bi-star-fill mr-1"></i><?= number_format((int) $r->poin_dibutuhkan) ?> pts</span>
                                        </div>
                                        <?php if ($isVoucher): ?>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs text-slate-400 font-bold">NILAI</span>
                                            <span class="font-bold text-emerald-600 text-sm num-tabular">Rp <?= number_format((float) $r->nominal, 0, ',', '.') ?></span>
                                        </div>
                                        <?php if ($ratio > 0): ?>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs text-slate-400 font-bold">RASIO</span>
                                            <span class="text-xs text-slate-500 num-tabular">Rp <?= number_format($ratio, 0, ',', '.') ?>/poin</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs text-slate-400 font-bold">STOK</span>
                                            <span class="badge-tw <?= $stok <= 0 ? 'badge-danger' : ($stok <= 5 ? 'badge-warning' : 'badge-info') ?> rounded-full"><?= $stok ?> pcs</span>
                                        </div>
                                        <div class="stock-bar" role="progressbar" aria-valuenow="<?= $stok ?>" aria-valuemin="0" aria-valuemax="20" title="Sisa stok <?= $stok ?>">
                                            <span style="width:<?= $stokPct ?>%;background:<?= $stokColor ?>"></span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 mt-auto">
                                        <button type="button" class="btn-tw btn-tw-sm btn-outline-primary flex-1 justify-center btn-edit-reward" data-reward='<?= esc(json_encode($r), 'attr') ?>' title="Edit reward">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-del-reward" data-id="<?= (int) $r->id ?>" data-nama="<?= esc($r->nama, 'attr') ?>" title="Hapus reward">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </article>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="empty-state hidden" id="rewardEmptySearch" style="display:none">
                            <i class="bi bi-search"></i>
                            <div class="font-bold text-slate-700">Tidak ada hasil</div>
                            <p class="text-sm">Coba kata kunci atau filter lain.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: KLAIM -->
                <div class="tab-pane" id="tab-redemptions" role="tabpanel">
                    <div class="flex flex-wrap justify-between items-end gap-2 mb-3">
                        <div>
                            <h6 class="font-bold text-slate-800 mb-0"><i class="bi bi-hourglass-split text-amber-500 mr-1"></i> Menunggu Persetujuan</h6>
                            <p class="text-slate-400 mb-0" style="font-size:.8rem">Setujui / tolak sebelum voucher dipakai. Penolakan otomatis refund poin + pulihkan stok.</p>
                        </div>
                        <span class="badge-tw badge-warning px-3 py-2 rounded-full num-tabular"><?= count($pending) ?> menunggu</span>
                    </div>
                    <?php if (empty($pending)): ?>
                        <div class="alert-tw alert-success mb-4"><i class="bi bi-check-circle-fill"></i><div>Tidak ada klaim menunggu. Semua pengajuan sudah diproses.</div></div>
                    <?php else: ?>
                        <div class="table-responsive-tw mb-5">
                            <table class="table-tw" id="pendingTable">
                                <thead><tr><th>Tgl Klaim</th><th>Guru</th><th>Reward</th><th class="text-center">Poin</th><th class="text-right">Nilai</th><th class="text-center">Aksi</th></tr></thead>
                                <tbody>
                                <?php foreach ($pending as $p): ?>
                                    <tr>
                                        <td class="text-sm text-slate-500 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                        <td class="font-bold text-slate-800"><?= esc(trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) ?: 'Guru #' . $p->user_id) ?></td>
                                        <td><span class="badge-tw badge-purple rounded-full"><i class="bi bi-gift-fill"></i><?= esc($p->reward_nama ?? 'Reward dihapus') ?></span></td>
                                        <td class="text-center font-bold text-amber-600 num-tabular"><?= number_format((int) $p->poin_digunakan) ?> pts</td>
                                        <td class="text-right font-bold text-emerald-600 num-tabular"><?= isset($p->nominal) && (float) $p->nominal > 0 ? 'Rp ' . number_format((float) $p->nominal, 0, ',', '.') : '–' ?></td>
                                        <td class="text-center whitespace-nowrap">
                                            <button type="button" class="btn-tw btn-tw-sm btn-success btn-approve-redemption" data-id="<?= (int) $p->id ?>"><i class="bi bi-check-circle-fill"></i> Setujui</button>
                                            <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-reject-redemption" data-id="<?= (int) $p->id ?>"><i class="bi bi-x-circle"></i> Tolak</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-wrap justify-between items-end gap-2 mb-3 mt-4">
                        <h6 class="font-bold text-slate-800 mb-0">Riwayat Penukaran</h6>
                        <div class="flex gap-2 items-center flex-wrap">
                            <select id="historyStatusFilter" class="form-select-tw form-select-sm" style="max-width:180px">
                                <option value="">Semua status</option>
                                <option value="PENDING">PENDING</option>
                                <option value="APPROVED">APPROVED</option>
                                <option value="CLAIMED">CLAIMED</option>
                                <option value="REJECTED">REJECTED</option>
                            </select>
                            <div class="input-group-tw" style="max-width:220px">
                                <span class="ig-prepend"><i class="bi bi-search"></i></span>
                                <input type="search" class="form-control-tw" id="historySearch" placeholder="Cari guru / reward...">
                            </div>
                        </div>
                    </div>
                    <?php if (empty($allRedemptions)): ?>
                        <p class="text-slate-400 text-sm">Belum ada riwayat penukaran.</p>
                    <?php else: ?>
                        <div class="table-responsive-tw" style="max-height:420px;overflow-y:auto">
                            <table class="table-tw" id="historyTable" style="font-size:.8rem">
                                <thead><tr><th>Tgl</th><th>Guru</th><th>Reward</th><th class="text-center">Poin</th><th class="text-right">Nilai</th><th class="text-center">Status</th><th>Diproses / Catatan</th></tr></thead>
                                <tbody>
                                <?php foreach ($allRedemptions as $ar):
                                    $sb = 'badge-warning';
                                    if ($ar->status === 'APPROVED') $sb = 'badge-success';
                                    elseif ($ar->status === 'REJECTED') $sb = 'badge-danger';
                                    elseif ($ar->status === 'CLAIMED') $sb = 'badge-info';
                                    $guruNama = $ar->nama_guru ?? trim(($ar->first_name ?? '') . ' ' . ($ar->last_name ?? ''));
                                ?>
                                    <tr data-status="<?= esc($ar->status) ?>" data-search="<?= esc(strtolower(($guruNama . ' ' . ($ar->reward_nama ?? '') . ' ' . ($ar->nip ?? '')))) ?>">
                                        <td class="text-slate-500 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($ar->created_at)) ?></td>
                                        <td><div class="font-bold text-slate-800"><?= esc($guruNama ?: '-') ?></div><div class="text-xs text-slate-400">NIP: <?= esc($ar->nip ?? '-') ?></div></td>
                                        <td><?= esc($ar->reward_nama ?? 'Reward dihapus') ?></td>
                                        <td class="text-center font-bold text-amber-600 num-tabular"><?= number_format((int) $ar->poin_digunakan) ?></td>
                                        <td class="text-right font-bold text-emerald-600 num-tabular"><?= isset($ar->nominal) && (float) $ar->nominal > 0 ? 'Rp ' . number_format((float) $ar->nominal, 0, ',', '.') : '–' ?></td>
                                        <td class="text-center"><span class="badge-tw <?= $sb ?> rounded-full"><?= esc($ar->status) ?></span></td>
                                        <td class="text-sm text-slate-500">
                                            <?php if (!empty($ar->admin_first)): ?><div><i class="bi bi-person-check mr-1"></i><?= esc(trim($ar->admin_first . ' ' . $ar->admin_last)) ?></div><?php endif; ?>
                                            <?php if (!empty($ar->keterangan)): ?><div class="text-xs"><?= esc($ar->keterangan) ?></div><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="empty-state" id="historyEmpty" style="display:none"><i class="bi bi-search"></i><div class="font-bold text-slate-700">Tidak ada hasil</div></div>
                    <?php endif; ?>
                </div>

                <!-- TAB 3: GURU -->
                <div class="tab-pane" id="tab-teachers" role="tabpanel">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div class="p-3 rounded-xl bg-white border flex items-center gap-3">
                            <div class="stat-icon" style="background:#f3e8ff;color:#7e22ce"><i class="bi bi-trophy-fill"></i></div>
                            <div><div class="text-xs font-bold uppercase text-slate-400">Poin Kinerja <?= $currentYear ?></div><div class="font-bold text-slate-800 num-tabular"><?= number_format((float) ($stats['poin_tahunan_total'] ?? 0)) ?> pts</div><div class="text-xs text-slate-400">Neto seluruh guru, termasuk penalti</div></div>
                        </div>
                        <div class="p-3 rounded-xl bg-white border flex items-center gap-3">
                            <div class="stat-icon" style="background:var(--primary-50);color:var(--primary-600)"><i class="bi bi-bar-chart-fill"></i></div>
                            <div><div class="text-xs font-bold uppercase text-slate-400">Rata-rata Guru</div><div class="font-bold text-slate-800 num-tabular"><?= number_format((float) ($stats['poin_tahunan_avg'] ?? 0), 1) ?> pts</div><div class="text-xs text-slate-400">Per guru aktif</div></div>
                        </div>
                        <div class="p-3 rounded-xl bg-white border flex items-center gap-3">
                            <div class="stat-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-cash-coin"></i></div>
                            <div><div class="text-xs font-bold uppercase text-slate-400">Saldo Beredar</div><div class="font-bold text-slate-800 num-tabular"><?= number_format((float) ($stats['total_poin_beredar'] ?? 0)) ?> pts</div><div class="text-xs text-slate-400">Akumulasi saldo positif</div></div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-between items-end gap-2 mb-3">
                        <div><h6 class="font-bold text-slate-800 mb-0">Saldo &amp; Kedisiplinan Guru</h6><p class="text-slate-400 mb-0" style="font-size:.8rem">Bonus / penalti manual tercatat sebagai jejak audit. Maks 1.000 pts per transaksi.</p></div>
                        <div class="flex gap-2 items-center flex-wrap">
                            <button type="button" class="btn-tw btn-tw-sm btn-outline-success" id="btnSeedPoin"><i class="bi bi-arrow-repeat"></i> Sinkron Tahunan</button>
                        </div>
                    </div>

                    <div class="table-responsive-tw" style="max-height:56vh;overflow-y:auto">
                        <table class="table-tw" id="teacherTable">
                            <thead><tr><th class="text-center">#</th><th>Guru</th><th>NIP</th><th class="text-center">Tier</th><th class="text-center">Poin <?= $currentYear ?></th><th class="text-center">Saldo</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($teachers as $t):
                                $pb = (int) $t->saldo < 0 ? 'badge-danger' : ((int) $t->saldo > 0 ? 'badge-success' : 'badge-secondary');
                                $rankCls = ((int) $t->rank_tahunan === 1) ? 'background:#fbbf24;color:#78350f' : (((int) $t->rank_tahunan === 2) ? 'background:#cbd5e1;color:#334155' : (((int) $t->rank_tahunan === 3) ? 'background:#fdba74;color:#7c2d12' : 'background:#f1f5f9;color:#64748b'));
                            ?>
                                <tr data-search="<?= esc(strtolower($t->nama . ' ' . $t->nip)) ?>">
                                    <td class="text-center"><span class="inline-flex items-center justify-center rounded-full font-bold text-xs num-tabular" style="width:28px;height:28px;<?= $rankCls ?>"><?= (int) $t->rank_tahunan ?></span></td>
                                    <td class="font-bold text-slate-800"><?= esc($t->nama) ?></td>
                                    <td class="text-slate-500 text-sm"><?= esc($t->nip ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($t->tier)):
                                            $tc = !empty($t->tier->warna) ? $t->tier->warna : '#64748b';
                                        ?>
                                            <span class="badge-tw rounded-full" style="background:<?= esc($tc) ?>20;color:<?= esc($tc) ?>;border:1px solid <?= esc($tc) ?>60"><i class="bi <?= esc($t->tier->badge ?? 'bi-star-fill') ?>"></i><?= esc($t->tier->nama_tier) ?></span>
                                        <?php else: ?><span class="badge-tw badge-secondary rounded-full">–</span><?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="badge-tw <?= (float) $t->poin_tahunan > 0 ? 'badge-info' : ((float) $t->poin_tahunan < 0 ? 'badge-danger' : 'badge-secondary') ?> rounded-full num-tabular"><?= (float) $t->poin_tahunan > 0 ? '+' . number_format((float) $t->poin_tahunan, 1) : number_format((float) $t->poin_tahunan, 1) ?> pts</span></td>
                                    <td class="text-center"><span class="badge-tw <?= $pb ?> rounded-full num-tabular"><i class="bi bi-star-fill text-amber-500"></i><?= (int) $t->saldo > 0 ? '+' . number_format((int) $t->saldo) : number_format((int) $t->saldo) ?> pts</span></td>
                                    <td class="text-center whitespace-nowrap">
                                        <div class="dropdown" style="display:inline-block">
                                            <button class="btn-tw btn-tw-sm btn-outline-secondary" data-ui-toggle="dropdown" aria-haspopup="true"><i class="bi bi-three-dots"></i> Kelola</button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item dd-adjust" href="#" data-id="<?= (int) $t->user_id ?>" data-nama="<?= esc($t->nama, 'attr') ?>" data-saldo="<?= (int) $t->saldo ?>"><i class="bi bi-plus-slash-minus text-primary"></i> Sesuaikan Poin</a>
                                                <a class="dropdown-item dd-history" href="#" data-id="<?= (int) $t->user_id ?>" data-nama="<?= esc($t->nama, 'attr') ?>"><i class="bi bi-clock-history text-secondary"></i> Riwayat Mutasi</a>
                                                <a class="dropdown-item dd-annual" href="#" data-id="<?= (int) $t->user_id ?>" data-nama="<?= esc($t->nama, 'attr') ?>"><i class="bi bi-pie-chart text-info"></i> Rincian <?= $currentYear ?></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: TIER -->
                <div class="tab-pane" id="tab-tiers" role="tabpanel">
                    <div class="flex flex-wrap justify-between items-end gap-2 mb-4">
                        <div><h6 class="font-bold text-slate-800 mb-0">Jenjang Tier Kedisiplinan</h6><p class="text-slate-400 mb-0" style="font-size:.8rem">Rentang tidak boleh tumpang tindih. <code>0</code> pada maks = tak terhingga.</p></div>
                        <button type="button" class="btn-tw btn-tw-sm btn-primary btn-add-tier"><i class="bi bi-plus-circle-fill"></i> Tambah Tier</button>
                    </div>
                    <?php if (!empty($tiers)): ?>
                    <div class="flex items-center gap-2 mb-4" style="overflow-x:auto;padding-bottom:4px" aria-label="Tangga tier">
                        <?php foreach ($tiers as $i => $tier): $tc = $tier->warna ?: '#64748b'; ?>
                            <div class="flex items-center gap-2" style="flex-shrink:0">
                                <div class="flex items-center gap-2 px-3 py-2 rounded-xl border bg-white" title="<?= esc($tier->nama_tier) ?>: <?= number_format((int) $tier->poin_min) ?>–<?= ((int) $tier->poin_max > 0 ? number_format((int) $tier->poin_max) : '∞') ?>">
                                    <span class="tier-badge-preview" style="background:<?= esc($tc) ?>20;color:<?= esc($tc) ?>;border:1px solid <?= esc($tc) ?>50"><i class="bi <?= esc($tier->badge ?: 'bi-star-fill') ?>"></i></span>
                                    <div><div class="font-bold text-slate-800" style="font-size:.8rem"><?= esc($tier->nama_tier) ?></div><div class="text-xs text-slate-400 num-tabular"><?= number_format((int) $tier->poin_min) ?>–<?= ((int) $tier->poin_max > 0 ? number_format((int) $tier->poin_max) : '∞') ?></div></div>
                                </div>
                                <?php if ($i < count($tiers) - 1): ?><i class="bi bi-chevron-right text-slate-300"></i><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="table-responsive-tw">
                        <table class="table-tw" id="tierTable">
                            <thead><tr><th class="text-center" style="width:56px">Badge</th><th>Nama Tier</th><th class="text-center">Rentang Poin</th><th class="text-center">Warna</th><th class="text-center" style="width:110px">Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($tiers as $tier): $tc = $tier->warna ?: '#64748b'; ?>
                                <tr>
                                    <td class="text-center"><span class="tier-badge-preview" style="background:<?= esc($tc) ?>20;color:<?= esc($tc) ?>;border:1px solid <?= esc($tc) ?>50"><i class="bi <?= esc($tier->badge ?: 'bi-star-fill') ?>"></i></span></td>
                                    <td class="font-bold text-slate-800"><?= esc($tier->nama_tier) ?></td>
                                    <td class="text-center num-tabular"><span class="badge-tw badge-primary rounded-full"><?= number_format((int) $tier->poin_min) ?> s/d <?= ((int) $tier->poin_max > 0 ? number_format((int) $tier->poin_max) . ' pts' : '∞') ?></span></td>
                                    <td class="text-center"><span class="badge-tw rounded-full" style="background:<?= esc($tc) ?>;color:#fff"><?= esc($tc) ?></span></td>
                                    <td class="text-center whitespace-nowrap">
                                        <button type="button" class="btn-tw btn-tw-sm btn-outline-primary btn-edit-tier" data-tier='<?= esc(json_encode($tier), 'attr') ?>' title="Edit tier"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn-tw btn-tw-sm btn-outline-danger btn-del-tier" data-id="<?= (int) $tier->id ?>" data-nama="<?= esc($tier->nama_tier, 'attr') ?>" title="Hapus tier"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REWARD -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="rewardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="rewardForm" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header-tw">
                <h5 id="rewardModalTitle"><i class="bi bi-gift mr-2"></i>Kelola Reward</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <input type="hidden" name="id" id="reward_id">
                <div class="mb-3">
                    <label class="form-label-tw" for="reward_nama">Nama Reward / Voucher <span class="text-red-500">*</span></label>
                    <input type="text" class="form-control-tw" name="nama" id="reward_nama" minlength="3" maxlength="255" required placeholder="Contoh: Voucher Potongan Denda Rp 50.000">
                </div>
                <div class="mb-3">
                    <label class="form-label-tw" for="reward_deskripsi">Deskripsi</label>
                    <textarea class="form-control-tw" name="deskripsi" id="reward_deskripsi" rows="2" maxlength="2000" placeholder="Ketentuan penukaran..."></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label-tw" for="reward_poin">Poin Dibutuhkan <span class="text-red-500">*</span></label>
                        <input type="number" class="form-control-tw" name="poin_dibutuhkan" id="reward_poin" min="1" max="100000" required placeholder="50">
                        <div class="text-xs text-slate-400 mt-1">Min 1 — tidak boleh gratis.</div>
                    </div>
                    <div>
                        <label class="form-label-tw" for="reward_stok">Stok <span class="text-red-500">*</span></label>
                        <input type="number" class="form-control-tw" name="stok" id="reward_stok" min="0" max="100000" required placeholder="10">
                        <div class="text-xs text-slate-400 mt-1">Stok 0 → otomatis nonaktif.</div>
                    </div>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg mb-3 border">
                    <label class="form-label-tw" for="reward_nominal">Nominal Potongan Denda (Rp)</label>
                    <input type="number" class="form-control-tw" name="nominal" id="reward_nominal" min="0" max="100000000" step="1000" value="0">
                    <div class="text-xs text-slate-400 mt-1">Isi &gt; 0 jika <b>voucher denda</b>. Isi <b>0</b> untuk barang fisik.</div>
                    <div id="rewardPreview" class="mt-2 text-xs"></div>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input type="checkbox" class="peer sr-only" name="is_active" id="reward_is_active" value="1" checked>
                    <span class="toggle-switch relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 transition-colors peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40 after:content-[''] after:absolute after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-4"></span>
                    <span class="text-sm font-bold text-slate-800">Aktifkan di katalog guru</span>
                </label>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-tw-sm btn-primary" id="btnSaveReward"><i class="bi bi-check2-circle"></i> Simpan Reward</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TIER -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="tierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="tierForm" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header-tw">
                <h5 id="tierModalTitle"><i class="bi bi-award mr-2"></i>Kelola Tier</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <input type="hidden" name="id" id="tier_id">
                <div class="mb-3">
                    <label class="form-label-tw" for="tier_nama">Nama Tier <span class="text-red-500">*</span></label>
                    <input type="text" class="form-control-tw" name="nama_tier" id="tier_nama" minlength="2" maxlength="50" required placeholder="Contoh: Gold">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label-tw" for="tier_poin_min">Poin Minimal <span class="text-red-500">*</span></label>
                        <input type="number" class="form-control-tw" name="poin_min" id="tier_poin_min" min="0" max="1000000" required placeholder="100">
                    </div>
                    <div>
                        <label class="form-label-tw" for="tier_poin_max">Poin Maksimal</label>
                        <input type="number" class="form-control-tw" name="poin_max" id="tier_poin_max" min="0" max="10000000" placeholder="500 (0 = ∞)">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label-tw" for="tier_badge">Icon Badge</label>
                        <input type="text" class="form-control-tw" name="badge" id="tier_badge" pattern="bi-[a-z0-9-]+" placeholder="bi-trophy-fill">
                        <div class="text-xs text-slate-400 mt-1">Contoh: bi-trophy-fill, bi-star-fill, bi-gem</div>
                    </div>
                    <div>
                        <label class="form-label-tw" for="tier_warna">Warna Tier</label>
                        <div class="flex gap-2 items-center">
                            <input type="color" class="form-control-tw" name="warna" id="tier_warna" value="#eab308" style="max-width:64px;padding:.25rem">
                            <input type="text" class="form-control-tw" id="tier_warna_hex" value="#eab308" pattern="#[0-9a-fA-F]{6}" maxlength="7">
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border text-sm">
                    <div class="text-xs font-bold uppercase text-slate-400 mb-2">Pratinjau</div>
                    <div id="tierPreview"></div>
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-tw-sm btn-primary" id="btnSaveTier"><i class="bi bi-check2-circle"></i> Simpan Tier</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ADJUST -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="adjustPoinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="adjustPoinForm" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header-tw">
                <h5><i class="bi bi-plus-slash-minus mr-2"></i>Penyesuaian Poin Guru</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <input type="hidden" name="user_id" id="adj_user_id">
                <div class="text-center mb-4">
                    <h5 class="font-bold text-slate-800 mb-0" id="adj_nama">–</h5>
                    <div class="text-slate-400 text-sm">Saldo saat ini: <span class="font-bold text-blue-600 num-tabular" id="adj_saldo_text">0 pts</span></div>
                </div>
                <div class="mb-3">
                    <label class="form-label-tw" for="adj_tipe">Tipe <span class="text-red-500">*</span></label>
                    <select name="tipe" id="adj_tipe" class="form-select-tw">
                        <option value="BONUS">Tambah poin bonus (prestasi)</option>
                        <option value="PENALTY">Kurangi poin (pelanggaran / koreksi)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label-tw" for="adj_poin">Nominal Poin <span class="text-red-500">*</span></label>
                    <input type="number" class="form-control-tw" name="poin" id="adj_poin" min="1" max="1000" required placeholder="Contoh: 25">
                    <div class="text-xs text-slate-400 mt-1">Maks 1.000 pts per transaksi. Saldo tidak boleh &lt; −1.000.</div>
                </div>
                <div class="mb-2">
                    <label class="form-label-tw" for="adj_keterangan">Keterangan / Alasan <span class="text-red-500">*</span></label>
                    <input type="text" class="form-control-tw" name="keterangan" id="adj_keterangan" minlength="5" maxlength="255" required placeholder="Contoh: Bonus kepanitiaan ujian sekolah">
                </div>
                <div id="adjustPreview" class="mt-2 text-sm"></div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-tw-sm btn-success" id="btnSubmitAdjust"><i class="bi bi-check2-circle"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HISTORY -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-2xl">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5><i class="bi bi-clock-history mr-2"></i>Riwayat Mutasi Poin</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                    <div><h5 class="font-bold text-slate-800 mb-0" id="hist_nama">–</h5><span class="text-sm text-slate-400">Transaksi penambahan &amp; pemotongan poin</span></div>
                    <div class="text-right"><span class="text-xs text-slate-400 font-bold block">TOTAL SALDO</span><span class="text-xl font-bold text-blue-600 num-tabular" id="hist_saldo">0 pts</span></div>
                </div>
                <div class="table-responsive-tw" style="max-height:350px;overflow-y:auto">
                    <table class="table-tw" style="font-size:.8rem">
                        <thead><tr><th>Waktu</th><th>Tipe</th><th>Keterangan</th><th class="text-right">Poin</th></tr></thead>
                        <tbody id="hist_table_body"><tr><td colspan="4" class="text-center py-8 text-slate-400">Memuat data...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer-tw"><button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

<!-- MODAL ANNUAL -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="annualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-2xl">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5><i class="bi bi-pie-chart mr-2"></i>Rincian Kinerja Tahunan</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                    <div><h5 class="font-bold text-slate-800 mb-0" id="annual_nama">–</h5><span class="text-sm text-slate-400">Kinerja disiplin tahun <span class="font-bold text-slate-600" id="annual_tahun">–</span></span></div>
                    <div class="text-right"><span class="text-xs text-slate-400 font-bold block">TOTAL TAHUNAN</span><span class="text-xl font-bold text-blue-600 num-tabular" id="annual_saldo">0 pts</span></div>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg mb-4 border">
                    <div class="text-xs font-bold uppercase text-slate-400 mb-2">Tren bulanan (hijau = positif, merah = negatif)</div>
                    <div class="annual-bars" id="annualChart"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><div class="text-xs font-bold uppercase text-slate-400 mb-2">Ringkasan per tipe</div><div id="annualTypes" class="text-sm"></div></div>
                    <div class="table-responsive-tw" style="max-height:220px;overflow-y:auto">
                        <table class="table-tw" style="font-size:.8rem">
                            <thead><tr><th>Waktu</th><th>Tipe</th><th class="text-right">Poin</th></tr></thead>
                            <tbody id="annualTableBody"><tr><td colspan="3" class="text-center py-8 text-slate-400">Memuat data...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer-tw"><button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

<!-- MODAL REJECT -->
<div class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-4" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-box-tw w-full max-w-lg">
        <form id="rejectForm" novalidate>
            <?= csrf_field() ?>
            <div class="modal-header-tw">
                <h5><i class="bi bi-x-circle mr-2"></i>Tolak Klaim</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body-tw">
                <input type="hidden" name="redemption_id" id="reject_redemption_id">
                <div class="alert-tw alert-info mb-3"><i class="bi bi-info-circle-fill"></i><div>Poin otomatis <b>direfund</b> dan <b>stok dipulihkan</b>. Tindakan tercatat sebagai audit.</div></div>
                <div class="mb-2">
                    <label class="form-label-tw" for="reject_alasan">Alasan penolakan <span class="text-red-500">*</span></label>
                    <input type="text" class="form-control-tw" name="alasan" id="reject_alasan" minlength="5" maxlength="500" required placeholder="Contoh: Stok fisik habis / salah pilih voucher">
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-tw-sm btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-tw-sm btn-danger"><i class="bi bi-x-circle"></i> Konfirmasi Tolak</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    'use strict';
    // CSRF_NAME & CSRF_HASH sudah global dari layouts/main.php — jangan dideklarasikan ulang.
    var adjSaldo = 0;

    function csrfData() { var d = {}; d[CSRF_NAME] = CSRF_HASH; return d; }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function fmtNum(n) { return new Intl.NumberFormat('id-ID').format(Number(n) || 0); }
    function fmtRp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(n) || 0); }
    function fmtDate(s) {
        if (!s) return '–';
        var d = new Date(String(s).replace(' ', 'T'));
        if (isNaN(d)) return esc(s);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
               d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }
    function tipeBadge(t) {
        var up = String(t || 'INFO').toUpperCase();
        var cls = 'badge-secondary';
        if (['HADIR','REWARD','BONUS','REFUND'].indexOf(up) >= 0) cls = 'badge-success';
        else if (['TELAT','PENALTI','PENALTY','ALPA','ALPHA'].indexOf(up) >= 0) cls = 'badge-danger';
        else if (['REDEEM','CLAIMED','PENDING'].indexOf(up) >= 0) cls = 'badge-warning';
        else if (['IZIN','SAKIT','CUTI','DINAS'].indexOf(up) >= 0) cls = 'badge-info';
        return '<span class="badge-tw ' + cls + ' rounded-full">' + esc(t || 'INFO') + '</span>';
    }
    function reloadKeepTab() {
        var active = document.querySelector('.nav-tabs .nav-link.active');
        var hash = active ? active.getAttribute('href') : '#tab-rewards';
        location.hash = hash;
        location.reload();
    }
    function restoreTab() {
        var h = location.hash;
        if (h) {
            var link = document.querySelector('.nav-tabs .nav-link[href="' + h + '"]');
            if (link && window.AdminUI) AdminUI.tabs.activate(link);
        }
    }

    /* ---------- Katalog: cari / filter / sortir ---------- */
    window.filterCatalog = function() {
        var kw = (document.getElementById('rewardSearch').value || '').toLowerCase().trim();
        var ft = document.getElementById('rewardFilterType').value;
        var fs = document.getElementById('rewardFilterStatus').value;
        var sort = document.getElementById('rewardSort').value;
        var grid = document.getElementById('rewardGrid');
        if (!grid) return;
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.catalog-item'));
        var shown = 0;
        cards.forEach(function(c) {
            var ok = true;
            if (kw && (c.getAttribute('data-nama') || '').indexOf(kw) < 0) ok = false;
            if (ft && c.getAttribute('data-type') !== ft) ok = false;
            if (fs !== '' && c.getAttribute('data-active') !== fs) ok = false;
            c.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        cards.filter(function(c) { return c.style.display !== 'none'; }).sort(function(a, b) {
            if (sort === 'poin-desc') return (+b.dataset.poin) - (+a.dataset.poin);
            if (sort === 'nama-asc') return String(a.dataset.namaSort).localeCompare(String(b.dataset.namaSort));
            if (sort === 'stok-asc') return (+a.dataset.stok) - (+b.dataset.stok);
            return (+a.dataset.poin) - (+b.dataset.poin);
        }).forEach(function(c) { grid.appendChild(c); });
        document.getElementById('rewardCount').textContent = shown + ' dari ' + cards.length + ' item';
        document.getElementById('rewardEmptySearch').style.display = shown === 0 ? '' : 'none';
    };

    /* ---------- Riwayat klaim: filter ---------- */
    window.filterHistoryTable = function() {
        var st = document.getElementById('historyStatusFilter').value;
        var kw = (document.getElementById('historySearch').value || '').toLowerCase().trim();
        var rows = document.querySelectorAll('#historyTable tbody tr');
        var shown = 0;
        rows.forEach(function(r) {
            var ok = true;
            if (st && r.getAttribute('data-status') !== st) ok = false;
            if (kw && (r.getAttribute('data-search') || '').indexOf(kw) < 0) ok = false;
            r.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        document.getElementById('historyEmpty').style.display = shown === 0 ? '' : 'none';
    };

    /* ---------- Reward modal ---------- */
    window.previewReward = function() {
        var poin = Math.max(0, parseInt(document.getElementById('reward_poin').value || '0', 10));
        var stok = Math.max(0, parseInt(document.getElementById('reward_stok').value || '0', 10));
        var nom = Math.max(0, parseFloat(document.getElementById('reward_nominal').value || '0'));
        var html = '';
        if (nom > 0 && poin > 0) html += '<span class="badge-tw badge-purple">Voucher: ' + fmtRp(nom) + ' / ' + fmtNum(poin) + ' pts (≈ ' + fmtRp(nom / poin) + '/poin)</span> ';
        else if (nom > 0) html += '<span class="badge-tw badge-purple">Voucher: ' + fmtRp(nom) + '</span> ';
        else html += '<span class="badge-tw badge-info">Item fisik / merchandise</span> ';
        if (stok <= 0) html += '<span class="badge-tw badge-danger">Stok 0 → akan nonaktif otomatis</span>';
        else if (stok <= 5) html += '<span class="badge-tw badge-warning">Stok menipis (' + stok + ')</span>';
        document.getElementById('rewardPreview').innerHTML = html;
    };
    window.openRewardModal = function(data) {
        var form = document.getElementById('rewardForm');
        form.reset();
        if (data && data.id) {
            document.getElementById('rewardModalTitle').innerHTML = '<i class="bi bi-pencil-square mr-2"></i>Edit Reward';
            document.getElementById('reward_id').value = data.id;
            document.getElementById('reward_nama').value = data.nama || '';
            document.getElementById('reward_deskripsi').value = data.deskripsi || '';
            document.getElementById('reward_poin').value = data.poin_dibutuhkan || 1;
            document.getElementById('reward_stok').value = data.stok || 0;
            document.getElementById('reward_nominal').value = data.nominal || 0;
            document.getElementById('reward_is_active').checked = String(data.is_active) === '1';
        } else {
            document.getElementById('rewardModalTitle').innerHTML = '<i class="bi bi-gift mr-2"></i>Tambah Reward Baru';
            document.getElementById('reward_id').value = '';
            document.getElementById('reward_is_active').checked = true;
        }
        previewReward();
        $('#rewardModal').appModal('show');
    };
    window.saveReward = function(e) {
        e.preventDefault();
        var form = $('#rewardForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return false; }
        var btn = $('#btnSaveReward');
        var orig = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-tw"></span> Menyimpan...');
        $.ajax({
            url: '<?= site_url('admin/reward/saveReward') ?>',
            type: 'POST',
            data: form.serialize() + '&' + encodeURIComponent(CSRF_NAME) + '=' + encodeURIComponent(CSRF_HASH),
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    $('#rewardModal').appModal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1300, showConfirmButton: false }).then(reloadKeepTab);
                } else { Swal.fire('Gagal', res.message, 'error'); btn.prop('disabled', false).html(orig); }
            },
            error: function() { Swal.fire('Error', 'Terjadi kesalahan sistem', 'error'); btn.prop('disabled', false).html(orig); }
        });
        return false;
    };
    window.deleteReward = function(id, nama) {
        Swal.fire({
            title: 'Hapus Reward?', text: 'Reward "' + nama + '" akan dihapus permanen. Jika sudah ada riwayat penukaran, gunakan nonaktifkan.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function(r) {
            if (r.isConfirmed) $.post('<?= site_url('admin/reward/deleteReward') ?>/' + id, csrfData(), function(res) {
                if (res.status) Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1300, showConfirmButton: false }).then(reloadKeepTab);
                else Swal.fire('Gagal', res.message, 'error');
            }, 'json');
        });
    };

    /* ---------- Tier ---------- */
    window.previewTier = function() {
        var nama = document.getElementById('tier_nama').value || 'Nama Tier';
        var pmin = document.getElementById('tier_poin_min').value || '0';
        var pmax = document.getElementById('tier_poin_max').value || '0';
        var badge = document.getElementById('tier_badge').value || 'bi-star-fill';
        var warna = document.getElementById('tier_warna').value || '#64748b';
        if (/^#[0-9a-fA-F]{6}$/.test(document.getElementById('tier_warna_hex').value)) warna = document.getElementById('tier_warna_hex').value;
        document.getElementById('tierPreview').innerHTML =
            '<span class="preview-chip" style="background:' + esc(warna) + '20;color:' + esc(warna) + ';border:1px solid ' + esc(warna) + '60">' +
            '<i class="bi ' + esc(badge) + '"></i>' + esc(nama) + ' &middot; ' + esc(pmin) + '–' + (pmax === '0' || pmax === '' ? '∞' : esc(pmax)) + '</span>';
    };
    window.openTierModal = function(data) {
        document.getElementById('tierForm').reset();
        if (data && data.id) {
            document.getElementById('tierModalTitle').innerHTML = '<i class="bi bi-pencil-square mr-2"></i>Edit Tier';
            document.getElementById('tier_id').value = data.id;
            document.getElementById('tier_nama').value = data.nama_tier || '';
            document.getElementById('tier_poin_min').value = data.poin_min || 0;
            document.getElementById('tier_poin_max').value = data.poin_max || 0;
            document.getElementById('tier_badge').value = data.badge || 'bi-star-fill';
            document.getElementById('tier_warna').value = data.warna || '#eab308';
            document.getElementById('tier_warna_hex').value = data.warna || '#eab308';
        } else {
            document.getElementById('tierModalTitle').innerHTML = '<i class="bi bi-award mr-2"></i>Tambah Tier Baru';
            document.getElementById('tier_id').value = '';
            document.getElementById('tier_badge').value = 'bi-star-fill';
            document.getElementById('tier_warna').value = '#3b82f6';
            document.getElementById('tier_warna_hex').value = '#3b82f6';
        }
        previewTier();
        $('#tierModal').appModal('show');
    };
    window.saveTier = function(e) {
        e.preventDefault();
        var form = $('#tierForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return false; }
        var btn = $('#btnSaveTier');
        var orig = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-tw"></span> Menyimpan...');
        $.ajax({
            url: '<?= site_url('admin/reward/saveTier') ?>',
            type: 'POST',
            data: form.serialize() + '&' + encodeURIComponent(CSRF_NAME) + '=' + encodeURIComponent(CSRF_HASH),
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    $('#tierModal').appModal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1300, showConfirmButton: false }).then(reloadKeepTab);
                } else { Swal.fire('Gagal', res.message, 'error'); btn.prop('disabled', false).html(orig); }
            },
            error: function() { Swal.fire('Error', 'Terjadi kesalahan sistem', 'error'); btn.prop('disabled', false).html(orig); }
        });
        return false;
    };
    window.deleteTier = function(id, nama) {
        Swal.fire({ title: 'Hapus Tier?', text: 'Tier "' + nama + '" akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal' })
        .then(function(r) {
            if (r.isConfirmed) $.post('<?= site_url('admin/reward/deleteTier') ?>/' + id, csrfData(), function(res) {
                if (res.status) Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1200, showConfirmButton: false }).then(reloadKeepTab);
                else Swal.fire('Gagal', res.message, 'error');
            }, 'json');
        });
    };

    /* ---------- Approval ---------- */
    window.approveRedemption = function(id) {
        Swal.fire({ title: 'Setujui Klaim?', text: 'Voucher langsung dapat dipakai guru untuk potong denda.', icon: 'question', showCancelButton: true, confirmButtonColor: '#198754', confirmButtonText: 'Ya, Setujui', cancelButtonText: 'Batal' })
        .then(function(r) {
            if (r.isConfirmed) $.post('<?= site_url('admin/reward/approveRedemption') ?>/' + id, csrfData(), function(res) {
                if (res.status) Swal.fire({ icon: 'success', title: 'Disetujui!', text: res.message, timer: 1300, showConfirmButton: false }).then(reloadKeepTab);
                else Swal.fire('Gagal', res.message, 'error');
            }, 'json');
        });
    };
    window.rejectRedemptionModal = function(id) {
        document.getElementById('reject_redemption_id').value = id;
        document.getElementById('reject_alasan').value = '';
        $('#rejectModal').appModal('show');
    };
    window.submitReject = function(e) {
        e.preventDefault();
        var form = $('#rejectForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return false; }
        var id = document.getElementById('reject_redemption_id').value;
        $.post('<?= site_url('admin/reward/rejectRedemption') ?>/' + id, { alasan: document.getElementById('reject_alasan').value, [CSRF_NAME]: CSRF_HASH }, function(res) {
            if (res.status) { $('#rejectModal').appModal('hide'); Swal.fire({ icon: 'success', title: 'Ditolak & Refund!', text: res.message, timer: 1500, showConfirmButton: false }).then(reloadKeepTab); }
            else Swal.fire('Gagal', res.message, 'error');
        }, 'json');
        return false;
    };

    /* ---------- Adjust poin ---------- */
    window.openAdjustModal = function(userId, nama, saldo) {
        adjSaldo = parseInt(saldo || '0', 10);
        document.getElementById('adj_user_id').value = userId;
        document.getElementById('adj_nama').textContent = nama;
        document.getElementById('adj_saldo_text').textContent = (adjSaldo > 0 ? '+' : '') + fmtNum(adjSaldo) + ' pts';
        document.getElementById('adj_poin').value = '';
        document.getElementById('adj_keterangan').value = '';
        document.getElementById('adjustPreview').innerHTML = '';
        $('#adjustPoinModal').appModal('show');
    };
    window.previewAdjust = function() {
        var tipe = document.getElementById('adj_tipe').value;
        var poin = Math.max(0, parseInt(document.getElementById('adj_poin').value || '0', 10));
        if (!poin) { document.getElementById('adjustPreview').innerHTML = ''; return; }
        var baru = adjSaldo + (tipe === 'PENALTY' ? -poin : poin);
        var warn = baru < -1000 ? ' <span class="badge-tw badge-danger">Melebihi batas −1.000, akan ditolak</span>' : (baru < 0 ? ' <span class="badge-tw badge-warning">Saldo menjadi negatif</span>' : '');
        document.getElementById('adjustPreview').innerHTML = 'Saldo baru: <strong class="num-tabular">' + (baru > 0 ? '+' : '') + fmtNum(baru) + ' pts</strong>' + warn;
    };
    window.submitAdjustPoin = function(e) {
        e.preventDefault();
        var form = $('#adjustPoinForm');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return false; }
        var btn = $('#btnSubmitAdjust');
        var orig = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-tw"></span> Menyimpan...');
        $.ajax({
            url: '<?= site_url('admin/reward/adjustPoin') ?>',
            type: 'POST',
            data: form.serialize() + '&' + encodeURIComponent(CSRF_NAME) + '=' + encodeURIComponent(CSRF_HASH),
            dataType: 'json',
            success: function(res) {
                if (res.status) { $('#adjustPoinModal').appModal('hide'); Swal.fire({ icon: 'success', title: 'Poin Disesuaikan!', text: res.message, timer: 1500, showConfirmButton: false }).then(reloadKeepTab); }
                else { Swal.fire('Gagal', res.message, 'error'); btn.prop('disabled', false).html(orig); }
            },
            error: function() { Swal.fire('Error', 'Terjadi kesalahan sistem', 'error'); btn.prop('disabled', false).html(orig); }
        });
        return false;
    };

    /* ---------- History & Annual ---------- */
    window.openHistoryModal = function(userId, nama) {
        document.getElementById('hist_nama').textContent = nama;
        document.getElementById('hist_saldo').textContent = '…';
        document.getElementById('hist_table_body').innerHTML = '<tr><td colspan="4" class="text-center py-8 text-slate-400"><span class="spinner-tw spinner-tw-lite"></span> Memuat...</td></tr>';
        $('#historyModal').appModal('show');
        $.get('<?= site_url('admin/reward/getTeacherHistory') ?>/' + userId, function(res) {
            if (!res.status) { document.getElementById('hist_table_body').innerHTML = '<tr><td colspan="4" class="text-center text-red-500 py-4">' + esc(res.message) + '</td></tr>'; return; }
            document.getElementById('hist_saldo').textContent = ((+res.data.saldo) > 0 ? '+' : '') + fmtNum(res.data.saldo) + ' pts';
            if (!res.data.history || !res.data.history.length) { document.getElementById('hist_table_body').innerHTML = '<tr><td colspan="4" class="text-center py-8 text-slate-400">Belum ada transaksi.</td></tr>'; return; }
            document.getElementById('hist_table_body').innerHTML = res.data.history.map(function(h) {
                var p = parseInt(h.poin || '0', 10);
                return '<tr><td class="text-slate-500 whitespace-nowrap">' + esc(fmtDate(h.created_at)) + '</td><td>' + tipeBadge(h.tipe) + '</td><td>' + esc(h.keterangan || '–') + '</td>' +
                       '<td class="text-right font-bold num-tabular ' + (p >= 0 ? 'text-emerald-600' : 'text-red-500') + '">' + (p >= 0 ? '+' : '') + fmtNum(p) + ' pts</td></tr>';
            }).join('');
        }, 'json').fail(function() { document.getElementById('hist_table_body').innerHTML = '<tr><td colspan="4" class="text-center text-red-500 py-4">Gagal memuat data</td></tr>'; });
    };

    window.openAnnualModal = function(userId, nama) {
        var bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        document.getElementById('annual_nama').textContent = nama;
        document.getElementById('annual_tahun').textContent = '<?= $currentYear ?>';
        document.getElementById('annual_saldo').textContent = '…';
        document.getElementById('annualChart').innerHTML = '';
        document.getElementById('annualTypes').innerHTML = '<span class="text-slate-400">Memuat...</span>';
        document.getElementById('annualTableBody').innerHTML = '<tr><td colspan="3" class="text-center py-8 text-slate-400"><span class="spinner-tw spinner-tw-lite"></span> Memuat...</td></tr>';
        $('#annualModal').appModal('show');
        $.get('<?= site_url('admin/reward/getTeacherAnnual') ?>/' + userId, function(res) {
            if (!res.status) { document.getElementById('annualTableBody').innerHTML = '<tr><td colspan="3" class="text-center text-red-500 py-4">' + esc(res.message) + '</td></tr>'; return; }
            var total = Number(res.data.saldo_tahunan) || 0;
            document.getElementById('annual_saldo').textContent = (total > 0 ? '+' : '') + fmtNum(total) + ' pts';
            var monthly = res.data.monthly || [];
            var max = Math.max.apply(null, monthly.map(function(v) { return Math.abs(Number(v) || 0); }).concat([1]));
            var half = 50;
            var html = '';
            for (var m = 0; m < 12; m++) {
                var val = Number(monthly[m]) || 0;
                var hPct = Math.max(2, Math.round(Math.abs(val) / max * half));
                var bar = val === 0
                    ? '<div class="annual-zero"></div>'
                    : (val > 0
                        ? '<div class="annual-bar-pos" style="height:' + hPct + 'px;background:#10b981"></div><div class="annual-zero"></div><div style="height:' + half + 'px"></div>'
                        : '<div style="height:' + half + 'px"></div><div class="annual-zero"></div><div class="annual-bar-neg" style="height:' + hPct + 'px;background:#ef4444"></div>');
                html += '<div class="annual-col"><span class="annual-val ' + (val >= 0 ? 'text-emerald-600' : 'text-red-500') + '">' + fmtNum(val) + '</span>' +
                        '<div class="flex flex-col justify-center w-full" style="height:104px">' + bar + '</div>' +
                        '<span class="annual-month">' + bulan[m] + '</span></div>';
            }
            document.getElementById('annualChart').innerHTML = html;
            var ts = res.data.type_summary || {};
            var keys = Object.keys(ts);
            document.getElementById('annualTypes').innerHTML = keys.length === 0 ? '<span class="text-slate-400">Belum ada transaksi.</span>' :
                keys.map(function(t) {
                    var info = ts[t];
                    var p = Number(info.poin) || 0;
                    return '<div class="flex justify-between items-center mb-1 pb-1 border-b border-slate-100"><span>' + tipeBadge(t) + ' <span class="text-slate-400 text-xs">' + info.count + 'x</span></span>' +
                           '<span class="font-bold num-tabular ' + (p >= 0 ? 'text-emerald-600' : 'text-red-500') + '">' + (p >= 0 ? '+' : '') + fmtNum(p) + '</span></div>';
                }).join('');
            document.getElementById('annualTableBody').innerHTML = (!res.data.history || !res.data.history.length)
                ? '<tr><td colspan="3" class="text-center py-8 text-slate-400">Belum ada transaksi tahun ini.</td></tr>'
                : (res.data.dibatasi
                    ? '<tr><td colspan="3" class="text-center py-2 text-xs text-amber-600">Menampilkan 200 dari ' + (res.data.total_mutasi || 0) + ' mutasi — ringkasan di atas dihitung dari seluruhnya.</td></tr>'
                    : '') + res.data.history.map(function(h) {
                    var p = parseInt(h.poin || '0', 10);
                    return '<tr><td class="text-slate-500 whitespace-nowrap">' + esc(fmtDate(h.created_at)) + '</td><td>' + tipeBadge(h.tipe) + '</td>' +
                           '<td class="text-right font-bold num-tabular ' + (p >= 0 ? 'text-emerald-600' : 'text-red-500') + '">' + (p >= 0 ? '+' : '') + fmtNum(p) + ' pts</td></tr>';
                }).join('');
        }, 'json').fail(function() { document.getElementById('annualTableBody').innerHTML = '<tr><td colspan="3" class="text-center text-red-500 py-4">Gagal memuat data</td></tr>'; });
    };

    window.seedPoinAllModal = function() {
        Swal.fire({
            title: 'Sinkronisasi Poin <?= $currentYear ?>?',
            text: 'Menghitung ulang mutasi presensi seluruh guru Jan–Des <?= $currentYear ?>. Idempotent (tidak duplikat).',
            icon: 'question', showCancelButton: true, confirmButtonColor: '#198754', confirmButtonText: 'Ya, Sinkronkan', cancelButtonText: 'Batal'
        }).then(function(r) {
            if (!r.isConfirmed) return;
            Swal.fire({ title: 'Memproses...', text: 'Menyinkronkan presensi ke poin...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
            $.post('<?= site_url('admin/reward/seedPoinAll') ?>', Object.assign({ tahun: <?= $currentYear ?> }, csrfData()), function(res) {
                if (res.status) Swal.fire({ icon: 'success', title: 'Selesai!', text: res.message, timer: 1600, showConfirmButton: false }).then(reloadKeepTab);
                else Swal.fire('Gagal', res.message, 'error');
            }, 'json').fail(function() { Swal.fire('Error', 'Terjadi kesalahan sistem', 'error'); });
        });
    };

    /* ---------- DataTables ---------- */
    function initTables() {
        if (!window.jQuery || !$.fn.DataTable) return;
        if ($('#teacherTable').length && !$.fn.DataTable.isDataTable('#teacherTable')) {
            $('#teacherTable').DataTable({ pageLength: 10, lengthMenu: [10, 25, 50], order: [[5, 'desc']], language: window.dt_lang_id || {}, columnDefs: [{ orderable: false, targets: 6 }] });
        }
        if ($('#pendingTable').length && !$.fn.DataTable.isDataTable('#pendingTable')) {
            $('#pendingTable').DataTable({ pageLength: 10, order: [[0, 'asc']], language: window.dt_lang_id || {}, columnDefs: [{ orderable: false, targets: 5 }] });
        }
        if ($('#tierTable').length && !$.fn.DataTable.isDataTable('#tierTable')) {
            $('#tierTable').DataTable({ paging: false, searching: false, info: false, order: [[2, 'asc']] });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        restoreTab();
        if (typeof filterCatalog === 'function') filterCatalog();
        initTables();
    });

    /* ---------- Delegated bindings (ganti onclick inline) ---------- */
    function parseJsonAttr(v, fallback) {
        try { return JSON.parse(v); } catch (e) { return fallback; }
    }
    document.getElementById('btnRewardRefresh').addEventListener('click', function() { location.reload(); });
    var btnGotoKlaim = document.getElementById('btnGotoKlaim');
    if (btnGotoKlaim) btnGotoKlaim.addEventListener('click', function() {
        if (window.AdminUI) AdminUI.tabs.activate(document.querySelector('a[href="#tab-redemptions"]'));
    });
    document.querySelectorAll('.btn-add-reward').forEach(function(b) {
        b.addEventListener('click', function() { openRewardModal(); });
    });
    document.querySelectorAll('.btn-add-tier').forEach(function(b) {
        b.addEventListener('click', function() { openTierModal(); });
    });
    ['rewardSearch', 'rewardFilterType', 'rewardFilterStatus', 'rewardSort'].forEach(function(id) {
        document.getElementById(id).addEventListener('input', filterCatalog);
        document.getElementById(id).addEventListener('change', filterCatalog);
    });
    document.getElementById('historyStatusFilter').addEventListener('change', filterHistoryTable);
    document.getElementById('historySearch').addEventListener('input', filterHistoryTable);
    document.getElementById('btnSeedPoin').addEventListener('click', seedPoinAllModal);
    $(document).on('click', '.btn-edit-reward', function() { openRewardModal(parseJsonAttr($(this).attr('data-reward'), null)); });
    $(document).on('click', '.btn-del-reward', function() { deleteReward($(this).data('id'), $(this).data('nama')); });
    $(document).on('click', '.btn-approve-redemption', function() { approveRedemption($(this).data('id')); });
    $(document).on('click', '.btn-reject-redemption', function() { rejectRedemptionModal($(this).data('id')); });
    $(document).on('click', '.btn-edit-tier', function() { openTierModal(parseJsonAttr($(this).attr('data-tier'), null)); });
    $(document).on('click', '.btn-del-tier', function() { deleteTier($(this).data('id'), $(this).data('nama')); });
    $(document).on('click', '.dd-adjust', function(e) { e.preventDefault(); openAdjustModal($(this).data('id'), $(this).data('nama'), $(this).data('saldo')); });
    $(document).on('click', '.dd-history', function(e) { e.preventDefault(); openHistoryModal($(this).data('id'), $(this).data('nama')); });
    $(document).on('click', '.dd-annual', function(e) { e.preventDefault(); openAnnualModal($(this).data('id'), $(this).data('nama')); });
    $('#rewardForm').on('submit', saveReward);
    $('#tierForm').on('submit', saveTier);
    $('#adjustPoinForm').on('submit', submitAdjustPoin);
    $('#rejectForm').on('submit', submitReject);
    $('#reward_poin, #reward_stok, #reward_nominal').on('input', previewReward);
    $('#tier_nama, #tier_poin_min, #tier_poin_max, #tier_badge, #tier_warna').on('input', previewTier);
    $('#tier_warna_hex').on('input', function() {
        if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('tier_warna').value = this.value;
        previewTier();
    });
    $('#adj_tipe').on('change', previewAdjust);
    $('#adj_poin').on('input', previewAdjust);

    /* Kompatibilitas: pencarian lama */
    window.filterTeacherTable = function() {
        var kw = (document.getElementById('searchTeacherInput')?.value || '').toLowerCase().trim();
        if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#teacherTable')) {
            $('#teacherTable').DataTable().search(kw).draw();
            return;
        }
        document.querySelectorAll('#teacherTable tbody tr').forEach(function(r) {
            r.style.display = (!kw || (r.getAttribute('data-search') || '').indexOf(kw) >= 0) ? '' : 'none';
        });
    };
})();
</script>
<?= $this->endSection() ?>
