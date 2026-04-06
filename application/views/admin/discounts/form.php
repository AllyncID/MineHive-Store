<?php
// Tentukan variabel untuk mode edit
$is_edit = isset($discount);
$scope_type = $is_edit ? ($scopes['type'] ?? 'store-wide') : 'store-wide';
$scope_values = $is_edit ? ($scopes['values'] ?? []) : [];
?>

<h1><?= $is_edit ? 'Edit' : 'Buat'; ?> Diskon Baru</h1>
<p>Lengkapi detail di bawah untuk membuat diskon terjadwal.</p>

<div class="form-container">
    <form action="" method="post">
        
        <div class="form-group">
            <label for="name">Nama Diskon (Cth: Diskon Lebaran, Promo Akhir Pekan)</label>
            <input type="text" id="name" name="name" value="<?= $is_edit ? html_escape($discount->name) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="discount_percentage">Persentase Diskon (tanpa %)</label>
            <input type="number" id="discount_percentage" name="discount_percentage" value="<?= $is_edit ? $discount->discount_percentage : ''; ?>" required min="1" max="100">
        </div>

        <div class="form-group">
            <label for="starts_at">Waktu Mulai</label>
            <input type="text" class="flatpickr-datetime" id="starts_at" name="starts_at" value="<?= $is_edit ? $discount->starts_at : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="ends_at">Waktu Berakhir</label>
            <input type="text" class="flatpickr-datetime" id="ends_at" name="ends_at" value="<?= $is_edit ? $discount->ends_at : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="scope_type">Target Diskon (Cakupan)</label>
            <select id="scope_type" name="scope_type" required>
                <option value="store-wide" <?= ($scope_type == 'store-wide') ? 'selected' : ''; ?>>Seluruh Toko</option>
                <option value="category" <?= ($scope_type == 'category') ? 'selected' : ''; ?>>Per Kategori</option>
                <option value="product" <?= ($scope_type == 'product') ? 'selected' : ''; ?>>Per Produk</option>
            </select>
        </div>
        
        <div id="category-scope" class="form-group scope-field" style="display:none;">
            <label>Pilih Kategori</label>
            <select name="categories[]" multiple>
                <option value="ranks" <?= in_array('ranks', $scope_values) ? 'selected' : ''; ?>>Rank</option>
                <option value="currency" <?= in_array('currency', $scope_values) ? 'selected' : ''; ?>>Currency/Bucks</option>
                <option value="rank_upgrades" <?= in_array('rank_upgrades', $scope_values) ? 'selected' : ''; ?>>Rank Upgrades</option>
                <option value="bundles" <?= in_array('bundles', $scope_values) ? 'selected' : ''; ?>>Bundles</option>
            </select>
        </div>

        <div id="product-scope" class="form-group scope-field" style="display:none;">
            <label>Pilih Produk</label>
            <select name="products[]" multiple>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product->id; ?>" <?= in_array($product->id, $scope_values) ? 'selected' : ''; ?>>
                        <?= html_escape($product->name); ?> (<?= html_escape(ucfirst($product->realm)); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group toggle-stacked">
            <label class="toggle-switch-label">
                <input type="checkbox" name="is_active" value="1" <?= ($is_edit && $discount->is_active) ? 'checked' : (!$is_edit ? 'checked' : ''); ?>>
                <span class="switch-slider"></span>
                <span class="switch-text">Aktifkan Diskon Ini?</span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Diskon</button>
            <a href="<?= base_url('admin/discounts'); ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<!-- [PERBAIKAN] Tambahkan style di sini untuk atasi bug transparan -->
<style>
    /* Perbaikan untuk TomSelect transparan */
    .ts-dropdown {
        z-index: 1050 !important; /* Pastikan di atas elemen form lain */
        background-color: #2F2A22; /* Warna background solid (dari admin.css --bg-card) */
        border-color: #4B3420; /* Warna border (dari admin.css --border-color) */
    }
    .ts-dropdown .ts-option {
        padding: 10px 15px; /* Sedikit padding agar rapi */
    }
    .ts-dropdown .ts-option.active {
        background-color: #4B3420; /* Warna hover/active */
        color: #FFFFFF;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scopeType = document.getElementById('scope_type');
    const categoryScope = document.getElementById('category-scope');
    const productScope = document.getElementById('product-scope');

    function toggleScopeFields() {
        const selectedValue = scopeType.value;
        categoryScope.style.display = selectedValue === 'category' ? 'block' : 'none';
        productScope.style.display = selectedValue === 'product' ? 'block' : 'none';
    }

    // Panggil saat halaman dimuat
    toggleScopeFields();

    // Panggil setiap kali pilihan berubah
    scopeType.addEventListener('change', toggleScopeFields);

    // [BARU] Terapkan TomSelect ke multi-select
    if (document.querySelector('select[name="categories[]"]')) {
        new TomSelect('select[name="categories[]"]', {
            plugins: ['remove_button'],
            placeholder: 'Pilih satu atau lebih kategori...'
        });
    }
    if (document.querySelector('select[name="products[]"]')) {
        new TomSelect('select[name="products[]"]', {
            plugins: ['remove_button'],
            placeholder: 'Cari dan pilih produk...'
        });
    }
});
</script>