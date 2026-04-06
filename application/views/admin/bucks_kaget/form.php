<div class="page-header">
    <h1><i class='bx bxs-gift'></i> Buat Bucks Kaget Baru</h1>
    <a href="<?= base_url('admin/bucks_kaget'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali</a>
</div>

<div class="content-card">
    <form action="" method="POST">
        <div class="form-group">
            <label for="name">Nama Campaign</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= set_value('name'); ?>" placeholder="Contoh: Bucks Kaget Discord Malam Minggu" required>
            <small>Nama ini hanya tampil di admin dan halaman claim supaya gampang dibedakan.</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="total_bucks">Total Bucks</label>
                <input type="number" name="total_bucks" id="total_bucks" class="form-control" value="<?= set_value('total_bucks'); ?>" min="1" required>
                <small>Jumlah total bucks yang ingin dibagikan.</small>
            </div>

            <div class="form-group">
                <label for="total_recipients">Total Penerima</label>
                <input type="number" name="total_recipients" id="total_recipients" class="form-control" value="<?= set_value('total_recipients'); ?>" min="1" required>
                <small>Bucks akan dipecah random ke jumlah orang ini.</small>
            </div>
        </div>

        <div class="form-group">
            <label for="expires_at">Waktu Expired (Opsional)</label>
            <input type="text" name="expires_at" id="expires_at" class="form-control flatpickr-datetime" value="<?= set_value('expires_at'); ?>" placeholder="Kosongkan jika tidak ingin ada expired time">
            <small>Kalau dikosongkan, link hanya berhenti saat semua bucks sudah habis diklaim atau campaign ditutup manual.</small>
        </div>

        <div class="form-group">
            <label class="toggle-switch-label" for="is_active_checkbox">Status Campaign</label>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active_checkbox" name="is_active" value="1" <?= set_checkbox('is_active', '1', TRUE); ?>>
                <span class="slider"></span>
            </label>
            <small>Kalau aktif, link bisa langsung dipakai setelah campaign dibuat.</small>
        </div>

        <div class="content-note">
            <strong>Catatan:</strong> minimal total bucks harus sama dengan total penerima supaya semua orang dapat bagian minimal 1 bucks.
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Generate Link Bucks Kaget</button>
        </div>
    </form>
</div>
