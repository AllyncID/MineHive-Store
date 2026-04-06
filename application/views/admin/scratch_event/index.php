<div class="page-header">
    <h1><i class='bx bxs-magic-wand'></i> Pengaturan Event Gosok Berhadiah</h1>
    <a href="<?= base_url('admin/scratch_event/add_tier'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Tambah Tingkatan Baru</a>
</div>

<div class="content-card">
    <p>Atur event "Gosok Berhadiah" di sini. Customer akan mendapatkan 1 hadiah acak dari "Pool Hadiah" berdasarkan "Tingkatan Belanja" mereka.</p>
    
    <form action="<?= base_url('admin/scratch_event/update_settings'); ?>" method="POST">
        <div class="form-group">
            <label for="scratch_event_title">Judul Event (Dilihat User)</label>
            <input type="text" name="scratch_event_title" id="scratch_event_title" class="form-control" value="<?= html_escape($settings['scratch_event_title'] ?? 'Black Friday Hadiah!'); ?>">
        </div>
        
        <div class="form-group">
            <label class="toggle-switch-label" for="scratch_event_enabled_checkbox">Status Event</label>
            <label class="toggle-switch">
                <input type="checkbox" id="scratch_event_enabled_checkbox" name="scratch_event_enabled" value="1" <?= ($settings['scratch_event_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
            <small>Jika "Aktif", setiap pembelian yang memenuhi syarat akan memicu hadiah gosok.</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan Pengaturan Event</button>
        </div>
    </form>
</div>

<div class="content-card" style="margin-top: 20px;">
    <h3>Daftar Tingkatan (Tiers)</h3>
    <p>Sistem akan mengecek dari tingkatan tertinggi (termahal) dulu. Pastikan range tidak tumpang tindih.</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Judul Tier</th>
                <th>Range Belanja</th>
                <th>Pool Hadiah (Random)</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tiers)): ?>
                <?php foreach ($tiers as $tier): ?>
                <tr>
                    <td><strong><?= html_escape($tier->title); ?></strong></td>
                    <td>
                        Rp <?= number_format($tier->min_amount, 0, ',', '.'); ?>
                        - 
                        <?= $tier->max_amount ? 'Rp ' . number_format($tier->max_amount, 0, ',', '.') : 'ke atas'; ?>
                    </td>
                    <td>
                        <?php if (empty($tier->rewards)): ?>
                            <span style="color: #888;">(Belum ada hadiah)</span>
                        <?php else: ?>
                            <ul style="margin: 0; padding-left: 15px;">
                                <?php foreach ($tier->rewards as $reward): ?>
                                    <li style="font-size: 0.85rem;">
                                        <?= html_escape($reward->display_name); ?>
                                        (<?= $reward->reward_type == 'promo' ? '<i class="bx bxs-purchase-tag" style="color: #3B82F6;" title="Kode Promo"></i>' : '<i class="bx bxs-terminal" style="color: #F9B536;" title="Command"></i>'; ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td><?= $tier->is_active ? '<span class="status-badge status-active">Aktif</span>' : '<span class="status-badge status-inactive">Non-Aktif</span>'; ?></td>
                    <td class="action-cell">
                        <a href="<?= base_url('admin/scratch_event/edit_tier/' . $tier->id); ?>" class="btn btn-sm btn-warning" title="Edit"><i class='bx bxs-edit'></i></a>
                        <a href="<?= base_url('admin/scratch_event/delete_tier/' . $tier->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus tier ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Belum ada tingkatan event yang dibuat.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>