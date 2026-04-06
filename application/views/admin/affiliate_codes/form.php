<h1><i class="fas fa-pencil-alt"></i> Buat Kode Afiliasi Baru</h1>
<div class="form-container">
    <form action="<?= base_url('admin/affiliate_codes/add'); ?>" method="post">
        <div class="form-group">
            <label for="affiliate_id">Pilih Afiliasi (Pemilik Kode)</label>
            <select name="affiliate_id" required>
                <?php foreach($affiliates as $affiliate): ?>
                <option value="<?= $affiliate->id; ?>"><?= html_escape($affiliate->minecraft_username); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="code">Kode Referral Unik (Cth: MINEHIVE20)</label>
            <input type="text" name="code" required>
        </div>
        <div class="form-group">
            <label for="customer_discount_percentage">Diskon untuk Pembeli (%)</label>
            <input type="number" name="customer_discount_percentage" required placeholder="Cth: 10 untuk 10%">
        </div>
         <div class="form-group">
            <label for="affiliate_commission_percentage">Komisi untuk Afiliasi (%)</label>
            <input type="number" name="affiliate_commission_percentage" required placeholder="Cth: 3 untuk 3%">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Kode</button>
        </div>
    </form>
</div>