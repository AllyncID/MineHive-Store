<div class="page-header">
    <h1><i class='bx bxs-gift'></i> Bucks Kaget</h1>
    <a href="<?= base_url('admin/bucks_kaget/add'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Buat Campaign</a>
</div>

<div class="content-card">
    <p>Buat link random `bucks kaget`, bagi total bucks secara acak ke sejumlah penerima, lalu copy link-nya untuk dibagikan ke Discord atau platform lain.</p>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Link Random</th>
                    <th>Total</th>
                    <th>Claimed</th>
                    <th>Sisa</th>
                    <th>Expired</th>
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
                            $public_link = base_url($campaign->token);
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
                                <strong><?= number_format((int) $campaign->total_allocated_bucks, 0, ',', '.'); ?> Bucks</strong><br>
                                <small><?= number_format((int) $campaign->total_slots, 0, ',', '.'); ?> orang</small>
                            </td>
                            <td>
                                <strong><?= number_format((int) $campaign->claimed_bucks, 0, ',', '.'); ?> Bucks</strong><br>
                                <small><?= number_format((int) $campaign->claimed_slots, 0, ',', '.'); ?> / <?= number_format((int) $campaign->total_slots, 0, ',', '.'); ?> orang</small>
                            </td>
                            <td>
                                <strong><?= number_format((int) $campaign->remaining_bucks, 0, ',', '.'); ?> Bucks</strong><br>
                                <small><?= number_format((int) $campaign->remaining_slots, 0, ',', '.'); ?> slot</small>
                            </td>
                            <td><?= !empty($campaign->expires_at) ? date('d M Y H:i', strtotime($campaign->expires_at)) : 'Tidak ada'; ?></td>
                            <td><span class="status-badge <?= $status_class; ?>"><?= html_escape($campaign->status_label); ?></span></td>
                            <td class="action-cell">
                                <a href="<?= base_url('admin/bucks_kaget/view/' . $campaign->id); ?>" class="btn btn-sm btn-warning" title="Detail"><i class='bx bx-show'></i></a>
                                <a href="<?= base_url('admin/bucks_kaget/toggle/' . $campaign->id); ?>" class="btn btn-sm btn-secondary" title="<?= $campaign->is_active ? 'Tutup' : 'Aktifkan'; ?>"><i class='bx <?= $campaign->is_active ? 'bx-pause' : 'bx-play'; ?>'></i></a>
                                <a href="<?= base_url('admin/bucks_kaget/delete/' . $campaign->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin mau hapus campaign Bucks Kaget ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Belum ada campaign Bucks Kaget.</td>
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
