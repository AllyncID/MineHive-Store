<h1><?= isset($promo) ? 'Edit' : 'Tambah'; ?> Kode Promo</h1>
<p>Gunakan form di bawah ini untuk mengelola kode promo.</p>

<div class="form-container">
    <form action="" method="post">
        <div class="form-group">
            <label for="code">Kode Promo (Unik, tanpa spasi, huruf besar semua)</label>
            <input type="text" id="code" name="code" value="<?= isset($promo) ? html_escape($promo->code) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="type">Tipe Diskon</label>
            <select id="type" name="type" required>
                <option value="percentage" <?= (isset($promo) && $promo->type == 'percentage') ? 'selected' : ''; ?>>Persentase (%)</option>
                <option value="flat" <?= (isset($promo) && $promo->type == 'flat') ? 'selected' : ''; ?>>Potongan Flat (Rp)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="value">Nilai Diskon</label>
            <input type="number" step="0.01" id="value" name="value" value="<?= isset($promo) ? $promo->value : ''; ?>" required>
            <small>
                Jika tipe **Persentase**, masukkan dalam format desimal (contoh: `0.2` untuk 20%).<br>
                Jika tipe **Potongan Flat**, masukkan nominal angka (contoh: `10000` untuk Rp 10.000).
            </small>
        </div>

        <div class="form-group">
            <label for="expires_at">Tanggal Kedaluwarsa (Opsional)</label>
            <div class="input-with-helper">
                <input type="text" class="flatpickr-datetime" id="expires_at" name="expires_at" value="<?= isset($promo) && $promo->expires_at ? date('Y-m-d H:i', strtotime($promo->expires_at)) : ''; ?>">
                <small><i class="fas fa-info-circle"></i> Kosongkan jika tidak ada batas waktu.</small>
            </div>
        </div>

        <div class="form-group">
            <label for="usage_limit">Batas Maksimal Pemakaian (Opsional)</label>
            <input type="number" id="usage_limit" name="usage_limit" placeholder="contoh: 100" value="<?= isset($promo) ? $promo->usage_limit : ''; ?>">
            <small>Kosongkan jika tidak ada batas pemakaian (tak terbatas).</small>
        </div>

        <!-- [TAMBAHAN BARU] Toggle untuk mengizinkan stacking -->
        <div class="form-group toggle-stacked">
            <label class="toggle-switch-label">
                <input type="checkbox" name="allow_stacking" value="1" <?= (isset($promo) && $promo->allow_stacking == 1) ? 'checked' : ''; ?>>
                <span class="switch-slider"></span>
                <span class="switch-text">Izinkan dipakai bersamaan Kode Referral?</span>
            </label>
            <small>Jika diaktifkan, kode promo ini BISA dipakai bersamaan dengan kode referral.</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Kode Promo</button>
            <a href="<?= base_url('admin/promo'); ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
