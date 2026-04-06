<h1>Manajemen Afiliasi</h1>
<p>Kelola data helper atau staf yang menjadi bagian dari program afiliasi.</p>
<a href="<?= base_url('admin/affiliates/add'); ?>" class="btn btn-primary" style="margin-bottom: 20px;"><i class="fas fa-user-plus"></i> Tambah Afiliasi Baru</a>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username Minecraft</th>
            <th>Badge</th>
            <th>Total Sales</th>
            <th>Total Transaksi</th> <!-- KOLOM BARU -->
            <th>Saldo Komisi</th>
            <th>Status</th>
            <th>Aksi</th> </tr>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($affiliates as $affiliate): ?>
        <tr>
            <td><?= $affiliate->id; ?></td>
            <td><img src="https://minotar.net/avatar/<?= html_escape($affiliate->minecraft_username); ?>/24" style="margin-right:10px; border-radius:4px;"> <strong><?= html_escape($affiliate->minecraft_username); ?></strong></td>
            <td>
                <!-- KOLOM BADGE -->
                <span class="aff-badge badge-<?= html_escape($affiliate->badge); ?>">
                    <?= html_escape(ucfirst($affiliate->badge)); ?>
                </span>
            </td>
            <td>
                <!-- KOLOM TOTAL SALES -->
                Rp <?= number_format($affiliate->total_sales, 0, ',', '.'); ?>
            </td>
            <td>
                <!-- KOLOM TOTAL TRANSAKSI BARU -->
                <?= number_format($affiliate->total_transactions, 0, ',', '.'); ?>
            </td>
            <td>Rp <?= number_format($affiliate->wallet_balance, 2, ',', '.'); ?></td>
            <td><?= $affiliate->is_active ? 'Aktif' : 'Non-Aktif'; ?></td>
            <td>
                <a href="<?= base_url('admin/affiliates/delete/' . $affiliate->id); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus afiliasi ini? Semua kode referral miliknya juga akan terhapus.');">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

