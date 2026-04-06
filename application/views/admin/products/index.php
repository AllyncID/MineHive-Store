<?php
$test_defaults = $test_defaults ?? [
    'product_id' => '',
    'target_username' => '',
    'quantity' => 1
];

$selected_product_id = '';
$selected_product_name = '';
$selected_product_price = 0;
$selected_product_realm = '';

if (!empty($selected_test_product)) {
    $selected_product_id = is_object($selected_test_product)
        ? (int) $selected_test_product->id
        : (int) ($selected_test_product['id'] ?? 0);
    $selected_product_name = is_object($selected_test_product)
        ? (string) $selected_test_product->name
        : (string) ($selected_test_product['name'] ?? '');
    $selected_product_price = is_object($selected_test_product)
        ? (float) $selected_test_product->price
        : (float) ($selected_test_product['price'] ?? 0);
    $selected_product_realm = is_object($selected_test_product)
        ? (string) $selected_test_product->realm
        : (string) ($selected_test_product['realm'] ?? '');
}
?>

<style>
.product-test-panel,
.product-test-result {
    margin-bottom: 25px;
}

.product-test-grid {
    display: grid;
    grid-template-columns: 2fr 1.15fr 0.7fr auto auto;
    gap: 16px;
    align-items: end;
}

.product-test-summary {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.92rem;
    line-height: 1.6;
}

.product-test-summary strong {
    color: var(--text-primary);
}

.product-test-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

.product-test-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 999px;
    background: var(--bg-hover);
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.product-test-status {
    margin: 0 0 18px;
    font-weight: 600;
}

.product-test-status.success {
    color: #9AE6B4;
}

.product-test-status.error {
    color: #FEB2B2;
}

.product-test-warning-box {
    margin-bottom: 18px;
    padding: 16px 18px;
    border-radius: 10px;
    border: 1px solid rgba(245, 158, 11, 0.35);
    background: rgba(245, 158, 11, 0.08);
}

.product-test-warning-box strong {
    display: block;
    margin-bottom: 8px;
    color: #F6AD55;
}

.product-test-warning-box ul {
    margin: 0;
    padding-left: 18px;
    color: var(--text-secondary);
}

.product-test-command-list {
    display: grid;
    gap: 12px;
}

.product-test-command-item {
    padding: 14px 16px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: var(--bg-main);
}

.product-test-command-item code {
    display: block;
    margin-top: 8px;
    padding: 10px 12px;
    border-radius: 8px;
    background: rgba(15, 23, 42, 0.65);
    color: #F7FAFC;
    font-size: 0.88rem;
    white-space: pre-wrap;
    word-break: break-word;
}

.product-test-command-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    color: var(--text-secondary);
    font-size: 0.88rem;
}

.product-test-command-meta strong {
    color: var(--text-primary);
}

.product-row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.btn-test-purchase {
    background-color: #2F855A;
    color: white;
}

.btn-test-purchase:hover {
    background-color: #276749;
}

@media (max-width: 1200px) {
    .product-test-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .product-test-grid {
        grid-template-columns: 1fr;
    }

    .product-row-actions {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<h1>Manage Products</h1>
<p>Kelola semua produk yang dijual di toko Anda.</p>
<a href="<?= base_url('admin/products/add'); ?>" class="btn btn-primary" style="margin-bottom: 20px; display: inline-block;"><i class="fas fa-plus"></i> Tambah Produk Baru</a>

<div class="dashboard-filters" style="margin-bottom: 25px;">
    <?php
        $realms = [
            'all' => 'All',
            'survival' => 'Survival',
            'skyblock' => 'Skyblock',
            'acidisland' => 'AcidIsland',
            'oneblock' => 'OneBlock'
        ];
    ?>
    <?php foreach($realms as $realm => $label): ?>
        <a href="<?= base_url('admin/products?realm=' . $realm); ?>"
           class="filter-btn <?= ($current_filter == $realm) ? 'active' : '' ?>">
           <?= html_escape($label); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if($this->session->flashdata('success')): ?>
    <div class="flash-message success"><?= html_escape($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<?php if($this->session->flashdata('error')): ?>
    <div class="flash-message" style="background-color: rgba(229, 62, 62, 0.12); border-color: rgba(229, 62, 62, 0.45); color: #FEB2B2;">
        <?= html_escape($this->session->flashdata('error')); ?>
    </div>
<?php endif; ?>

<div class="main-panel product-test-panel" id="product-test-panel">
    <h2 style="margin-top: 0;">Admin Test Purchase</h2>
    <p class="product-test-summary">
        Panel ini menjalankan command produk secara <strong>live</strong> ke nickname target untuk kebutuhan testing.
        Test ini tidak membuat invoice, tidak mencatat transaksi, tidak mengirim Discord webhook, dan untuk produk currency tidak menjalankan donation counter global.
    </p>

    <form method="post" action="<?= base_url('admin/products/test_purchase'); ?>" id="admin-test-purchase-form" style="margin-top: 20px;">
        <input type="hidden" name="product_id" id="test_product_id" value="<?= html_escape((string) $selected_product_id); ?>">
        <input type="hidden" name="return_realm" value="<?= html_escape($current_filter); ?>">
        <input type="hidden" id="test_product_price" value="<?= html_escape((string) $selected_product_price); ?>">
        <input type="hidden" id="test_product_realm" value="<?= html_escape($selected_product_realm); ?>">

        <div class="product-test-grid">
            <div class="form-group">
                <label for="test_product_name">Produk Dipilih</label>
                <input
                    type="text"
                    id="test_product_name"
                    value="<?= html_escape($selected_product_name); ?>"
                    readonly
                    placeholder="Klik tombol Test Purchase pada produk yang ingin dites">
            </div>

            <div class="form-group">
                <label for="test_target_username">Nickname Target</label>
                <input
                    type="text"
                    name="target_username"
                    id="test_target_username"
                    maxlength="32"
                    required
                    value="<?= html_escape((string) ($test_defaults['target_username'] ?? '')); ?>"
                    placeholder="Contoh: SkittDev">
            </div>

            <div class="form-group">
                <label for="test_quantity">Quantity</label>
                <input
                    type="number"
                    name="quantity"
                    id="test_quantity"
                    min="1"
                    max="99"
                    required
                    value="<?= html_escape((string) ((int) ($test_defaults['quantity'] ?? 1))); ?>">
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="test_submit_btn" <?= empty($selected_product_id) ? 'disabled' : ''; ?>>
                    <i class="fas fa-flask"></i> Jalankan Test
                </button>
            </div>

            <div class="form-group">
                <button type="button" class="btn btn-secondary" id="test_reset_btn">
                    Reset
                </button>
            </div>
        </div>

        <p class="product-test-summary" id="test_placeholder_summary" style="margin-top: 4px;">
            Placeholder <code>{grand_total}</code> akan dihitung dari harga produk x quantity.
        </p>
    </form>
</div>

<?php if (!empty($test_purchase_result) && is_array($test_purchase_result)): ?>
    <div class="main-panel product-test-result">
        <h2 style="margin-top: 0;">Hasil Test Purchase</h2>

        <p class="product-test-status <?= !empty($test_purchase_result['success']) ? 'success' : 'error'; ?>">
            <?= html_escape($test_purchase_result['message'] ?? 'Tidak ada hasil test.'); ?>
        </p>

        <div class="product-test-meta">
            <span class="product-test-tag"><strong>Produk</strong> <?= html_escape((string) ($test_purchase_result['product_name'] ?? '-')); ?></span>
            <span class="product-test-tag"><strong>Nickname</strong> <?= html_escape((string) ($test_purchase_result['target_username'] ?? '-')); ?></span>
            <span class="product-test-tag"><strong>Qty</strong> <?= html_escape((string) ((int) ($test_purchase_result['quantity'] ?? 1))); ?></span>
            <span class="product-test-tag"><strong>{grand_total}</strong> Rp <?= number_format((float) ($test_purchase_result['grand_total'] ?? 0), 0, ',', '.'); ?></span>
            <span class="product-test-tag"><strong>Command</strong> <?= html_escape((string) count($test_purchase_result['executed_commands'] ?? [])); ?>x</span>
        </div>

        <?php if (!empty($test_purchase_result['warnings'])): ?>
            <div class="product-test-warning-box">
                <strong>Peringatan</strong>
                <ul>
                    <?php foreach ($test_purchase_result['warnings'] as $warning): ?>
                        <li><?= html_escape((string) $warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($test_purchase_result['executed_commands'])): ?>
            <div class="product-test-command-list">
                <?php foreach ($test_purchase_result['executed_commands'] as $command_info): ?>
                    <div class="product-test-command-item">
                        <div class="product-test-command-meta">
                            <span><strong>Produk:</strong> <?= html_escape((string) ($command_info['product_name'] ?? '-')); ?></span>
                            <span><strong>Server:</strong> <?= html_escape((string) ($command_info['server_name'] ?? '-')); ?></span>
                            <span><strong>ID:</strong> <?= html_escape((string) ($command_info['server_id'] ?? '-')); ?></span>
                        </div>
                        <code><?= html_escape((string) ($command_info['command'] ?? '')); ?></code>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>LuckPerms Group</th>
            <th>Realm</th>
            <th>Harga</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= html_escape($product->id); ?></td>
            <td><?= html_escape($product->name); ?></td>

            <td>
                <?php
                    echo !empty($product->luckperms_group) ? html_escape($product->luckperms_group) : '-';
                ?>
            </td>

            <td><?= html_escape($product->realm); ?></td>
            <td>Rp <?= number_format($product->price, 0, ',', '.'); ?></td>
            <td>
                <div class="product-row-actions">
                    <button
                        type="button"
                        class="btn btn-test-purchase js-select-test-product"
                        data-product-id="<?= html_escape($product->id); ?>"
                        data-product-name="<?= html_escape($product->name); ?>"
                        data-product-price="<?= html_escape((string) $product->price); ?>"
                        data-product-realm="<?= html_escape($product->realm); ?>">
                        Test Purchase
                    </button>
                    <a href="<?= base_url('admin/products/edit/' . $product->id); ?>" class="btn btn-secondary">Edit</a>
                    <a href="<?= base_url('admin/products/duplicate/' . $product->id); ?>" class="btn btn-info" style="background-color: var(--accent-blue);" onclick="return confirm('Yakin ingin menduplikasi produk ini?');">Duplikat</a>
                    <a href="<?= base_url('admin/products/delete/' . $product->id); ?>" class="btn btn-danger" style="background-color: var(--accent-pink);" onclick="return confirm('Yakin ingin menghapus produk ini?');">Hapus</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('admin-test-purchase-form');
    if (!form) {
        return;
    }

    const productIdInput = document.getElementById('test_product_id');
    const productNameInput = document.getElementById('test_product_name');
    const productPriceInput = document.getElementById('test_product_price');
    const productRealmInput = document.getElementById('test_product_realm');
    const quantityInput = document.getElementById('test_quantity');
    const summaryEl = document.getElementById('test_placeholder_summary');
    const submitBtn = document.getElementById('test_submit_btn');
    const resetBtn = document.getElementById('test_reset_btn');
    const currencyFormatter = new Intl.NumberFormat('id-ID');

    function updateSummary() {
        const productPrice = parseFloat(productPriceInput.value || '0') || 0;
        const quantity = Math.max(1, parseInt(quantityInput.value || '1', 10) || 1);
        const grandTotal = Math.round(productPrice * quantity);
        const realmText = productRealmInput.value ? ' | Realm: ' + productRealmInput.value : '';

        summaryEl.innerHTML = 'Placeholder <code>{grand_total}</code>: <strong>Rp ' + currencyFormatter.format(grandTotal) + '</strong>' + realmText;
        submitBtn.disabled = !productIdInput.value;
    }

    function selectProduct(button) {
        productIdInput.value = button.dataset.productId || '';
        productNameInput.value = button.dataset.productName || '';
        productPriceInput.value = button.dataset.productPrice || '0';
        productRealmInput.value = button.dataset.productRealm || '';
        updateSummary();
        document.getElementById('product-test-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.querySelectorAll('.js-select-test-product').forEach(function(button) {
        button.addEventListener('click', function() {
            selectProduct(button);
        });
    });

    quantityInput.addEventListener('input', updateSummary);

    resetBtn.addEventListener('click', function() {
        productIdInput.value = '';
        productNameInput.value = '';
        productPriceInput.value = '0';
        productRealmInput.value = '';
        updateSummary();
    });

    form.addEventListener('submit', function(event) {
        if (!productIdInput.value) {
            event.preventDefault();
            alert('Pilih produk yang ingin dites terlebih dahulu.');
            return;
        }

        if (!window.confirm('Ini akan menjalankan command produk secara LIVE ke nickname target. Lanjutkan test purchase?')) {
            event.preventDefault();
        }
    });

    updateSummary();
});
</script>
