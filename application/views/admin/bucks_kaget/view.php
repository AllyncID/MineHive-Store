<?php $public_link = base_url($campaign->token); ?>

<div class="page-header">
    <h1><i class='bx bxs-gift'></i> Detail Bucks Kaget</h1>
    <a href="<?= base_url('admin/bucks_kaget'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<p><?= html_escape($campaign->name); ?></p>

<div class="stat-cards-container">
    <div class="stat-card blue">
        <div class="card-icon"><i class='bx bxs-wallet'></i></div>
        <div class="card-content">
            <span class="card-title">Total Bucks</span>
            <span class="card-value"><?= number_format((int) $campaign->total_allocated_bucks, 0, ',', '.'); ?></span>
        </div>
    </div>

    <div class="stat-card green">
        <div class="card-icon"><i class='bx bxs-user-check'></i></div>
        <div class="card-content">
            <span class="card-title">Sudah Claim</span>
            <span class="card-value"><?= number_format((int) $campaign->claimed_slots, 0, ',', '.'); ?>/<?= number_format((int) $campaign->total_slots, 0, ',', '.'); ?></span>
        </div>
    </div>

    <div class="stat-card yellow">
        <div class="card-icon"><i class='bx bxs-hourglass'></i></div>
        <div class="card-content">
            <span class="card-title">Sisa Bucks</span>
            <span class="card-value"><?= number_format((int) $campaign->remaining_bucks, 0, ',', '.'); ?></span>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="bucks-kaget-detail-header">
        <div>
            <h3 style="margin-bottom: 8px;">Link Random</h3>
            <div class="copy-link-group copy-link-group-lg">
                <input type="text" class="copy-link-input" value="<?= html_escape($public_link); ?>" readonly>
                <button type="button" class="btn btn-primary copy-link-btn" data-copy-text="<?= html_escape($public_link); ?>"><i class='bx bx-copy'></i> Copy Link</button>
            </div>
        </div>
        <div class="bucks-kaget-meta">
            <span class="status-badge <?= $campaign->status === 'active' ? 'status-active' : ($campaign->status === 'finished' ? 'status-finished' : ($campaign->status === 'expired' ? 'status-expired' : 'status-inactive')); ?>"><?= html_escape($campaign->status_label); ?></span>
            <small>Expired: <?= !empty($campaign->expires_at) ? date('d M Y H:i', strtotime($campaign->expires_at)) : 'Tidak ada'; ?></small>
            <small>Dibuat: <?= date('d M Y H:i', strtotime($campaign->created_at)); ?></small>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?= base_url('admin/bucks_kaget/toggle/' . $campaign->id); ?>" class="btn btn-secondary">
            <i class='bx <?= $campaign->is_active ? 'bx-pause' : 'bx-play'; ?>'></i>
            <?= $campaign->is_active ? 'Tutup Campaign' : 'Aktifkan Lagi'; ?>
        </a>
        <a href="<?= base_url($campaign->token); ?>" target="_blank" class="btn btn-primary"><i class='bx bx-link-external'></i> Buka Halaman Claim</a>
    </div>
</div>

<div class="content-card" style="margin-top: 20px;">
    <h3>Rincian Pembagian Random</h3>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Pemain</th>
                    <th>Platform</th>
                    <th>Waktu Claim</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($claims)): ?>
                    <?php foreach ($claims as $index => $claim): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td><strong><?= number_format((int) $claim->amount, 0, ',', '.'); ?> Bucks</strong></td>
                            <td>
                                <?= $claim->claimed_at ? '<span class="status-badge status-active">Sudah Claim</span>' : '<span class="status-badge status-inactive">Belum Claim</span>'; ?>
                            </td>
                            <td><?= $claim->claimed_by_username ? html_escape($claim->claimed_by_username) : '-'; ?></td>
                            <td><?= $claim->claimed_platform ? html_escape(ucfirst($claim->claimed_platform)) : '-'; ?></td>
                            <td><?= $claim->claimed_at ? date('d M Y H:i', strtotime($claim->claimed_at)) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Belum ada data pembagian.</td>
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
