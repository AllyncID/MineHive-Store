<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?></title>
        <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">

    <link rel="stylesheet" href="<?= base_url('assets/css/affiliate.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="affiliate-wrapper">
        <header class="affiliate-header-page">
            <a href="<?= base_url('affiliate/dashboard'); ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Pengaturan Pembayaran</h1>
        </header>

        <main class="affiliate-main">
            <div class="content-card">
                <h3>Metode Tersimpan</h3>
                <?php if (empty($payout_methods)): ?>
                    <p class="empty-state">Anda belum memiliki metode pembayaran.</p>
                <?php else: ?>
                    <div class="payout-methods-list">
                        <?php foreach($payout_methods as $method): ?>
                            <div class="payout-method-item">
                                <div class="payout-method-icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="payout-method-details">
                                    <strong><?= html_escape($method->method_type); ?></strong>
                                    <span><?= html_escape($method->account_details); ?></span>
                                </div>
                                <a href="<?= base_url('affiliate/settings/delete_method/' . $method->id); ?>" class="btn-delete-method" onclick="return confirm('Yakin ingin menghapus metode ini?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <h3>Tambah Metode Baru</h3>
                <form action="<?= base_url('affiliate/settings/add_method'); ?>" method="post">
                    <div class="form-group">
                        <label for="method_type">Tipe Metode</label>
                        <select id="method_type" name="method_type" required>
                            <option value="OVO">OVO</option>
                            <option value="GoPay">GoPay</option>
                            <option value="DANA">DANA</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="account_details">Detail Akun</label>
                        <input type="text" id="account_details" name="account_details" placeholder="Cth: 08123... atau BCA 123... a/n John" required>
                        <small>Untuk e-wallet, masukkan nomor HP. Untuk bank, masukkan Nama Bank, No. Rek, dan Atas Nama.</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Tambah Metode</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>