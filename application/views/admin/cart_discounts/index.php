<div class="page-header">
    <h1>Diskon Berdasarkan Total Keranjang</h1>
    <div>
        <!-- Tombol Enable/Disable -->
        <a href="<?= base_url('admin/cart_discounts/toggle_status'); ?>"
           class="btn <?= $is_enabled ? 'btn-danger' : 'btn-success'; ?>" style="margin-right: 10px;">
            <i class='bx <?= $is_enabled ? 'bx-toggle-right' : 'bx-toggle-left'; ?>'></i>
            <?= $is_enabled ? 'Nonaktifkan Fitur' : 'Aktifkan Fitur'; ?>
        </a>
        <!-- Tombol Tambah Tier -->
        <a href="<?= base_url('admin/cart_discounts/add'); ?>" class="btn btn-primary">
            <i class='bx bx-plus'></i> Tambah Tingkatan Baru
        </a>
    </div>
</div>

<div class="content-card">
    <p>Atur diskon otomatis yang akan didapatkan pembeli berdasarkan total nilai keranjang belanja mereka. Sistem akan otomatis menerapkan diskon tertinggi yang memenuhi syarat.</p>
    <p><strong>Status Fitur Saat Ini:</strong>
        <span class="status-badge <?= $is_enabled ? 'status-active' : 'status-inactive'; ?>">
            <?= $is_enabled ? 'Aktif' : 'Non-Aktif'; ?>
        </span>
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Total Keranjang Minimum</th>
                <th>Persentase Diskon</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tiers)): ?>
                <?php foreach ($tiers as $tier): ?>
                <tr>
                    <td>Rp <?= number_format($tier->min_amount, 0, ',', '.'); ?></td>
                    <td><?= $tier->discount_percentage; ?>%</td>
                    <td class="action-cell">
                        <a href="<?= base_url('admin/cart_discounts/edit/' . $tier->id); ?>" class="btn btn-sm btn-warning" title="Edit"><i class='bx bxs-edit'></i></a>
                        <a href="<?= base_url('admin/cart_discounts/delete/' . $tier->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus tingkatan diskon ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center;">Belum ada tingkatan diskon yang dibuat.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Style tambahan untuk tombol success (jika belum ada di admin.css) -->
<style>
.btn-success { background-color: #22C55E; color: white; }
.btn-success:hover { background-color: #16A34A; }
</style>
