<h1><?= isset($affiliate) ? 'Edit' : 'Tambah'; ?> Afiliasi Baru</h1>
<p>Daftarkan helper atau staf Anda ke dalam program afiliasi.</p>

<div class="form-container">
    <form action="" method="post">
        
        <div class="form-group">
            <label for="minecraft_username">Username Minecraft Helper/Staf</label>
            <input type="text" id="minecraft_username" name="minecraft_username" value="<?= isset($affiliate) ? html_escape($affiliate->minecraft_username) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email Afiliasi</label>
            <input type="email" id="email" name="email" value="<?= isset($affiliate) ? html_escape($affiliate->email) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password Awal</label>
            <input type="password" id="password" name="password" <?= isset($affiliate) ? '' : 'required'; ?>>
            <?php if (isset($affiliate)): ?>
                <small>Kosongkan jika tidak ingin mengubah password.</small>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Afiliasi</button>
            <a href="<?= base_url('admin/affiliates'); ?>" class="btn btn-secondary">Batal</a>
        </div>

    </form>
</div>