<div class="page-header">
    <h1><i class='bx bxs-inbox'></i> Bank Hadiah (Scratch & Win)</h1>
    <a href="<?= base_url('admin/reward_bank/add'); ?>" class="btn btn-primary"><i class='bx bx-plus'></i> Tambah Hadiah Baru</a>
</div>

<div class="content-card">
    <p>Daftar semua kemungkinan hadiah yang bisa dimenangkan dari event "Gosok Berhadiah". Hadiah ini akan Anda pilih saat membuat "Tingkatan Event".</p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama Tampilan (Dilihat User)</th>
                <th>Tipe</th>
                <th>Isi Hadiah (Value)</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rewards)): ?>
                <?php foreach ($rewards as $reward): ?>
                <tr>
                    <td><strong><?= html_escape($reward->display_name); ?></strong></td>
                    <td><span class="badge" style="background-color: <?= $reward->reward_type == 'promo' ? '#3B82F6' : '#F9B536'; ?>;"><?= ucfirst($reward->reward_type); ?></span></td>
                    <td><code class="code-snippet"><?= html_escape($reward->reward_value); ?></code></td>
                    <td><?= $reward->is_active ? '<span class="status-badge status-active">Aktif</span>' : '<span class="status-badge status-inactive">Non-Aktif</span>'; ?></td>
                    <td class="action-cell">
                        <a href="<?= base_url('admin/reward_bank/edit/' . $reward->id); ?>" class="btn btn-sm btn-warning" title="Edit"><i class='bx bxs-edit'></i></a>
                        <a href="<?= base_url('admin/reward_bank/delete/' . $reward->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus hadiah ini?')" title="Hapus"><i class='bx bxs-trash'></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Belum ada hadiah di dalam bank.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>