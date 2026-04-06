<?php $public_link = base_url('lucky-spin/' . $campaign->token); ?>

<div class="page-header">
    <h1><i class='bx bxs-dice-5'></i> Detail Lucky Spin</h1>
    <a href="<?= base_url('admin/lucky_spin'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<p><?= html_escape($campaign->name); ?></p>

<?php if ($this->session->flashdata('success')): ?>
    <div class="flash-message success"><?= html_escape($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="stat-cards-container">
    <div class="stat-card blue">
        <div class="card-icon"><i class='bx bxs-group'></i></div>
        <div class="card-content">
            <span class="card-title">Peserta</span>
            <span class="card-value"><?= number_format((int) $campaign->participants_count, 0, ',', '.'); ?>/<?= number_format((int) $campaign->max_players, 0, ',', '.'); ?></span>
        </div>
    </div>

    <div class="stat-card green">
        <div class="card-icon"><i class='bx bx-rotate-right'></i></div>
        <div class="card-content">
            <span class="card-title">Spin Terpakai</span>
            <span class="card-value"><?= number_format((int) $campaign->total_spins_used, 0, ',', '.'); ?></span>
        </div>
    </div>

    <div class="stat-card yellow">
        <div class="card-icon"><i class='bx bxs-gift'></i></div>
        <div class="card-content">
            <span class="card-title">Hadiah Aktif</span>
            <span class="card-value"><?= number_format((int) $campaign->available_rewards, 0, ',', '.'); ?></span>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="bucks-kaget-detail-header">
        <div>
            <h3 style="margin-bottom: 8px;">Link Spin</h3>
            <div class="copy-link-group copy-link-group-lg">
                <input type="text" class="copy-link-input" value="<?= html_escape($public_link); ?>" readonly>
                <button type="button" class="btn btn-primary copy-link-btn" data-copy-text="<?= html_escape($public_link); ?>"><i class='bx bx-copy'></i> Copy Link</button>
            </div>
        </div>
        <div class="bucks-kaget-meta">
            <span class="status-badge <?= $campaign->status === 'active' ? 'status-active' : ($campaign->status === 'finished' ? 'status-finished' : ($campaign->status === 'expired' ? 'status-expired' : 'status-inactive')); ?>"><?= html_escape($campaign->status_label); ?></span>
            <small>Maks peserta: <?= number_format((int) $campaign->max_players, 0, ',', '.'); ?> orang</small>
            <small>Spin per player: <?= number_format((int) $campaign->max_spins_per_player, 0, ',', '.'); ?>x</small>
            <small>Expired: <?= !empty($campaign->expires_at) ? date('d M Y H:i', strtotime($campaign->expires_at)) : 'Tidak ada'; ?></small>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?= base_url('admin/lucky_spin/toggle/' . $campaign->id); ?>" class="btn btn-secondary">
            <i class='bx <?= $campaign->is_active ? 'bx-pause' : 'bx-play'; ?>'></i>
            <?= $campaign->is_active ? 'Tutup Campaign' : 'Aktifkan Lagi'; ?>
        </a>
        <a href="<?= $public_link; ?>" target="_blank" class="btn btn-primary"><i class='bx bx-link-external'></i> Buka Halaman Spin</a>
    </div>
</div>

<div class="content-card" style="margin-top: 20px;">
    <h3>Pool Hadiah</h3>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hadiah</th>
                    <th>Tipe</th>
                    <th>Detail</th>
                    <th>Weight</th>
                    <th>Stock</th>
                    <th>Terpakai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rewards)): ?>
                    <?php foreach ($rewards as $reward): ?>
                        <tr>
                            <td><strong><?= html_escape($reward->label); ?></strong></td>
                            <td><?= html_escape(ucfirst($reward->reward_type)); ?></td>
                            <td>
                                <?php if ($reward->reward_type === 'bucks'): ?>
                                    <?= number_format((int) $reward->bucks_amount, 0, ',', '.'); ?> Bucks
                                <?php elseif ($reward->reward_type === 'product'): ?>
                                    <?= html_escape($reward->product_name ?: 'Produk tidak ditemukan'); ?>
                                <?php else: ?>
                                    Zonk / hadiah kosong
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((int) $reward->weight, 0, ',', '.'); ?></td>
                            <td><?= $reward->stock !== null ? number_format((int) $reward->stock, 0, ',', '.') : 'Unlimited'; ?></td>
                            <td><?= number_format((int) $reward->won_count, 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Belum ada hadiah.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-card" style="margin-top: 20px;">
    <h3>Riwayat Spin</h3>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pemain</th>
                    <th>Platform</th>
                    <th>Hadiah</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($entries)): ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= date('d M Y H:i', strtotime($entry->spun_at)); ?></td>
                            <td><strong><?= html_escape($entry->claimed_by_username); ?></strong></td>
                            <td><?= html_escape(ucfirst((string) $entry->claimed_platform)); ?></td>
                            <td><?= html_escape($entry->reward_label); ?></td>
                            <td>
                                <?php if ($entry->reward_type === 'bucks'): ?>
                                    <?= number_format((int) $entry->bucks_amount, 0, ',', '.'); ?> Bucks
                                <?php elseif ($entry->reward_type === 'product'): ?>
                                    <?= html_escape($entry->product_name ?: 'Produk Reward'); ?>
                                <?php else: ?>
                                    Zonk
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Belum ada pemain yang spin.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.querySelector('.copy-link-btn');
    if (!copyButton) return;

    copyButton.addEventListener('click', async function() {
        try {
            await navigator.clipboard.writeText(this.dataset.copyText || '');
            this.innerHTML = "<i class='bx bx-check'></i> Copied";
            setTimeout(() => {
                this.innerHTML = "<i class='bx bx-copy'></i> Copy Link";
            }, 1800);
        } catch (error) {
            alert('Gagal copy link. Silakan copy manual.');
        }
    });
});
</script>
