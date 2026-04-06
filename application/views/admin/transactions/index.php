<h1> Riwayat Transaksi <span class="filter-title">(<?= $filter_title ?>)</span></h1>
<p>Lihat semua catatan pembelian yang telah dilakukan di toko Anda.</p>

<div class="dashboard-filters" style="margin-bottom: 25px;">
    <a href="<?= base_url('admin/transactions?range=today'); ?>" class="filter-btn <?= ($current_range == 'today') ? 'active' : '' ?>">Hari Ini</a>
    <a href="<?= base_url('admin/transactions?range=7days'); ?>" class="filter-btn <?= ($current_range == '7days') ? 'active' : '' ?>">7 Hari Terakhir</a>
    <a href="<?= base_url('admin/transactions?range=30days'); ?>" class="filter-btn <?= ($current_range == '30days') ? 'active' : '' ?>">30 Hari Terakhir</a>
    <a href="<?= base_url('admin/transactions?range=all'); ?>" class="filter-btn <?= ($current_range == 'all') ? 'active' : '' ?>">Semua Waktu</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Produk Dibeli</th> <th>Total Bayar</th>
            <th>Promo</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($transactions)): ?>
            <?php foreach ($transactions as $trx): ?>
            <tr>
                <td><?= $trx->id; ?></td>
                <td><strong><?= html_escape($trx->player_username); ?></strong></td>
                <td><?= html_escape($trx->purchased_items) ? html_escape($trx->purchased_items) : 'N/A'; ?></td> <td>Rp <?= number_format($trx->grand_total, 0, ',', '.'); ?></td>
                <td><?= $trx->promo_code_used ? html_escape($trx->promo_code_used) : '-'; ?></td>
                <td><?= date('d M Y, H:i', strtotime($trx->created_at)); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center;">Belum ada data transaksi.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>