<h1>Dashboard<span class="filter-title">(<?= $filter_title ?>)</span></h1>
<p>Selamat Datang, <strong><?= html_escape($this->session->userdata('admin_username')); ?></strong>! Berikut adalah ringkasan toko Anda.</p>

<div class="dashboard-filters">
    <a href="<?= base_url('admin/dashboard?range=today'); ?>" class="filter-btn <?= ($current_range == 'today') ? 'active' : '' ?>">Hari Ini</a>
    <a href="<?= base_url('admin/dashboard?range=7days'); ?>" class="filter-btn <?= ($current_range == '7days') ? 'active' : '' ?>">7 Hari Terakhir</a>
    <a href="<?= base_url('admin/dashboard?range=30days'); ?>" class="filter-btn <?= ($current_range == '30days') ? 'active' : '' ?>">30 Hari Terakhir</a>
    <a href="<?= base_url('admin/dashboard?range=all'); ?>" class="filter-btn <?= ($current_range == 'all') ? 'active' : '' ?>">Semua Waktu</a>
</div>

<div class="stat-cards-container">
    <div class="stat-card blue">
        <div class="card-icon">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="card-content">
            <span class="card-title">Total Produk</span>
            <span class="card-value"><?= $total_products; ?></span>
        </div>
    </div>

    <div class="stat-card green">
        <div class="card-icon">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="card-content">
            <span class="card-title">Total Transaksi</span>
            <span class="card-value"><?= $total_transactions; ?></span>
        </div>
    </div>

    <div class="stat-card yellow">
        <div class="card-icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="card-content">
            <span class="card-title">Total Pendapatan</span>
            <span class="card-value">Rp <?= number_format($total_revenue, 0, ',', '.'); ?></span>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="main-panel">
        <h2>Grafik Penjualan (<?= $filter_title ?>)</h2>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    <div class="side-panel">
        <h2>Aktivitas Terbaru</h2>
        <div class="activity-feed">
            <?php if (!empty($recent_transactions)): ?>
                <?php foreach($recent_transactions as $trx): ?>
                    <div class="activity-item">
                        <div class="activity-icon green">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="activity-content">
                            <p>
                                <strong><?= html_escape($trx->player_username); ?></strong> baru saja membeli item senilai 
                                <strong>Rp <?= number_format($trx->grand_total, 0, ',', '.'); ?></strong>
                            </p>
                            <span class="activity-time">
                                <?= date('d M Y, H:i', strtotime($trx->created_at)); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <p>Belum ada transaksi.</p>
                        </div>
                    </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="content-card" style="margin-top: 20px;">
    <h3><i class='bx bxs-coin-stack'></i> Shortcut Bucks Kaget</h3>
    <p>Ada <strong><?= number_format((int) $active_bucks_kaget_campaigns, 0, ',', '.'); ?></strong> campaign aktif dengan total <strong><?= number_format((int) $bucks_kaget_remaining_slots, 0, ',', '.'); ?></strong> slot claim yang masih tersedia.</p>
    <div class="form-actions">
        <a href="<?= base_url('admin/bucks_kaget'); ?>" class="btn btn-primary"><i class='bx bx-right-arrow-alt'></i> Kelola Bucks Kaget</a>
        <a href="<?= base_url('admin/bucks_kaget/add'); ?>" class="btn btn-secondary"><i class='bx bx-plus'></i> Buat Campaign Baru</a>
    </div>
</div>

<div class="content-card">
    <h3><i class='bx bxs-megaphone'></i> Pengaturan Box Sale (Halaman Home)</h3>
    <form action="<?= base_url('admin/dashboard/update_settings'); ?>" method="POST">
        <div class="form-group">
            <label for="announcement_bar_enabled">Status Box Sale</label>
            <select name="announcement_bar_enabled" id="announcement_bar_enabled" class="form-control">
                <option value="1" <?= ($settings['announcement_bar_enabled'] == '1') ? 'selected' : ''; ?>>Aktif (Tampilkan)</option>
                <option value="0" <?= ($settings['announcement_bar_enabled'] == '0') ? 'selected' : ''; ?>>Tidak Aktif (Sembunyikan)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="announcement_bar_text">Judul Sale</label>
            <input type="text" name="announcement_bar_text" id="announcement_bar_text" class="form-control" value="<?= html_escape($settings['announcement_bar_text']); ?>" placeholder="Cth: Diskon 20% untuk semua rank!">
        </div>
        <div class="form-group">
            <label for="announcement_bar_link">Sub-Judul Sale</label>
            <input type="text" name="announcement_bar_link" id="announcement_bar_link" class="form-control" value="<?= html_escape($settings['announcement_bar_link']); ?>" placeholder="Cth: UP TO 70% OFF">
        </div>
        <div class="form-group">
            <label for="announcement_timer_end">Waktu Berakhir Timer (Opsional)</label>
            <input type="text" name="announcement_timer_end" id="announcement_timer_end" class="form-control flatpickr-datetime" value="<?= html_escape($settings['announcement_timer_end']); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="announcement_bg_color_1">Warna Gradient Awal (HEX)</label>
                <input type="text" name="announcement_bg_color_1" id="announcement_bg_color_1" class="form-control" value="<?= html_escape($settings['announcement_bg_color_1'] ?? '#1A823C'); ?>" placeholder="#1A823C">
            </div>
            <div class="form-group">
                <label for="announcement_bg_color_2">Warna Gradient Akhir (HEX)</label>
                <input type="text" name="announcement_bg_color_2" id="announcement_bg_color_2" class="form-control" value="<?= html_escape($settings['announcement_bg_color_2'] ?? '#1A823C'); ?>" placeholder="#1A823C">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan Pengaturan</button>
        </div>
    </form>
</div>

<!-- Card Pengaturan Promo Popup -->
<div class="content-card">
    <h3><i class='bx bxs-gift'></i> Pengaturan Event Popup (Bonus Top Up)</h3>
    
    <form action="<?= base_url('admin/dashboard/update_promo_popup'); ?>" method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
            <label for="is_enabled">Status Popup Event</label>
            <select name="is_enabled" id="is_enabled" class="form-control">
                <option value="1" <?= (isset($promo_popup['is_enabled']) && $promo_popup['is_enabled'] == '1') ? 'selected' : ''; ?>>Aktif (Tampilkan)</option>
                <option value="0" <?= (isset($promo_popup['is_enabled']) && $promo_popup['is_enabled'] == '0') ? 'selected' : ''; ?>>Tidak Aktif (Sembunyikan)</option>
            </select>
            <small>Popup akan muncul sekali per sesi (browser close/reopen) atau setelah 1 jam.</small>
        </div>

        <div class="form-group">
            <label for="popup_image">Gambar Header (Opsional)</label>
            <input type="file" name="popup_image" id="popup_image" class="form-control" accept="image/*">
            <?php if (!empty($promo_popup['image_url'])): ?>
                <div class="dashboard-current-image">
                    <p>Gambar Saat Ini:</p>
                    <img src="<?= html_escape($promo_popup['image_url']); ?>" alt="Current Image" class="dashboard-image-preview">
                    <label class="dashboard-delete-image-label">
                        <input type="checkbox" name="delete_image" value="1"> Hapus gambar ini?
                    </label>
                </div>
            <?php endif; ?>
            <small>Gambar akan tampil di bagian paling atas popup.</small>
        </div>

        <div class="form-group">
            <label for="title">Judul Popup</label>
            <input type="text" name="title" id="title" class="form-control" value="<?= isset($promo_popup['title']) ? html_escape($promo_popup['title']) : ''; ?>" placeholder="Cth: 🎁 BONUS TOP UP EVENT!">
        </div>

        <div class="form-group">
            <label for="description">Deskripsi (Body Text)</label>
            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Jelaskan detail event bonus di sini..."><?= isset($promo_popup['description']) ? html_escape($promo_popup['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label>Daftar Tier Bonus</label>
            <p class="dashboard-tier-helper">Isi bagian "Judul" dengan Range (Misal: 100K - 149K) dan "Deskripsi" dengan hadiahnya.</p>
            
            <div class="dashboard-promo-tier-list">
                <?php 
                for ($i = 1; $i <= 5; $i++): 
                    // Pecah data yang tersimpan (format: Judul|Deskripsi)
                    $tier_data = isset($promo_popup['promo_tier_' . $i]) ? explode('|', $promo_popup['promo_tier_' . $i]) : [];
                    $title = $tier_data[0] ?? '';
                    $desc = $tier_data[1] ?? '';
                ?>
                <div class="dashboard-promo-tier-card">
                    <strong>Tier <?= $i; ?></strong>
                    <div class="dashboard-promo-tier-grid">
                        <div>
                            <label>Judul (Range)</label>
                            <input type="text" name="promo_tier_<?= $i; ?>_title" class="form-control" value="<?= html_escape($title); ?>" placeholder="Cth: 50K - 99K">
                        </div>
                        <div>
                            <label>Deskripsi Hadiah</label>
                            <input type="text" name="promo_tier_<?= $i; ?>_desc" class="form-control" value="<?= html_escape($desc); ?>" placeholder="Cth: 1x Christmas Key">
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="button_text">Teks Tombol Aksi</label>
                <input type="text" name="button_text" id="button_text" class="form-control" value="<?= isset($promo_popup['button_text']) ? html_escape($promo_popup['button_text']) : 'View Bonus Details'; ?>" placeholder="View Bonus Details">
            </div>
            <div class="form-group">
                <label for="button_link">Link Tombol (Opsional)</label>
                <input type="text" name="button_link" id="button_link" class="form-control" value="<?= isset($promo_popup['button_link']) ? html_escape($promo_popup['button_link']) : '#'; ?>" placeholder="https://... atau #section-id">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan Pengaturan Popup</button>
        </div>

    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= $chart_labels; ?>,
                    datasets: [{
                        label: 'Pendapatan Harian (Rp)',
                        data: <?= $chart_data; ?>,
                        fill: true,
                        backgroundColor: 'rgba(249, 181, 54, 0.2)',
                        borderColor: 'rgba(249, 181, 54, 1)',
                        pointBackgroundColor: 'rgba(249, 181, 54, 1)',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 7,
                        tension: 0.3
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); }
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    });
</script>
