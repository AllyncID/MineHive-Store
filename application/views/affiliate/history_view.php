<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/affiliate.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="affiliate-wrapper">
        <header class="affiliate-header-page">
            <a href="<?= base_url('affiliate/dashboard'); ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Riwayat Transaksi</h1>
        </header>

        <main class="affiliate-main">
            <div class="content-card">
                <div class="table-responsive">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">Tidak ada riwayat transaksi.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($history as $item): ?>
                                        <tr>
                                            <td data-label="Tanggal"><?= date('d M Y, H:i', strtotime($item->created_at)); ?></td>
                                            <td data-label="Deskripsi"><?= html_escape($item->description); ?></td>
                                            <td data-label="Jumlah">
                                                <?php if($item->amount >= 0): ?>
                                                    <span class="amount-positive">+ Rp <?= number_format($item->amount, 0, ',', '.'); ?></span>
                                                <?php else: ?>
                                                    <span class="amount-negative">- Rp <?= number_format(abs($item->amount), 0, ',', '.'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td data-label="Status">
                                                <span class="status-badge status-<?= strtolower(html_escape($item->type)); ?>">
                                                    <?php 
                                                        // Mengubah 'type' menjadi teks yang lebih ramah
                                                        if ($item->type == 'commission') {
                                                            echo 'Komisi';
                                                        } elseif ($item->type == 'withdrawal') {
                                                            echo 'Penarikan';
                                                        } else {
                                                            echo ucfirst(html_escape($item->type));
                                                        }
                                                    ?>
                                                </span>
                                            </td>
                                            </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>