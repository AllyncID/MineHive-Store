<div class="page-header">
    <h1><i class='bx bxs-dice-5'></i> Lucky Spin</h1>
    <a href="<?= base_url('admin/lucky_spin/add'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Buat Campaign</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="flash-message success"><?= html_escape($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<?php if ($this->session->flashdata('error')): ?>
    <div class="flash-message"><?= html_escape($this->session->flashdata('error')); ?></div>
<?php endif; ?>

<div class="content-card">
    <p>Buat link Lucky Spin yang bisa dipakai pemain untuk spin hadiah random seperti Bucks, produk store, atau zonk. Jumlah peserta dan batas spin per pemain bisa diatur per campaign.</p>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Link Spin</th>
                    <th>Limit</th>
                    <th>Reward Pool</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($campaigns)): ?>
                    <?php foreach ($campaigns as $campaign): ?>
                        <?php
                            $status_class = 'status-active';
                            if ($campaign->status === 'finished') {
                                $status_class = 'status-finished';
                            } elseif ($campaign->status === 'expired') {
                                $status_class = 'status-expired';
                            } elseif ($campaign->status === 'inactive') {
                                $status_class = 'status-inactive';
                            }
                            $public_link = base_url('lucky-spin/' . $campaign->token);
                        ?>
                        <tr>
                            <td>
                                <strong><?= html_escape($campaign->name); ?></strong><br>
                                <small>Dibuat <?= date('d M Y H:i', strtotime($campaign->created_at)); ?></small>
                            </td>
                            <td>
                                <div class="copy-link-group">
                                    <input type="text" class="copy-link-input" value="<?= html_escape($public_link); ?>" readonly>
                                    <button type="button" class="btn btn-sm btn-primary copy-link-btn" data-copy-text="<?= html_escape($public_link); ?>">
                                        <i class='bx bx-copy'></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <strong><?= number_format((int) $campaign->participants_count, 0, ',', '.'); ?> / <?= number_format((int) $campaign->max_players, 0, ',', '.'); ?> orang</strong><br>
                                <small><?= number_format((int) $campaign->max_spins_per_player, 0, ',', '.'); ?> spin per player</small>
                            </td>
                            <td>
                                <strong><?= number_format((int) $campaign->available_rewards, 0, ',', '.'); ?> hadiah aktif</strong><br>
                                <small><?= number_format((int) $campaign->total_spins_used, 0, ',', '.'); ?> spin sudah dipakai</small>
                            </td>
                            <td><span class="status-badge <?= $status_class; ?>"><?= html_escape($campaign->status_label); ?></span></td>
                            <td class="action-cell">
                                <a href="<?= base_url('admin/lucky_spin/view/' . $campaign->id); ?>" class="btn btn-sm btn-warning" title="Detail"><i class='bx bx-show'></i></a>
                                <a href="<?= base_url('admin/lucky_spin/toggle/' . $campaign->id); ?>" class="btn btn-sm btn-secondary" title="<?= $campaign->is_active ? 'Tutup' : 'Aktifkan'; ?>"><i class='bx <?= $campaign->is_active ? 'bx-pause' : 'bx-play'; ?>'></i></a>
                                <a href="<?= base_url('admin/lucky_spin/delete/' . $campaign->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin mau hapus campaign Lucky Spin ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Belum ada campaign Lucky Spin.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-link-btn').forEach(function(button) {
        button.addEventListener('click', async function() {
            const text = this.dataset.copyText || '';
            if (!text) return;

            try {
                await navigator.clipboard.writeText(text);
                this.innerHTML = "<i class='bx bx-check'></i>";
                setTimeout(() => {
                    this.innerHTML = "<i class='bx bx-copy'></i>";
                }, 1500);
            } catch (error) {
                alert('Gagal copy link. Silakan copy manual.');
            }
        });
    });
});
</script>
