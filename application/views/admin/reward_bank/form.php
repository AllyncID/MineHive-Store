<div class="page-header">
    <h1><i class='bx bxs-inbox'></i> <?= isset($reward) ? 'Edit' : 'Tambah'; ?> Hadiah</h1>
    <a href="<?= base_url('admin/reward_bank'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali ke Bank Hadiah</a>
</div>

<div class="content-card">
    <form action="" method="POST">
        <div class="form-group">
            <label for="display_name">Nama Hadiah (Dilihat User)</label>
            <input type="text" name="display_name" id="display_name" class="form-control" value="<?= isset($reward) ? html_escape($reward->display_name) : ''; ?>" placeholder="Cth: 1x Legendary Key" required>
            <small>Ini adalah teks yang akan muncul di kartu gosok (Cth: "Selamat! Anda dapat 1x Legendary Key!").</small>
        </div>

        <div class="form-group">
            <label for="reward_type">Tipe Hadiah</label>
            <select name="reward_type" id="reward_type" class="form-control" required>
                <option value="command" <?= (isset($reward) && $reward->reward_type == 'command') ? 'selected' : ''; ?>>Command (Hadiah Langsung)</option>
                <option value="promo" <?= (isset($reward) && $reward->reward_type == 'promo') ? 'selected' : ''; ?>>Kode Promo (Untuk Nanti)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reward_value">Isi Hadiah (Value)</label>
            <input type="text" name="reward_value" id="reward_value" class="form-control" value="<?= isset($reward) ? html_escape($reward->reward_value) : ''; ?>" required>
            <small id="reward-helper">Contoh: `crate give {username} legendary 1` (Gunakan `{username}` sebagai placeholder).</small>
        </div>
        
        <div class="form-group">
            <label class="toggle-switch-label" for="is_active_checkbox">Status Hadiah</label>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active_checkbox" name="is_active" value="1" <?= (isset($reward) && $reward->is_active == 1) ? 'checked' : (!isset($reward) ? 'checked' : ''); ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan Hadiah</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('reward_type');
    const helperText = document.getElementById('reward-helper');
    
    function updateHelperText() {
        if (typeSelect.value === 'promo') {
            helperText.textContent = "Contoh: `BF-DISKON15` (Masukkan kode promo yang sudah Anda buat di menu 'Kode Promo').";
        } else {
            helperText.textContent = "Contoh: `crate give {username} legendary 1` (Gunakan `{username}` sebagai placeholder).";
        }
    }
    
    updateHelperText();
    typeSelect.addEventListener('change', updateHelperText);
});
</script>