<h1>Manajemen Kode Afiliasi</h1>
<p>Buat dan kelola kode referral unik untuk para afiliasi Anda.</p>
<a href="<?= base_url('admin/affiliate_codes/add'); ?>" class="btn btn-primary" style="margin-bottom: 20px;"><i class="fas fa-plus"></i> Buat Kode Baru</a>
<table class="admin-table">
    <thead>
        <tr>
            <th>Kode</th>
            <th>Pemilik</th>
            <th>Diskon Pembeli</th>
            <th>Komisi Afiliasi</th>
            <th>Jumlah Pakai</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($codes as $code): ?>
        <tr>
            <td><strong><?= html_escape($code->code); ?></strong></td>
            <td><?= html_escape($code->minecraft_username); ?></td>
            <td><?= $code->customer_discount_percentage; ?>%</td>
            <td><?= $code->affiliate_commission_percentage; ?>%</td>
            <td><?= $code->usage_count; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>