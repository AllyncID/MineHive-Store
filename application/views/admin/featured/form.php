<div class="page-header">
    <h1><?= isset($item) ? 'Edit' : 'Tambah'; ?> Featured Product</h1>
    <a href="<?= base_url('admin/featured'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<div class="content-card">
    <form action="" method="POST">
        
        <div class="form-group">
            <label for="product_id">Pilih Produk</label>
            <!-- Jika edit, disable select produk agar tidak error duplikat unique id -->
            <select name="product_id" id="product_id" class="form-control" <?= isset($item) ? 'disabled' : 'required'; ?>>
                <option value="">-- Cari Produk --</option>
                <?php foreach($products as $p): ?>
                    <option value="<?= $p->id; ?>" <?= (isset($item) && $item->product_id == $p->id) ? 'selected' : ''; ?>>
                        [<?= ucfirst($p->realm); ?>] <?= $p->name; ?> (Rp <?= number_format($p->price,0,',','.'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if(isset($item)): ?>
                <input type="hidden" name="product_id" value="<?= $item->product_id; ?>">
                <small>Produk tidak bisa diubah saat edit. Silakan hapus dan buat baru jika ingin ganti produk.</small>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="discount_percentage">Diskon (%)</label>
                <input type="number" name="discount_percentage" id="discount_percentage" class="form-control" value="<?= isset($item) ? $item->discount_percentage : ''; ?>" min="0" max="99" required>
                <small>Masukkan 0 jika tidak ada diskon.</small>
            </div>
            
            <div class="form-group">
                <label for="sort_order">Urutan Tampil</label>
                <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?= isset($item) ? $item->sort_order : '0'; ?>">
                <small>Angka kecil tampil duluan.</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan</button>
        </div>
    </form>
</div>

<script>
    // Aktifkan TomSelect
    new TomSelect('#product_id',{
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });
</script>