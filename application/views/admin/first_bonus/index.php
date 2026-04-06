<div class="page-header">
    <h1>Manajemen Bonus Pembelian Pertama</h1>
    <a href="<?= base_url('admin/first_bonus/add'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Tambah Command Bonus</a>
</div>

<div class="content-card">
    <p>Atur hadiah (command) yang akan diterima pemain HANYA pada pembelian pertama mereka. Jika pemain sudah pernah transaksi, bonus ini tidak akan berlaku.</p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Deskripsi (untuk Admin)</th>
                <th>Command Hadiah</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bonuses)): ?>
                <?php foreach ($bonuses as $bonus): ?>
                <tr>
                    <td><?= $bonus->id; ?></td>
                    <td><?= html_escape($bonus->description); ?></td>
                    <td><code class="code-snippet"><?= html_escape($bonus->reward_command); ?></code></td>
                    <td><?= $bonus->is_active ? '<span class="status-badge status-active">Aktif</span>' : '<span class="status-badge status-inactive">Non-Aktif</span>'; ?></td>
                    <td class="action-cell">
                        <a href="<?= base_url('admin/first_bonus/edit/' . $bonus->id); ?>" class="btn btn-sm btn-warning" title="Edit"><i class='bx bxs-edit'></i></a>
                        <a href="<?= base_url('admin/first_bonus/delete/' . $bonus->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus command ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Belum ada command bonus yang diatur.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
