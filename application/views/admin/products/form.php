<?php
// [FITUR] Siapkan data untuk mode edit
$is_edit = isset($product);
$current_product_type = $is_edit ? strtolower(trim((string) $product->product_type)) : 'rank';
$current_category = $is_edit ? $product->category : 'ranks'; // Ambil kategori saat ini

// [FITUR] Siapkan data untuk bundle
$bundle_product_ids = [];
$bundle_mode = 'product_list';
$bundle_custom_description = '';
$bundle_custom_commands = '';
if ($is_edit && $current_product_type == 'bundles' && !empty($product->description)) {
    $decoded_bundle_description = json_decode($product->description, true);
    $is_numeric_bundle_list = is_array($decoded_bundle_description)
        && !empty($decoded_bundle_description)
        && array_keys($decoded_bundle_description) === range(0, count($decoded_bundle_description) - 1);

    if ($is_numeric_bundle_list) {
        foreach ($decoded_bundle_description as $bundle_item_id) {
            if (!is_scalar($bundle_item_id) || !ctype_digit((string) $bundle_item_id)) {
                $is_numeric_bundle_list = false;
                break;
            }
        }
    }

    if ($is_numeric_bundle_list) {
        $bundle_product_ids = array_map('intval', $decoded_bundle_description);
    } elseif (is_array($decoded_bundle_description) && (($decoded_bundle_description['bundle_mode'] ?? '') === 'custom_commands' || isset($decoded_bundle_description['commands']))) {
        $bundle_mode = 'custom_commands';
        $bundle_custom_description = isset($decoded_bundle_description['display_description']) && is_string($decoded_bundle_description['display_description'])
            ? $decoded_bundle_description['display_description']
            : '';

        if (!empty($decoded_bundle_description['commands']) && is_array($decoded_bundle_description['commands'])) {
            $bundle_custom_commands = implode("\n", array_filter(array_map('strval', $decoded_bundle_description['commands']), function($command) {
                return trim($command) !== '';
            }));
        }
    } else {
        $bundle_mode = 'custom_commands';
        $bundle_custom_description = $product->description;
    }
}

if ($is_edit && $current_product_type == 'bundles' && empty($bundle_product_ids)) {
    $bundle_mode = 'custom_commands';
    if ($bundle_custom_description === '' && !empty($product->description)) {
        $bundle_custom_description = $product->description;
    }
    if ($bundle_custom_commands === '' && !empty($product->command)) {
        $bundle_custom_commands = $product->command;
    }
}

// [FITUR BARU] Siapkan data untuk battlepass
$battlepass_config_json = '';
if ($is_edit && $current_product_type == 'battlepass' && !empty($product->description)) {
    $battlepass_config_json = $product->description;
}

$bucks_kaget_config = [
    'display_description' => '',
    'default_total_bucks' => 10,
    'min_total_bucks' => 1,
    'max_total_bucks' => 500,
    'default_recipients' => 10,
    'min_recipients' => 1,
    'max_recipients' => 100,
    'default_expiry_hours' => 24,
    'min_expiry_hours' => 1,
    'max_expiry_hours' => 168,
    'price_tiers' => [
        ['min_qty' => 1, 'price_per_buck' => 7000],
        ['min_qty' => 10, 'price_per_buck' => 6850],
        ['min_qty' => 25, 'price_per_buck' => 6700],
        ['min_qty' => 50, 'price_per_buck' => 6550],
        ['min_qty' => 100, 'price_per_buck' => 6400],
        ['min_qty' => 200, 'price_per_buck' => 6250],
    ]
];

if ($is_edit && $current_product_type == 'bucks_kaget' && !empty($product->description)) {
    $decoded_bucks_kaget = json_decode($product->description, true);
    if (is_array($decoded_bucks_kaget)) {
        $bucks_kaget_config = array_merge($bucks_kaget_config, $decoded_bucks_kaget);
    }
}
?>

<h1><?= $is_edit ? 'Edit' : 'Tambah'; ?> Produk</h1>

<div class="form-container">
    <form action="" method="post">
        
        <div class="form-group">
            <label for="product_type">Tipe Produk</label>
            <select id="product_type" name="product_type" required>
                <option value="rank" <?= ($current_product_type == 'rank') ? 'selected' : ''; ?>>Rank</option>
                <option value="currency" <?= ($current_product_type == 'currency') ? 'selected' : ''; ?>>Mata Uang (Currency/Bucks)</option>
                <option value="bucks_kaget" <?= ($current_product_type == 'bucks_kaget') ? 'selected' : ''; ?>>Bucks Kaget</option>
                <option value="bundles" <?= ($current_product_type == 'bundles') ? 'selected' : ''; ?>>Bundles (Paket Item)</option>
                <!-- [PENAMBAHAN BARU] Tipe produk Battlepass -->
                <option value="battlepass" <?= ($current_product_type == 'battlepass') ? 'selected' : ''; ?>>Battlepass Level (Tipe Kuantitas)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="category">Kategori Toko (Untuk Tampilan di Store)</label>
            <select id="category" name="category" required>
                <option value="ranks" <?= ($current_category == 'ranks') ? 'selected' : ''; ?>>Ranks</option>
                <option value="rank_upgrades" <?= ($current_category == 'rank_upgrades') ? 'selected' : ''; ?>>Rank Upgrades</option>
                <option value="currency" <?= ($current_category == 'currency') ? 'selected' : ''; ?>>Currency / Bucks</option>
                <option value="bundles" <?= ($current_category == 'bundles') ? 'selected' : ''; ?>>Bundles</option>
                <option value="gktau" <?= ($current_category == 'gktau') ? 'selected' : ''; ?>>gktau (Lain-lain)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="name">Nama Produk (Cth: [VIP] Rank atau 1x Battlepass Level)</label>
            <input type="text" id="name" name="name" value="<?= $is_edit ? html_escape($product->name) : ''; ?>" required>
        </div>

        <!-- [UPDATE] Wrapper untuk field spesifik Tipe Produk -->

        <div id="luckperms-group-wrapper" class="form-group toggleable-field">
            <label for="luckperms_group">LuckPerms Group Identifier (Wajib untuk Rank)</label>
            <input type="text" id="luckperms_group" name="luckperms_group" 
                value="<?= $is_edit ? html_escape($product->luckperms_group ?? '') : ''; ?>" 
                placeholder="Cth: vip, mvp, warden (sesuai nama grup di LuckPerms)">
        </div>

        <div id="description-wrapper" class="form-group toggleable-field">
            <label for="description_simple">Deskripsi Singkat (Untuk Currency)</label>
            <textarea id="description_simple" name="description_simple" rows="4"><?= ($is_edit && $current_product_type == 'currency') ? html_escape($product->description) : ''; ?></textarea>
        </div>

        <div id="bucks-kaget-config-wrapper" class="toggleable-field">
            <div class="form-group">
                <label for="bucks_kaget_description">Deskripsi Popup / Detail Produk</label>
                <textarea id="bucks_kaget_description" name="bucks_kaget_description" rows="4"><?= html_escape((string) ($bucks_kaget_config['display_description'] ?? '')); ?></textarea>
                <small>Teks ini akan muncul di popup detail produk dan sebagai penjelasan singkat di cart.</small>
            </div>

            <div class="bucks-kaget-grid">
                <div class="form-group">
                    <label for="bucks_kaget_default_total_bucks">Default Total Bucks</label>
                    <input type="number" id="bucks_kaget_default_total_bucks" name="bucks_kaget_default_total_bucks" min="1" value="<?= html_escape((string) ($bucks_kaget_config['default_total_bucks'] ?? 10)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_min_total_bucks">Minimal Total Bucks</label>
                    <input type="number" id="bucks_kaget_min_total_bucks" name="bucks_kaget_min_total_bucks" min="1" value="<?= html_escape((string) ($bucks_kaget_config['min_total_bucks'] ?? 1)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_max_total_bucks">Maksimal Total Bucks</label>
                    <input type="number" id="bucks_kaget_max_total_bucks" name="bucks_kaget_max_total_bucks" min="1" value="<?= html_escape((string) ($bucks_kaget_config['max_total_bucks'] ?? 500)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_default_recipients">Default Total Penerima</label>
                    <input type="number" id="bucks_kaget_default_recipients" name="bucks_kaget_default_recipients" min="1" value="<?= html_escape((string) ($bucks_kaget_config['default_recipients'] ?? 10)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_min_recipients">Minimal Penerima</label>
                    <input type="number" id="bucks_kaget_min_recipients" name="bucks_kaget_min_recipients" min="1" value="<?= html_escape((string) ($bucks_kaget_config['min_recipients'] ?? 1)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_max_recipients">Maksimal Penerima</label>
                    <input type="number" id="bucks_kaget_max_recipients" name="bucks_kaget_max_recipients" min="1" value="<?= html_escape((string) ($bucks_kaget_config['max_recipients'] ?? 100)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_default_expiry_hours">Default Expired (Jam)</label>
                    <input type="number" id="bucks_kaget_default_expiry_hours" name="bucks_kaget_default_expiry_hours" min="1" value="<?= html_escape((string) ($bucks_kaget_config['default_expiry_hours'] ?? 24)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_min_expiry_hours">Minimal Expired (Jam)</label>
                    <input type="number" id="bucks_kaget_min_expiry_hours" name="bucks_kaget_min_expiry_hours" min="1" value="<?= html_escape((string) ($bucks_kaget_config['min_expiry_hours'] ?? 1)); ?>">
                </div>
                <div class="form-group">
                    <label for="bucks_kaget_max_expiry_hours">Maksimal Expired (Jam)</label>
                    <input type="number" id="bucks_kaget_max_expiry_hours" name="bucks_kaget_max_expiry_hours" min="1" value="<?= html_escape((string) ($bucks_kaget_config['max_expiry_hours'] ?? 168)); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="bucks_kaget_price_tiers">Tier Harga per Bucks (JSON)</label>
                <textarea id="bucks_kaget_price_tiers" name="bucks_kaget_price_tiers" rows="8"><?= html_escape(json_encode($bucks_kaget_config['price_tiers'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></textarea>
                <small>
                    Harga dasar ambil dari field <code>Price</code> utama produk. Tier ini dipakai untuk diskon quantity Bucks Kaget.
                    Format contoh:
                    <code>[{"min_qty":1,"price_per_buck":7000},{"min_qty":10,"price_per_buck":6850}]</code>
                </small>
            </div>

            <div class="form-group">
                <small>
                    Produk ini akan selalu tampil di kategori <code>Currency / Bucks</code> dan diproses sebagai link claim random, bukan command currency biasa.
                    Set <code>Price</code> produk sebagai harga dasar 1 Bucks, misalnya <code>7000</code>.
                </small>
            </div>
        </div>
        
        <div id="perks-editor-wrapper" class="form-group toggleable-field">
            <label>Keuntungan / Perks (Untuk Rank)</label>
            <div id="perks-editor">
                <!-- Editor Perks akan di-generate oleh JS di admin_footer.php -->
            </div>
            <textarea name="description_perks" id="description_hidden" style="display: none;"><?= ($is_edit && $current_product_type == 'rank') ? html_escape($product->description) : ''; ?></textarea>
        </div>

        <div id="bundle-mode-wrapper" class="form-group toggleable-field">
            <label for="bundle_mode">Mode Bundle</label>
            <select id="bundle_mode" name="bundle_mode">
                <option value="product_list" <?= ($bundle_mode == 'product_list') ? 'selected' : ''; ?>>Bundle dari Produk Store</option>
                <option value="custom_commands" <?= ($bundle_mode == 'custom_commands') ? 'selected' : ''; ?>>Custom Bundle (Multi Command)</option>
            </select>
            <small>Pilih mode lama kalau isi bundle berasal dari produk store yang sudah ada. Pilih custom kalau mau isi command langsung tanpa bikin produk satu-satu.</small>
        </div>

        <div id="bundle-items-wrapper" class="form-group toggleable-field">
            <label for="bundle_product_ids">Isi Bundle (Pilih produk yang termasuk)</label>
            <select name="bundle_product_ids[]" id="bundle_product_ids" multiple>
                <option value="">Pilih produk...</option>
                <?php if (isset($all_products) && !empty($all_products)): ?>
                    <?php foreach($all_products as $prod): ?>
                        <?php
                        if ($is_edit && $prod->id == $product->id) continue;
                        $option_text = html_escape($prod->name) . ' (' . html_escape(ucfirst($prod->realm)) . ')';
                        ?>
                        <option value="<?= $prod->id; ?>" 
                                data-text="<?= $option_text; ?>"
                                <?= in_array($prod->id, $bundle_product_ids) ? 'selected' : ''; ?>>
                            [<?= html_escape(ucfirst($prod->realm)); ?>] <?= html_escape($prod->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small>Pilih semua produk yang akan otomatis ditambahkan ke keranjang saat bundle ini dibeli.</small>
        </div>

        <div id="bundle-custom-description-wrapper" class="form-group toggleable-field">
            <label for="description_bundle_custom">Detail Bundle (Untuk Tampilan Popup)</label>
            <textarea id="description_bundle_custom" name="description_bundle_custom" rows="6"><?= html_escape($bundle_custom_description); ?></textarea>
            <small>
                Bisa diisi teks biasa atau JSON object perks.<br>
                Contoh teks biasa: <code>Initiate 30 hari\n5x Common Key\n2x Rare Key</code><br>
                Contoh JSON: <code>{"Includes":["Initiate 30 hari","5x Common Key"],"Bonus":["Telepathy I"]}</code>
            </small>
        </div>

        <div id="bundle-custom-commands-wrapper" class="form-group toggleable-field">
            <label for="bundle_commands">Daftar Command Bundle</label>
            <textarea id="bundle_commands" name="bundle_commands" rows="8"><?= html_escape($bundle_custom_commands); ?></textarea>
            <small>
                Satu baris = satu command. Semua baris akan dieksekusi saat bundle dibeli.<br>
                Gunakan <code>{username}</code> sebagai placeholder pemain.
            </small>
        </div>

        <!-- [FITUR BARU] Wrapper untuk Konfigurasi Battlepass -->
        <div id="battlepass-config-wrapper" class="form-group toggleable-field">
            <label for="description_battlepass">Konfigurasi Diskon Tier (Format JSON)</label>
            <textarea id="description_battlepass" name="description_battlepass" rows="8"><?= $battlepass_config_json; ?></textarea>
            <small>
                Simpan konfigurasi harga bertingkat di sini. Harga diurutkan dari kuantitas KECIL ke BESAR.<br>
                <strong>Format Wajib:</strong> (JSON kamu udah bener, tapi ada koma kurang 1)<br>
                <code>[</code><br>
                <code>  {"min_qty": 1, "price_per_level": 5000},</code><br>
                <code>  {"min_qty": 5, "price_per_level": 4500},</code><br>
                <code>  {"min_qty": 15, "price_per_level": 4000},</code><br>
                <code>  {"min_qty": 25, "price_per_level": 3500},</code><br>
                <code>  {"min_qty": 50, "price_per_level": 3000}</code><br>
                <code>]</code>
            </small>
        </div>
        <!-- Akhir Wrapper -->

        <div class="form-group">
            <label for="price">Harga Jual</label>
            <input type="number" id="price" name="price" value="<?= $is_edit ? $product->price : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="original_price">Harga Asli (Harga coret, opsional)</label>
            <input type="number" id="original_price" name="original_price" value="<?= $is_edit ? $product->original_price : ''; ?>">
        </div>
        <div class="form-group">
            <label for="realm">Realm (Pilih realm utama untuk produk ini)</label>
            <input type="text" id="realm" name="realm" value="<?= $is_edit ? html_escape($product->realm) : ''; ?>" required>
        </div>
         <div class="form-group">
            <label for="image_url">URL Gambar</label>
            <input type="text" id="image_url" name="image_url" value="<?= $is_edit ? html_escape($product->image_url) : ''; ?>">
        </div>
        <div id="command-wrapper" class="form-group">
            <label for="command">Perintah (Command)</label>
            <textarea id="command" name="command" rows="4"><?= $is_edit ? html_escape($product->command) : ''; ?></textarea>
            <small>
                Gunakan <code>{username}</code> sebagai placeholder nama pemain.<br>
                Jika butuh lebih dari satu command, tulis satu command per baris.<br>
                <strong>[BARU]</strong> Untuk Battlepass, gunakan <code>{quantity}</code> sebagai placeholder jumlah.<br>
                <strong>Contoh Battlepass:</strong> <code>lp user {username} meta add battlepass_level {quantity}</code>
            </small>
        </div>
        
        <div class="form-group">
            <label for="is_active">Status Produk</label>
            <select id="is_active" name="is_active" class="form-control">
                <option value="1" <?= ($is_edit && $product->is_active == 1) ? 'selected' : ''; ?>>
                    Aktif (Tampil di Toko)
                </option>
                <option value="0" 
                    <?php 
                        if ($is_edit && $product->is_active == 0) {
                            echo 'selected';
                        } elseif (!$is_edit) { // Default ke Non-Aktif (Draft) untuk produk baru
                            echo 'selected';
                        }
                    ?>>
                    Non-Aktif (Draft)
                </option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Produk</button>
            <a href="<?= base_url('admin/products'); ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<!-- [PERBAIKAN] Tambahkan style di sini untuk atasi bug transparan -->
<style>
    /* Perbaikan untuk TomSelect transparan */
    .ts-dropdown {
        z-index: 1050 !important; /* Pastikan di atas elemen form lain */
        /* Ganti warna background solid (sesuai tema admin.css) */
        background-color: #1E180E; 
        border: 1px solid rgb(43, 36, 21);
    }
    .ts-dropdown .ts-option {
        padding: 10px 15px; /* Sedikit padding agar rapi */
    }
    .ts-dropdown .ts-option.active {
        background-color: #1A150C; /* Warna hover/active */
        color: #FFFFFF;
    }

    /* [BARU] Style untuk textarea JSON */
    #description_battlepass {
        font-family: 'Fira Code', monospace;
        color: #ECCA01;
        background-color: #1A140F;
        border-color: #4B3420;
    }

    #bundle_commands,
    #command {
        font-family: 'Fira Code', monospace;
        background-color: #1A140F;
        border-color: #4B3420;
    }

    .bucks-kaget-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
    }
</style>

<!-- [UPDATE] Tambahkan TomSelect untuk Bundle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelector = document.getElementById('product_type');
    const luckpermsWrapper = document.getElementById('luckperms-group-wrapper');
    const descriptionWrapper = document.getElementById('description-wrapper');
    const perksWrapper = document.getElementById('perks-editor-wrapper');
    const commandWrapper = document.getElementById('command-wrapper');
    const bundleModeWrapper = document.getElementById('bundle-mode-wrapper');
    const bundleWrapper = document.getElementById('bundle-items-wrapper');
    const bundleModeSelector = document.getElementById('bundle_mode');
    const bundleCustomDescriptionWrapper = document.getElementById('bundle-custom-description-wrapper');
    const bundleCustomCommandsWrapper = document.getElementById('bundle-custom-commands-wrapper');
    // [BARU] Ambil wrapper battlepass
    const battlepassWrapper = document.getElementById('battlepass-config-wrapper');
    const bucksKagetWrapper = document.getElementById('bucks-kaget-config-wrapper');
    // [BARU] Ambil dropdown kategori
    const categorySelector = document.getElementById('category');
    const realmInput = document.getElementById('realm');

    if (document.getElementById('bundle_product_ids')) {
        new TomSelect('#bundle_product_ids', {
            plugins: ['remove_button'],
            placeholder: 'Cari dan pilih produk...',
            searchField: ['text'],
            render: {
                option: function(data, escape) {
                    var text = data.text; 
                    var match = text.match(/\[(.*?)\] (.*)/);
                    if (match) {
                        return '<div>' + escape(match[2]) + ' <span style="color: #888; font-size: 0.9em;">(' + escape(match[1]) + ')</span></div>';
                    }
                    return '<div>' + escape(text) + '</div>';
                },
                item: function(data, escape) {
                     var text = data.text;
                     var match = text.match(/\[(.*?)\] (.*)/);
                     if (match) {
                        return '<div>' + escape(match[2]) + ' <span style="color: #aaa;">(' + escape(match[1]) + ')</span></div>';
                    }
                    return '<div>' + escape(text) + '</div>';
                }
            }
        });
    }

    function toggleBundleModeFields() {
        const selectedBundleMode = bundleModeSelector ? bundleModeSelector.value : 'product_list';

        bundleWrapper.style.display = 'none';
        bundleCustomDescriptionWrapper.style.display = 'none';
        bundleCustomCommandsWrapper.style.display = 'none';

        if (selectedBundleMode === 'custom_commands') {
            bundleCustomDescriptionWrapper.style.display = 'block';
            bundleCustomCommandsWrapper.style.display = 'block';
        } else {
            bundleWrapper.style.display = 'block';
        }
    }

    function toggleFields() {
        const selectedType = typeSelector.value;

        // Sembunyikan semua field unik dulu
        luckpermsWrapper.style.display = 'none';
        descriptionWrapper.style.display = 'none';
        perksWrapper.style.display = 'none';
        commandWrapper.style.display = 'block';
        bundleModeWrapper.style.display = 'none';
        bundleWrapper.style.display = 'none';
        bundleCustomDescriptionWrapper.style.display = 'none';
        bundleCustomCommandsWrapper.style.display = 'none';
        battlepassWrapper.style.display = 'none'; // [BARU] Sembunyikan battlepass
        bucksKagetWrapper.style.display = 'none';

        // Tampilkan field yang sesuai
        if (selectedType === 'rank') {
            luckpermsWrapper.style.display = 'block';
            perksWrapper.style.display = 'block';
        } else if (selectedType === 'currency') {
            descriptionWrapper.style.display = 'block';
        } else if (selectedType === 'bucks_kaget') {
            bucksKagetWrapper.style.display = 'block';
            commandWrapper.style.display = 'none';
            descriptionWrapper.style.display = 'none';
            if (categorySelector) {
                categorySelector.value = 'currency';
            }
            if (realmInput) {
                realmInput.value = 'global';
            }
        } else if (selectedType === 'bundles') {
            commandWrapper.style.display = 'none';
            bundleModeWrapper.style.display = 'block';
            toggleBundleModeFields();
            if (categorySelector) {
                categorySelector.value = 'bundles';
            }
        } else if (selectedType === 'battlepass') {
            // [BARU] Tampilkan wrapper battlepass
            battlepassWrapper.style.display = 'block';
            // [BARU] Otomatis pilih kategori 'bundles'
            if (categorySelector) {
                categorySelector.value = 'bundles';
            }
        }
    }

    // Jalankan saat halaman dimuat
    toggleFields();

    // Jalankan setiap kali pilihan tipe produk berubah
    typeSelector.addEventListener('change', toggleFields);
    if (bundleModeSelector) {
        bundleModeSelector.addEventListener('change', toggleBundleModeFields);
        bundleModeSelector.addEventListener('change', function() {
            if (typeSelector.value === 'bundles' && categorySelector) {
                categorySelector.value = 'bundles';
            }
        });
    }
});
</script>
