<div class="page-header">
    <h1><i class='bx bxs-bot'></i> Pengaturan Random Flash Sale</h1>
    <a href="<?= base_url('admin/flash_sales'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali ke Flash Sale</a>
</div>

<div class="content-card">
    <p>Atur sistem untuk membuat flash sale harian secara otomatis. Cron job akan berjalan setiap hari dan memilih satu produk secara acak berdasarkan pengaturan ini.</p>
    <p><strong>PENTING:</strong> Sistem otomatis ini <strong>tidak akan berjalan</strong> jika sudah ada flash sale (manual) yang sedang aktif.</p>

    <form action="<?= base_url('admin/flash_sales/settings'); ?>" method="POST">
        
        <div class="form-group">
            <label for="random_fs_enabled">Status Random Flash Sale</label>
            <select name="random_fs_enabled" id="random_fs_enabled" class="form-control">
                <option value="1" <?= ($settings['random_fs_enabled'] ?? '0') == '1' ? 'selected' : ''; ?>>Aktif</option>
                <option value="0" <?= ($settings['random_fs_enabled'] ?? '0') == '0' ? 'selected' : ''; ?>>Non-Aktif</option>
            </select>
            <small>Jika "Aktif", cron job akan membuat flash sale baru setiap hari (jika tidak ada sale manual yang aktif).</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="random_fs_min_discount">Minimal Diskon (%)</label>
                <input type="number" name="random_fs_min_discount" id="random_fs_min_discount" class="form-control" value="<?= html_escape($settings['random_fs_min_discount'] ?? '10'); ?>" min="1" max="99" required>
                <small>Contoh: 10</small>
            </div>
            <div class="form-group">
                <label for="random_fs_max_discount">Maksimal Diskon (%)</label>
                <input type="number" name="random_fs_max_discount" id="random_fs_max_discount" class="form-control" value="<?= html_escape($settings['random_fs_max_discount'] ?? '30'); ?>" min="1" max="99" required>
                <small>Contoh: 30</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="random_fs_min_duration_hours">Minimal Durasi (Jam)</label>
                <input type="number" name="random_fs_min_duration_hours" id="random_fs_min_duration_hours" class="form-control" value="<?= html_escape($settings['random_fs_min_duration_hours'] ?? '3'); ?>" min="1" required>
                <small>Contoh: 3</small>
            </div>
            <div class="form-group">
                <label for="random_fs_max_duration_hours">Maksimal Durasi (Jam)</label>
                <input type="number" name="random_fs_max_duration_hours" id="random_fs_max_duration_hours" class="form-control" value="<?= html_escape($settings['random_fs_max_duration_hours'] ?? '6'); ?>" min="1" required>
                <small>Contoh: 6</small>
            </div>
        </div>

        <!-- [BARU] Input untuk Jumlah Penjualan -->
        <div class="form-group">
            <label for="random_fs_count">Jumlah Penjualan Otomatis</label>
            <input type="number" name="random_fs_count" id="random_fs_count" class="form-control" value="<?= html_escape($settings['random_fs_count'] ?? '3'); ?>" min="1" max="10" required>
            <small>Jumlah produk yang akan di-sale acak setiap hari. (Cth: 3)</small>
        </div>
        <!-- [AKHIR BARU] -->

        <div class="form-group">
            <label for="random_fs_excluded_products">Kecualikan Produk</label>
            <select name="random_fs_excluded_products[]" id="random_fs_excluded_products" multiple>
                <option value="">Pilih produk...</option>
                <?php 
                    $excluded_ids = json_decode($settings['random_fs_excluded_products'] ?? '[]', true);
                    foreach($products as $product): 
                ?>
                    <option value="<?= $product->id; ?>" <?= in_array($product->id, $excluded_ids) ? 'selected' : ''; ?>>
                        [<?= html_escape(ucfirst($product->realm)); ?>] <?= html_escape($product->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Produk yang ada di list ini tidak akan pernah terpilih oleh sistem random flash sale.</small>
        </div>

        <!-- [BARU] Input untuk Webhook URL -->
        <div class="form-group">
            <label for="random_fs_webhook_url">Discord Webhook URL (Opsional)</label>
            <input type="text" name="random_fs_webhook_url" id="random_fs_webhook_url" class="form-control" value="<?= html_escape($settings['random_fs_webhook_url'] ?? ''); ?>" placeholder="httpsComplete Discord Webhook URL...">
            <small>Jika diisi, notifikasi flash sale baru akan dikirim ke channel Discord ini.</small>
        </div>
        <!-- [AKHIR BARU] -->

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan Pengaturan</button>
        </div>

    </form>
</div>

<script>
    // Inisialisasi TomSelect untuk dropdown multi-select
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('random_fs_excluded_products')) {
            new TomSelect('#random_fs_excluded_products', {
                plugins: ['remove_button'],
                placeholder: 'Cari dan pilih produk...'
            });
        }
    });
</script>