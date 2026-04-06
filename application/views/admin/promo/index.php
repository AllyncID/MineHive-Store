<h1>Manage Promo Codes</h1>
<p>Buat dan kelola kode promo untuk memberikan diskon kepada pemain.</p>

<a href="<?= base_url('admin/promo/add'); ?>" class="btn btn-primary" style="margin-bottom: 20px; display: inline-block;">
    <i class="fas fa-plus"></i> Tambah Kode Promo Baru
</a>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Kode</th>
            <th>Tipe</th>
            <th>Nilai</th>
            <th>Jumlah Pemakaian</th>
            <th>Bisa Stack?</th> <!-- [PERUBAHAN] Kolom baru -->
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($promos)): ?>
            <?php foreach ($promos as $promo): ?>
            <tr>
                <td><?= $promo->id; ?></td>
                <td><strong><?= html_escape($promo->code); ?></strong></td>
                <td><?= ucfirst(html_escape($promo->type)); ?></td>
                <td>
                    <?php 
                        if ($promo->type == 'percentage') {
                            echo ($promo->value * 100) . '%';
                        } else {
                            echo 'Rp ' . number_format($promo->value, 0, ',', '.');
                        }
                    ?>
                </td>
                <td><?= $promo->usage_count; ?></td>
                <td><!-- [PERUBAHAN] Menampilkan status stacking -->
                    <?= isset($promo->allow_stacking) && $promo->allow_stacking ? 'Ya' : 'Tidak'; ?>
                </td>
                <td>
                    <a href="<?= base_url('admin/promo/edit/' . $promo->id); ?>" class="btn btn-secondary">Edit</a>
                    <a href="<?= base_url('admin/promo/delete/' . $promo->id); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus kode promo ini?');">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">Belum ada kode promo yang dibuat.</td> <!-- [PERUBAHAN] colspan jadi 7 -->
            </tr>
        <?php endif; ?>
    </tbody>
</table>
