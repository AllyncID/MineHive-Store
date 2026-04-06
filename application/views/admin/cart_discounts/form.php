<div class="page-header">
    <h1><?= isset($tier) ? 'Edit' : 'Tambah'; ?> Tingkatan Diskon Keranjang</h1>
    <a href="<?= base_url('admin/cart_discounts'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<div class="form-container">
    <form action="" method="POST"> <!-- Action dikosongkan agar otomatis ke method controller yang sama -->
        <div class="form-group">
            <label for="min_amount">Total Keranjang Minimum (Rp)</label>
            <input type="number" name="min_amount" id="min_amount" class="form-control"
                   value="<?= isset($tier) ? $tier->min_amount : set_value('min_amount'); ?>" required>
            <?php echo form_error('min_amount', '<small class="text-danger">', '</small>'); ?>
            <small>Masukkan jumlah minimum total keranjang agar diskon ini berlaku. Contoh: 100000</small>
        </div>

        <div class="form-group">
            <label for="discount_percentage">Persentase Diskon (%)</label>
            <input type="number" name="discount_percentage" id="discount_percentage" class="form-control"
                   value="<?= isset($tier) ? $tier->discount_percentage : set_value('discount_percentage'); ?>" required max="100" min="1">
            <?php echo form_error('discount_percentage', '<small class="text-danger">', '</small>'); ?>
            <small>Masukkan persentase diskon (1-100) yang akan diberikan. Contoh: 10</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan Tingkatan</button>
        </div>
    </form>
</div>

<!-- Style untuk pesan error (jika belum ada) -->
<style> .text-danger { color: #EF4444; } </style>
