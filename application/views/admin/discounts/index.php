<h1>Manajemen Diskon Terjadwal</h1>
<p>Buat dan kelola diskon berdasarkan waktu untuk produk atau kategori tertentu di toko Anda.</p>

<a href="<?= base_url('admin/discounts/add'); ?>" class="btn btn-primary" style="margin-bottom: 20px; display: inline-block;">
    <i class="fas fa-plus"></i> Buat Diskon Baru
</a>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nama Diskon</th>
            <th>Persentase</th>
            <th>Target</th>
            <th>Waktu Mulai</th>
            <th>Waktu Berakhir</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($discounts)): ?>
            <?php foreach ($discounts as $discount): ?>
            <tr>
                <td>
                    <strong><?= html_escape($discount->name); ?></strong>
                </td>
                <td>
                    <?= $discount->discount_percentage; ?>%
                </td>
                <td>
                    <?php if (!empty($discount->scopes)): ?>
                        <?php 
                            $scopes = explode(';', $discount->scopes);
                            foreach($scopes as $scope): 
                                // Memastikan setiap scope punya format yang benar sebelum dipecah
                                if (strpos($scope, ':') !== false):
                                    list($type, $value) = explode(':', $scope, 2);
                        ?>
                                    <span class="badge"><?= ucfirst(html_escape($type)) . ($value ? ': ' . html_escape($value) : ''); ?></span>
                        <?php 
                                endif;
                            endforeach; 
                        ?>
                    <?php else: ?>
                        <span class="badge" style="background-color: #555;">Belum ada target</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= date('d M Y, H:i', strtotime($discount->starts_at)); ?>
                </td>
                <td>
                    <?= date('d M Y, H:i', strtotime($discount->ends_at)); ?>
                </td>
                <td>
                    <?= $discount->is_active ? '<span style="color:var(--accent-green);">Aktif</span>' : '<span style="color:var(--text-secondary);">Non-Aktif</span>'; ?>
                </td>
                <td>
                    <!-- PERUBAHAN DI SINI: Link # diganti -->
                    <a href="<?= base_url('admin/discounts/edit/' . $discount->id); ?>" class="btn btn-secondary">Edit</a> 
                    <a href="<?= base_url('admin/discounts/delete/' . $discount->id); ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus diskon ini? Tindakan ini tidak bisa dibatalkan.');">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">Belum ada diskon yang dibuat. Silakan buat diskon baru.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
    .admin-table .badge {
        background-color: #4A5063;
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 0.8em;
        font-weight: 500;
        margin: 2px;
        display: inline-block;
        color: var(--text-primary);
    }
</style>