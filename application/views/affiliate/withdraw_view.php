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
            <h1>Permintaan Penarikan Dana</h1>
        </header>

        <main class="affiliate-main">
            <div class="info-box">
                <p>Saldo Anda saat ini: <strong class="green-text">Rp <?= number_format($affiliate->wallet_balance, 0, ',', '.'); ?></strong></p>
                <p>Minimal penarikan adalah <strong class="yellow-text">Rp 20.000</strong></p>
            </div>

            <div class="content-card">
                <form action="<?= base_url('affiliate/withdrawals/submit_request'); ?>" method="post">
                    <div class="form-group">
                        <label for="amount">Jumlah yang Ingin Ditarik</label>
                        <div class="input-wrapper with-prefix">
                            <span>Rp</span>
                            <input type="number" id="amount" name="amount" min="20000" max="<?= $affiliate->wallet_balance; ?>" placeholder="50000" required>
                        </div>
                    </div>
                    
                    <?php if (!empty($payout_methods)): ?>
                        <div class="form-group">
                            <label>Kirim Ke Metode Pembayaran</label>
                            <div class="radio-group">
                                <?php foreach($payout_methods as $method): ?>
                                    <div class="radio-option">
                                        <input type="radio" name="payout_method_id" value="<?= $method->id; ?>" id="method_<?= $method->id; ?>" required>
                                        <label for="method_<?= $method->id; ?>" class="radio-label">
                                            <div class="payout-method-details">
                                                <strong><?= html_escape($method->method_type); ?></strong>
                                                <span><?= html_escape($method->account_details); ?></span>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-submit full-width">Kirim Permintaan</button>
                        </div>
                    <?php else: ?>
                        <p class="warning-message">Anda harus <a href="<?= base_url('affiliate/settings'); ?>">menambahkan metode pembayaran</a> terlebih dahulu sebelum bisa melakukan penarikan.</p>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    <?php if ($this->session->flashdata('error')): ?>
        Swal.fire({ 
            icon: 'error', 
            title: 'Oops...', 
            text: '<?= $this->session->flashdata('error'); ?>',
            background: '#1F2937', color: '#F9FAFB', confirmButtonColor: '#F97316'
        });
    <?php endif; ?>
    </script>
</body>
</html>