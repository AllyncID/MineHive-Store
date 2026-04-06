<div class="page-header">
    <h1><?= isset($tier) ? 'Edit' : 'Tambah'; ?> Tier Bonus</h1>
    <a href="<?= base_url('admin/bonus'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<div class="content-card">
    <form action="" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="min_amount">Total Belanja Minimum</label>
                <input type="number" name="min_amount" id="min_amount" class="form-control" value="<?= isset($tier) ? $tier->min_amount : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="max_amount">Total Belanja Maksimum</label>
                <input type="number" name="max_amount" id="max_amount" class="form-control" value="<?= isset($tier) ? $tier->max_amount : ''; ?>" placeholder="Kosongkan untuk tier teratas">
                <small>Contoh: 99999. Kosongkan jika ini adalah tingkatan bonus tertinggi (misal: 300rb+).</small>
            </div>
        </div>

        <div class="form-group">
            <label for="reward_description">Deskripsi Hadiah</label>
            <input type="text" name="reward_description" id="reward_description" class="form-control" value="<?= isset($tier) ? html_escape($tier->reward_description) : ''; ?>" placeholder="Cth: 1x Legendary Key" required>
            <small>Teks ini akan ditampilkan kepada pemain jika perlu.</small>
        </div>

        <div class="form-group">
            <label for="reward_command">Command Hadiah</label>
            <input type="text" name="reward_command" id="reward_command" class="form-control" value="<?= isset($tier) ? html_escape($tier->reward_command) : ''; ?>" placeholder="Gunakan {username} sebagai placeholder nama pemain" required>
            <small>Contoh: `crate give {username} legendary_key 1`</small>
        </div>
        
        <div class="form-group">
            <label for="is_active">Status Bonus</label>
            <select name="is_active" id="is_active" class="form-control">
                <option value="1" <?= (isset($tier) && $tier->is_active == 1) ? 'selected' : ''; ?>>Aktif</option>
                <option value="0" <?= (isset($tier) && $tier->is_active == 0) ? 'selected' : ''; ?>>Non-Aktif</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan</button>
        </div>
    </form>
</div>