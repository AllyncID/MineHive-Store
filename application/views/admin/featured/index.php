<div class="page-header">
    <h1><i class='bx bxs-star'></i> Featured Products (Hot Deals)</h1>
    <?php if ($total_items < 12): ?> <!-- Limit diubah jadi 12 -->
        <a href="<?= base_url('admin/featured/add'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Tambah Produk</a>
    <?php else: ?>
        <button class="btn btn-secondary" disabled>Slot Penuh (Max 12)</button>
    <?php endif; ?>
</div>

<div class="content-card">
    <p>Produk yang ditampilkan di halaman utama (di bawah Flash Sale). Maksimal 12 produk. Tanpa batas waktu.</p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Urutan</th>
                <th>Produk</th>
                <th>Realm</th>
                <th>Harga Asli</th>
                <th>Diskon</th>
                <th>Harga Akhir</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($featured)): ?>
                <?php foreach ($featured as $item): ?>
                    <?php 
                        $final_price = $item->price - ($item->price * $item->discount_percentage / 100);
                    ?>
                    <tr>
                        <td><?= $item->sort_order; ?></td>
                        <td><strong><?= html_escape($item->product_name); ?></strong></td>
                        <td><?= ucfirst($item->realm); ?></td>
                        <td>Rp <?= number_format($item->price, 0, ',', '.'); ?></td>
                        <td><span class="badge" style="background-color: #DB4845;"><?= $item->discount_percentage; ?>%</span></td>
                        <td style="color: #ECCA01; font-weight: bold;">Rp <?= number_format($final_price, 0, ',', '.'); ?></td>
                        <td class="action-cell">
                            <a href="<?= base_url('admin/featured/edit/' . $item->id); ?>" class="btn btn-sm btn-warning"><i class='bx bxs-edit'></i></a>
                            <a href="<?= base_url('admin/featured/delete/' . $item->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus dari featured?');"><i class='bx bxs-trash'></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Belum ada Featured Products.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>