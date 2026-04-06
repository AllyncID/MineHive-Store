<div class="page-header">
    <h1><?= isset($flash_sale) ? 'Edit' : 'Tambah'; ?> Flash Sale</h1>
    <a href="<?= base_url('admin/flash_sales'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<div class="content-card">
    <form action="" method="POST">
        <div class="form-group">
            <label for="product_id">Pilih Produk</label>
            <select name="product_id" id="product_id" class="form-control" required>
                <option value="">-- Pilih Produk untuk Flash Sale --</option>
                <?php foreach($products as $product): ?>
                    <option value="<?= $product['id']; ?>" <?= (isset($flash_sale) && $flash_sale->product_id == $product['id']) ? 'selected' : ''; ?>>
                        <?= html_escape($product['name']); ?> (<?= html_escape(ucfirst($product['realm'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="discount_percentage">Persentase Diskon (%)</label>
                <input type="number" name="discount_percentage" id="discount_percentage" class="form-control" value="<?= isset($flash_sale) ? $flash_sale->discount_percentage : ''; ?>" min="1" max="100" required>
            </div>
            <div class="form-group">
                <label for="stock_limit">Batas Stok</label>
                <input type="number" name="stock_limit" id="stock_limit" class="form-control" value="<?= isset($flash_sale) ? $flash_sale->stock_limit : ''; ?>" min="1" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Waktu Mulai</label>
                <input type="text" name="start_date" class="form-control flatpickr-datetime" value="<?= isset($flash_sale) ? $flash_sale->start_date : ''; ?>" placeholder="Pilih waktu mulai" required>
            </div>
            <div class="form-group">
                <label for="end_date">Waktu Berakhir</label>
                <input type="text" name="end_date" class="form-control flatpickr-datetime" value="<?= isset($flash_sale) ? $flash_sale->end_date : ''; ?>" placeholder="Pilih waktu berakhir" required>
            </div>
        </div>
        
        <div class="form-group">
            <label class="toggle-switch-label" for="is_active_checkbox">Aktifkan Flash Sale?</label>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active_checkbox" name="is_active" value="1" <?= (isset($flash_sale) && $flash_sale->is_active) ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan</button>
        </div>
    </form>
</div>