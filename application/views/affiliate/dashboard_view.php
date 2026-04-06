<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?></title>
        <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">

    <link rel="stylesheet" href="<?= base_url('assets/css/affiliate.css?v=1.0.3'); ?>"> <!-- Versi dinaikkan -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="affiliate-wrapper">
        <header class="affiliate-header">
            <div class="header-left">
                <img src="https://minotar.net/avatar/<?= html_escape($affiliate->minecraft_username); ?>/40" alt="Avatar">
                <span>Welcome, 
                    <strong>
                        <?= html_escape($affiliate->minecraft_username); ?>
                        <!-- TAMPILKAN BADGE DI SINI -->
                        <span class="aff-badge badge-<?= html_escape($affiliate->badge); ?>">
                            <?= html_escape($affiliate->badge_name); ?>
                        </span>
                    </strong>
                </span>
            </div>
            <div class="header-right">
                <a href="<?= base_url('affiliate/auth/logout'); ?>" class="btn-logout-affiliate">
                    Logout
                </a>
            </div>
        </header>

        <main class="affiliate-main">
            <div class="balance-card">
                <span class="balance-title">SALDO TERSEDIA</span>
                <span class="balance-amount">Rp <?= number_format($affiliate->wallet_balance, 0, ',', '.'); ?></span>
                <span class="balance-subtitle">Dapat ditarik kapan saja</span>
            </div>

            <!-- KARTU MISI (VERSI 2 DENGAN 2 GOAL) -->
            <div class="mission-card">
                <h3>Misi Afiliasi</h3>
                <div class="mission-status">
                    <div class="status-left">
                        <span class="status-title">Badge Anda Saat Ini</span>
                        <span class="status-value badge-<?= html_escape($affiliate->badge); ?>"><?= html_escape($affiliate->badge_name); ?></span>
                        <span class="status-subtitle">Komisi: <?= $affiliate->commission_rate * 100; ?>%</span>
                    </div>
                    <div class="status-right">
                        <span class="status-title">Total Penjualan</span>
                        <span class="status-value">Rp <?= number_format($affiliate->total_sales, 0, ',', '.'); ?></span>
                        <span class="status-subtitle"><?= number_format($affiliate->total_transactions, 0, ',', '.'); ?> Transaksi</span>
                    </div>
                </div>
                
                <?php if($affiliate->next_badge_name != 'Max Level'): ?>
                <div class="mission-progress">
                    
                    <!-- Progress Bar 1: Total Penjualan (Rp) -->
                    <div class="progress-block">
                        <div class="progress-header">
                            <span>Progres Total Penjualan (Rp)</span>
                            <span>Rp <?= number_format($affiliate->total_sales, 0, ',', '.'); ?> / Rp <?= number_format($affiliate->next_badge_goal_sales, 0, ',', '.'); ?></span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $affiliate->progress_percent_sales; ?>%;"></div>
                        </div>
                    </div>

                    <!-- Progress Bar 2: Total Transaksi (Qty) -->
                    <div class="progress-block">
                        <div class="progress-header">
                            <span>Progres Total Transaksi</span>
                            <span><?= number_format($affiliate->total_transactions, 0, ',', '.'); ?> / <?= number_format($affiliate->next_badge_goal_transactions, 0, ',', '.'); ?> Transaksi</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $affiliate->progress_percent_transactions; ?>%;"></div>
                        </div>
                    </div>
                    
                    <span class="progress-footer">
                        Capai kedua target untuk naik ke badge <strong><?= html_escape($affiliate->next_badge_name); ?></strong>.
                    </span>
                </div>
                <?php else: ?>
                <div class="mission-progress-max">
                    <i class="fas fa-crown"></i>
                    <span>Anda telah mencapai badge tertinggi!</span>
                </div>
                <?php endif; ?>
            </div>
            <!-- AKHIR KARTU MISI -->


            <div class="action-buttons">
                <a href="<?= base_url('affiliate/withdrawals'); ?>" class="action-btn primary" <?= ($affiliate->wallet_balance < 20000) ? 'disabled' : ''; ?>>
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Tarik Dana</span>
                </a>
                <a href="<?= base_url('affiliate/settings'); ?>" class="action-btn">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
                <a href="<?= base_url('affiliate/history'); ?>" class="action-btn">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span>
                </a>
            </div>
                <div class="content-card">
                    <h3>Ganti Password</h3>
                
                    <?php 
                        // Menampilkan semua pesan error validasi dalam satu kotak
                        $validation_errors = validation_errors('<p class="error-item">', '</p>');
                        if (!empty($validation_errors)) {
                            // Gunakan class .error-message yang sudah ada di CSS login Anda
                            echo '<div class="error-message" style="text-align: left;">' . $validation_errors . '</div>';
                        }
                    ?>
                    
                    <form action="<?= base_url('affiliate/dashboard/change_password'); ?>" method="POST">
                        
                        <div class="form-group">
                            <label for="old_password">Password Lama</label>
                            <div class="input-wrapper">
                                <i class="fas fa-key"></i>
                                <input type="password" name="old_password" id="old_password" placeholder="Masukkan password Anda saat ini" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="new_password" id="new_password" placeholder="Minimal 8 karakter" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Ketik ulang password baru" required>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit full-width">
                                <span>Ganti Password</span>
                            </button>
                        </div>
                
                    </form>
                </div>

            <div class="activity-section">
                <h3>Aktivitas Komisi Terbaru</h3>
                <div class="activity-list">
                    
                    <?php if (empty($activities)): ?>
                        <p>Belum ada aktivitas terbaru.</p>
                    <?php else: ?>
                        <?php foreach($activities as $activity): ?>
                            <div class="activity-item">
                                
                                <?php if($activity->type == 'commission'): ?>
                                    <div class="activity-icon green"><i class="fas fa-plus"></i></div>
                                <?php else: // withdrawal ?>
                                    <div class="activity-icon red"><i class="fas fa-arrow-up"></i></div>
                                <?php endif; ?>

                                <div class="activity-details">
                                    <span><?= html_escape($activity->description); ?></span>

                                    <?php if($activity->amount >= 0): ?>
                                        <span class="activity-amount green">+ Rp <?= number_format($activity->amount, 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="activity-amount red">- Rp <?= number_format(abs($activity->amount), 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                            <span class="activity-time">
                                                <?= timespan($activity->created_at_unix, time(), 1); ?> ago
                                            </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if ($this->session->flashdata('success')): ?>
    Swal.fire({
        // Konfigurasi dasar
        title: 'Berhasil!',
        text: '<?= $this->session->flashdata('success'); ?>',

        // --- Perubahan Utama: Gunakan Class dari CSS Anda ---
         customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text'},

        
        // Hapus warna latar belakang inline agar diatur penuh oleh CSS
        background: 'transparent',
        
        // Atur backdrop agar sesuai tema
        backdrop: `rgba(27, 22, 13, 0.85)`,
        
        // Konfigurasi tambahan untuk tampilan yang lebih baik
        showConfirmButton: false,
        focusConfirm: false
    });
<?php endif; ?>

<?php if ($this->session->flashdata('error')): ?>
    Swal.fire({
        // Konfigurasi dasar
        title: 'Oops..',
        text: '<?= $this->session->flashdata('error'); ?>',

        // --- Perubahan Utama: Gunakan Class dari CSS Anda ---
         customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text'},

        
        // Hapus warna latar belakang inline agar diatur penuh oleh CSS
        background: 'transparent',
        
        // Atur backdrop agar sesuai tema
        backdrop: `rgba(27, 22, 13, 0.85)`,
        
        // Konfigurasi tambahan untuk tampilan yang lebih baik
        showConfirmButton: false,
        focusConfirm: false
    });
<?php endif; ?>
</script>
</body>
</html>

