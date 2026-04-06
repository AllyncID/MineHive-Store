<div class="page-header">
    <h1><?= isset($bonus) ? 'Edit' : 'Tambah'; ?> Command Bonus</h1>
    <a href="<?= base_url('admin/first_bonus'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<div class="content-card">
    <form action="" method="POST">
        <div class="form-group">
            <label for="description">Deskripsi</label>
            <input type="text" name="description" id="description" class="form-control" value="<?= isset($bonus) ? html_escape($bonus->description) : ''; ?>" placeholder="Cth: Bonus Kunci Pemain Baru" required>
            <small>Ini hanya untuk referensi Anda di panel admin.</small>
        </div>

        <div class="form-group">
            <label for="reward_command">Command Hadiah</label>
            <input type="text" name="reward_command" id="reward_command" class="form-control" value="<?= isset($bonus) ? html_escape($bonus->reward_command) : ''; ?>" placeholder="Gunakan {username} sebagai placeholder nama pemain" required>
            <small>Contoh: `crate give {username} bonus_key 1`</small>
        </div>
        
        <div class="form-group">
            <label class="toggle-switch-label" for="is_active_checkbox">Status Bonus</label>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active_checkbox" name="is_active" value="1" <?= (isset($bonus) && $bonus->is_active == 1) ? 'checked' : (!isset($bonus) ? 'checked' : ''); ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan</button>
        </div>
    </form>
</div>
