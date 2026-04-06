<div class="page-header">
    <h1>Manajemen Bonus Top Up</h1>
    <a href="<?= base_url('admin/bonus/add'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Tambah Tier Baru</a>
</div>

<div class="content-card">
    <p>Atur hadiah yang akan didapat pemain ketika mereka mencapai total belanja tertentu dalam satu transaksi.</p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Total Belanja Minimum</th>
                <th>Total Belanja Maksimum</th>
                <th>Deskripsi Hadiah</th>
                <th>Command</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tiers)): ?>
                <?php foreach ($tiers as $tier): ?>
                <tr>
                    <td>Rp <?= number_format($tier->min_amount, 0, ',', '.'); ?></td>
                    <td><?= $tier->max_amount ? 'Rp ' . number_format($tier->max_amount, 0, ',', '.') : 'ke atas'; ?></td>
                    <td><?= html_escape($tier->reward_description); ?></td>
                    <td><code class="code-snippet"><?= html_escape($tier->reward_command); ?></code></td>
                    <td><?= $tier->is_active ? '<span class="status-badge status-active">Aktif</span>' : '<span class="status-badge status-inactive">Non-Aktif</span>'; ?></td>
                    <td class="action-cell">
                        <a href="<?= base_url('admin/bonus/edit/' . $tier->id); ?>" class="btn btn-sm btn-warning" title="Edit"><i class='bx bxs-edit'></i></a>
                        <a href="<?= base_url('admin/bonus/delete/' . $tier->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus tier ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Belum ada tingkatan bonus yang dibuat.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>