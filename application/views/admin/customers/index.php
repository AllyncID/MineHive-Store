<h1><i class='bx bxs-trophy' style="color: #FFD700;"></i> Top Spenders <span class="filter-title">(<?= $filter_title ?>)</span></h1>
<p>Lihat siapa saja pelanggan setia yang paling banyak berbelanja di toko Anda.</p>

<div class="dashboard-filters" style="margin-bottom: 25px; flex-wrap: wrap;">
    <!-- Lifetime Filters -->
    <a href="<?= base_url('admin/customers?filter=lifetime_desc'); ?>" class="filter-btn <?= ($current_filter == 'lifetime_desc') ? 'active' : '' ?>">
        <i class='bx bxs-crown'></i> Top Lifetime
    </a>
    <a href="<?= base_url('admin/customers?filter=lifetime_asc'); ?>" class="filter-btn <?= ($current_filter == 'lifetime_asc') ? 'active' : '' ?>">
        <i class='bx bx-sort-up'></i> Lifetime (Terendah)
    </a>
    
    <!-- Separator -->
    <span style="border-right: 1px solid var(--border-color); margin: 0 5px;"></span>

    <!-- Monthly Filters -->
    <a href="<?= base_url('admin/customers?filter=month_desc'); ?>" class="filter-btn <?= ($current_filter == 'month_desc') ? 'active' : '' ?>">
        <i class='bx bxs-calendar-star'></i> Top Bulan Ini
    </a>
    <a href="<?= base_url('admin/customers?filter=month_asc'); ?>" class="filter-btn <?= ($current_filter == 'month_asc') ? 'active' : '' ?>">
        <i class='bx bx-sort-up'></i> Bulan Ini (Terendah)
    </a>
</div>

<div class="content-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 60px; text-align: center;">Rank</th>
                <th>Pelanggan</th>
                <th>Total Belanja</th>
                <th>Jumlah Transaksi</th>
                <th>Rata-rata / Transaksi</th>
                <th>Transaksi Terakhir</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($customers)): ?>
                <?php $rank = 1; foreach ($customers as $customer): ?>
                    <?php 
                        // Logika Avatar (Bedrock vs Java)
                        $username = $customer->player_username;
                        $avatar_url = 'https://minotar.net/avatar/' . rawurlencode($username) . '/32';
                        if (strpos($username, '.') === 0) {
                            $avatar_url = 'https://minotar.net/avatar/Steve/32';
                        }
                        
                        // Highlight Top 3 untuk mode DESC
                        $rank_class = '';
                        if (strpos($current_filter, 'desc') !== false) {
                            if ($rank == 1) $rank_class = 'rank-1';
                            elseif ($rank == 2) $rank_class = 'rank-2';
                            elseif ($rank == 3) $rank_class = 'rank-3';
                        }

                        // Hitung rata-rata
                        $avg_spent = ($customer->total_transactions > 0) ? ($customer->total_spent / $customer->total_transactions) : 0;
                    ?>
                    <tr class="<?= $rank_class; ?>">
                        <td style="text-align: center;">
                            <?php if ($rank == 1 && strpos($current_filter, 'desc') !== false): ?>
                                <i class='bx bxs-crown' style="color: #FFD700; font-size: 1.2rem;"></i>
                            <?php elseif ($rank == 2 && strpos($current_filter, 'desc') !== false): ?>
                                <i class='bx bxs-medal' style="color: #C0C0C0; font-size: 1.2rem;"></i>
                            <?php elseif ($rank == 3 && strpos($current_filter, 'desc') !== false): ?>
                                <i class='bx bxs-medal' style="color: #CD7F32; font-size: 1.2rem;"></i>
                            <?php else: ?>
                                #<?= $rank; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?= $avatar_url; ?>" alt="Avatar" style="border-radius: 4px;">
                                <strong><?= html_escape($username); ?></strong>
                            </div>
                        </td>
                        <td>
                            <span style="color: #ECCA01; font-weight: 700;">
                                Rp <?= number_format($customer->total_spent, 0, ',', '.'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background-color: #4B3420;">
                                <?= number_format($customer->total_transactions, 0, ',', '.'); ?>x
                            </span>
                        </td>
                        <td style="color: var(--text-secondary);">
                            Rp <?= number_format($avg_spent, 0, ',', '.'); ?>
                        </td>
                        <td>
                            <?= date('d M Y', strtotime($customer->last_transaction)); ?>
                            <small style="color: var(--text-secondary); display: block;">
                                <?= date('H:i', strtotime($customer->last_transaction)); ?>
                            </small>
                        </td>
                    </tr>
                <?php $rank++; endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px;">
                        Belum ada data transaksi untuk periode ini.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    /* Styling khusus untuk Top Rank */
    tr.rank-1 { background-color: rgba(255, 215, 0, 0.1); }
    tr.rank-2 { background-color: rgba(192, 192, 192, 0.05); }
    tr.rank-3 { background-color: rgba(205, 127, 50, 0.05); }
    
    tr.rank-1 td { border-top: 1px solid rgba(255, 215, 0, 0.2); border-bottom: 1px solid rgba(255, 215, 0, 0.2); }
</style>