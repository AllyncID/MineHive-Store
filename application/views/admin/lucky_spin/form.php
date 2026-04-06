<?php
$reward_types = $this->input->post('reward_type');
$reward_labels = $this->input->post('reward_label');
$bucks_amounts = $this->input->post('bucks_amount');
$product_ids = $this->input->post('product_id');
$reward_weights = $this->input->post('reward_weight');
$reward_stocks = $this->input->post('reward_stock');

if (!is_array($reward_types) || empty($reward_types)) {
    $reward_types = ['bucks', 'product', 'zonk'];
    $reward_labels = ['', '', ''];
    $bucks_amounts = ['100', '', ''];
    $product_ids = ['', '', ''];
    $reward_weights = ['50', '35', '15'];
    $reward_stocks = ['', '', ''];
}
?>

<div class="page-header">
    <h1><i class='bx bxs-dice-5'></i> Buat Lucky Spin Baru</h1>
    <a href="<?= base_url('admin/lucky_spin'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<?php if (validation_errors()): ?>
    <div class="flash-message"><?= validation_errors(); ?></div>
<?php endif; ?>

<?php if ($this->session->flashdata('error')): ?>
    <div class="flash-message"><?= html_escape($this->session->flashdata('error')); ?></div>
<?php endif; ?>

<?php if (!empty($manual_error)): ?>
    <div class="flash-message"><?= html_escape($manual_error); ?></div>
<?php endif; ?>

<div class="content-card">
    <form action="" method="POST">
        <div class="form-group">
            <label for="name">Nama Campaign</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= set_value('name'); ?>" placeholder="Contoh: Lucky Spin Discord Weekend" required>
            <small>Nama ini tampil di admin dan halaman spin pemain.</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="max_players">Jumlah Orang yang Bisa Spin</label>
                <input type="number" name="max_players" id="max_players" class="form-control" value="<?= set_value('max_players', '1'); ?>" min="1" required>
                <small>Contoh `1` berarti hanya pemain pertama yang berhasil masuk yang bisa ikut spin.</small>
            </div>

            <div class="form-group">
                <label for="max_spins_per_player">Batas Spin per Player</label>
                <input type="number" name="max_spins_per_player" id="max_spins_per_player" class="form-control" value="<?= set_value('max_spins_per_player', '1'); ?>" min="1" required>
                <small>Jumlah spin maksimal untuk satu nickname pada campaign ini.</small>
            </div>
        </div>

        <div class="form-group">
            <label for="expires_at">Waktu Expired (Opsional)</label>
            <input type="text" name="expires_at" id="expires_at" class="form-control flatpickr-datetime" value="<?= set_value('expires_at'); ?>" placeholder="Kosongkan jika tidak ingin ada expired time">
            <small>Kalau dikosongkan, Lucky Spin aktif sampai dinonaktifkan admin atau hadiah habis.</small>
        </div>

        <div class="form-group">
            <label class="toggle-switch-label" for="is_active_checkbox">Status Campaign</label>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active_checkbox" name="is_active" value="1" <?= set_checkbox('is_active', '1', TRUE); ?>>
                <span class="slider"></span>
            </label>
            <small>Kalau aktif, link Lucky Spin bisa langsung dipakai setelah campaign dibuat.</small>
        </div>

        <div class="content-note">
            <strong>Catatan:</strong> hasil spin dipilih di server, bukan di browser, jadi lebih aman untuk event atau giveaway publik.
        </div>

        <div class="content-card lucky-spin-builder-card">
            <div class="lucky-spin-builder-header">
                <div>
                    <h3>Pool Hadiah</h3>
                    <p>Campur hadiah Bucks, produk store, atau zonk. Weight makin besar berarti peluang makin besar.</p>
                </div>
                <button type="button" class="btn btn-primary" id="addRewardRowBtn"><i class='bx bx-plus'></i> Tambah Hadiah</button>
            </div>

            <div class="lucky-spin-reward-list" id="rewardRows">
                <?php foreach ($reward_types as $index => $type): ?>
                    <div class="lucky-spin-reward-row" data-index="<?= $index; ?>">
                        <div class="lucky-spin-reward-topbar">
                            <strong>Hadiah #<?= $index + 1; ?></strong>
                            <button type="button" class="btn btn-sm btn-danger remove-reward-row"><i class='bx bx-trash'></i></button>
                        </div>

                        <div class="lucky-spin-reward-grid">
                            <div class="form-group">
                                <label>Tipe Hadiah</label>
                                <select name="reward_type[]" class="form-control reward-type-select">
                                    <option value="bucks" <?= strtolower((string) $type) === 'bucks' ? 'selected' : ''; ?>>Bucks</option>
                                    <option value="product" <?= strtolower((string) $type) === 'product' ? 'selected' : ''; ?>>Product</option>
                                    <option value="zonk" <?= strtolower((string) $type) === 'zonk' ? 'selected' : ''; ?>>Zonk</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Label Hadiah</label>
                                <input type="text" name="reward_label[]" class="form-control" value="<?= html_escape($reward_labels[$index] ?? ''); ?>" placeholder="Kosongkan untuk label otomatis">
                            </div>

                            <div class="form-group reward-type-bucks">
                                <label>Nominal Bucks</label>
                                <input type="number" name="bucks_amount[]" class="form-control" value="<?= html_escape($bucks_amounts[$index] ?? ''); ?>" min="1" placeholder="100">
                            </div>

                            <div class="form-group reward-type-product">
                                <label>Pilih Product</label>
                                <select name="product_id[]" class="form-control">
                                    <option value="">Pilih produk...</option>
                                    <?php foreach ($products as $product): ?>
                                        <?php $product_label = $product['name'] . ' [' . ucfirst((string) ($product['category'] ?? 'general')) . ']'; ?>
                                        <option value="<?= (int) $product['id']; ?>" <?= (string) ($product_ids[$index] ?? '') === (string) $product['id'] ? 'selected' : ''; ?>>
                                            <?= html_escape($product_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Weight / Peluang</label>
                                <input type="number" name="reward_weight[]" class="form-control" value="<?= html_escape($reward_weights[$index] ?? '1'); ?>" min="1" placeholder="10">
                            </div>

                            <div class="form-group">
                                <label>Stock Hadiah (Opsional)</label>
                                <input type="number" name="reward_stock[]" class="form-control" value="<?= html_escape($reward_stocks[$index] ?? ''); ?>" min="1" placeholder="Kosongkan = unlimited">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Generate Link Lucky Spin</button>
        </div>
    </form>
</div>

<template id="rewardRowTemplate">
    <div class="lucky-spin-reward-row" data-index="__INDEX__">
        <div class="lucky-spin-reward-topbar">
            <strong>Hadiah #__NUMBER__</strong>
            <button type="button" class="btn btn-sm btn-danger remove-reward-row"><i class='bx bx-trash'></i></button>
        </div>

        <div class="lucky-spin-reward-grid">
            <div class="form-group">
                <label>Tipe Hadiah</label>
                <select name="reward_type[]" class="form-control reward-type-select">
                    <option value="bucks">Bucks</option>
                    <option value="product">Product</option>
                    <option value="zonk">Zonk</option>
                </select>
            </div>

            <div class="form-group">
                <label>Label Hadiah</label>
                <input type="text" name="reward_label[]" class="form-control" value="" placeholder="Kosongkan untuk label otomatis">
            </div>

            <div class="form-group reward-type-bucks">
                <label>Nominal Bucks</label>
                <input type="number" name="bucks_amount[]" class="form-control" value="" min="1" placeholder="100">
            </div>

            <div class="form-group reward-type-product">
                <label>Pilih Product</label>
                <select name="product_id[]" class="form-control">
                    <option value="">Pilih produk...</option>
                    <?php foreach ($products as $product): ?>
                        <?php $product_label = $product['name'] . ' [' . ucfirst((string) ($product['category'] ?? 'general')) . ']'; ?>
                        <option value="<?= (int) $product['id']; ?>"><?= html_escape($product_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Weight / Peluang</label>
                <input type="number" name="reward_weight[]" class="form-control" value="10" min="1" placeholder="10">
            </div>

            <div class="form-group">
                <label>Stock Hadiah (Opsional)</label>
                <input type="number" name="reward_stock[]" class="form-control" value="" min="1" placeholder="Kosongkan = unlimited">
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rewardRows = document.getElementById('rewardRows');
    const addButton = document.getElementById('addRewardRowBtn');
    const template = document.getElementById('rewardRowTemplate');

    const toggleRewardFields = row => {
        const typeSelect = row.querySelector('.reward-type-select');
        const bucksGroup = row.querySelector('.reward-type-bucks');
        const productGroup = row.querySelector('.reward-type-product');
        const type = typeSelect ? typeSelect.value : 'bucks';

        if (bucksGroup) {
            bucksGroup.style.display = type === 'bucks' ? '' : 'none';
        }

        if (productGroup) {
            productGroup.style.display = type === 'product' ? '' : 'none';
        }
    };

    const refreshRowNumbers = () => {
        rewardRows.querySelectorAll('.lucky-spin-reward-row').forEach((row, index) => {
            row.dataset.index = index;
            const title = row.querySelector('.lucky-spin-reward-topbar strong');
            if (title) {
                title.textContent = 'Hadiah #' + (index + 1);
            }
        });
    };

    const bindRow = row => {
        const typeSelect = row.querySelector('.reward-type-select');
        const removeButton = row.querySelector('.remove-reward-row');

        if (typeSelect) {
            typeSelect.addEventListener('change', () => toggleRewardFields(row));
        }

        if (removeButton) {
            removeButton.addEventListener('click', () => {
                if (rewardRows.querySelectorAll('.lucky-spin-reward-row').length <= 1) {
                    alert('Minimal harus ada satu hadiah.');
                    return;
                }

                row.remove();
                refreshRowNumbers();
            });
        }

        toggleRewardFields(row);
    };

    rewardRows.querySelectorAll('.lucky-spin-reward-row').forEach(bindRow);

    if (addButton && template) {
        addButton.addEventListener('click', () => {
            const nextIndex = rewardRows.querySelectorAll('.lucky-spin-reward-row').length;
            const html = template.innerHTML
                .replace(/__INDEX__/g, String(nextIndex))
                .replace(/__NUMBER__/g, String(nextIndex + 1));
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const newRow = wrapper.firstElementChild;
            rewardRows.appendChild(newRow);
            bindRow(newRow);
            refreshRowNumbers();
        });
    }
});
</script>
