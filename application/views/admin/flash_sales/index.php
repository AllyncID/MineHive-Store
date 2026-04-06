<div class="page-header">
    <h1>Manajemen Flash Sale</h1>
    <a href="<?= base_url('admin/flash_sales/add'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Buat Flash Sale Baru</a>
</div>

<div class="content-card">
    <p>Atur penawaran produk dengan waktu dan stok terbatas untuk menciptakan urgensi pembelian.</p>
    <table class="data-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Diskon</th>
                <th>Waktu Berlangsung</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($flash_sales)): ?>
                <?php foreach ($flash_sales as $fs): ?>
                    <tr>
                        <td><?= html_escape($fs->product_name); ?></td>
                        <td><?= $fs->discount_percentage; ?>%</td>
                        <td>
                            <div>Mulai: <?= date('d M Y, H:i', strtotime($fs->start_date)); ?></div>
                            <div>Selesai: <?= date('d M Y, H:i', strtotime($fs->end_date)); ?></div>
                        </td>
                        <td>
                            <?= $fs->stock_sold; ?> / <?= $fs->stock_limit; ?>
                            <?php $stock_percentage = ($fs->stock_limit > 0) ? ($fs->stock_sold / $fs->stock_limit) * 100 : 0; ?>
                            <div class="stock-bar-sm">
                                <div class="stock-progress-sm" style="width: <?= $stock_percentage; ?>%;"></div>
                            </div>
                        </td>
                        <td>
                            <?php if ($fs->is_active): ?>
                                <span class="status-badge status-active">Aktif</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Non-Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-cell">
                            <a href="<?= base_url('admin/flash_sales/edit/' . $fs->id); ?>" class="btn btn-sm btn-warning" title="Edit"><i class='bx bxs-edit'></i></a>
                            <a href="<?= base_url('admin/flash_sales/delete/' . $fs->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus flash sale ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Belum ada flash sale yang dibuat.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>