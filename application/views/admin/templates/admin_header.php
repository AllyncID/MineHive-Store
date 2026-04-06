<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? html_escape($title) : 'Admin Panel'; ?> | Mine Hive</title>
    
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css'); ?>">
    
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/css/tom-select.bootstrap5.min.css" xintegrity="sha512-w7Qns0H5MydTEn1rJ3VLJ7wN/HBEJ423T7XQz/t3YdOaA2tcrQSPcbwE/aeaA53Mmcg42AJEaU3PguL21dYdQA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/js/tom-select.complete.min.js" xintegrity="sha512-9CVW3wZ0B_HnU2BqStifwZoeCda2vH1SMMctLXXT2HwL5J6ye2w2vGDXyv204QoP2HwnKbgBzvK5MvPZ+g0gjg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">

<button type="button" class="admin-mobile-toggle" id="adminSidebarToggle" aria-controls="adminSidebar" aria-expanded="false" aria-label="Buka navigasi admin">
    <i class='bx bx-menu'></i>
</button>

<div class="admin-sidebar-overlay" id="adminSidebarOverlay" hidden></div>

<div class="admin-wrapper">
    <div class="sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
            <button type="button" class="sidebar-close" id="adminSidebarClose" aria-label="Tutup navigasi admin">
                <i class='bx bx-x'></i>
            </button>
        </div>
        
<ul class="sidebar-nav">
    <?php 
        $active_segment = $this->uri->segment(2, 'dashboard'); 
        $active_segment_3 = $this->uri->segment(3, null); 
    ?>

    <li class="<?= ($active_segment == 'dashboard') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/dashboard'); ?>"><i class='bx bxs-dashboard'></i> Dashboard</a>
    </li>
    
    <li class="<?= ($active_segment == 'customers') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/customers'); ?>"><i class='bx bxs-trophy'></i> Top Spenders</a>
    </li>

    <li class="<?= ($active_segment == 'products') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/products'); ?>"><i class='bx bxs-package'></i> Manage Products</a>
    </li>
    <li class="<?= ($active_segment == 'promo') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/promo'); ?>"><i class='bx bxs-purchase-tag'></i> Kode Promo</a>
    </li>
    <li class="<?= ($active_segment == 'discounts') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/discounts'); ?>"><i class='bx bxs-discount'></i> Manajemen Diskon</a>
    </li>
    <li class="<?= ($active_segment == 'cart_discounts') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/cart_discounts'); ?>"><i class='bx bxs-cart-add'></i> Diskon Keranjang</a>
    </li>
    <li class="<?= ($active_segment == 'flash_sales' && $active_segment_3 == null) ? 'active' : '' ?>">
        <a href="<?= base_url('admin/flash_sales'); ?>"><i class='bx bxs-zap'></i> Flash Sales Manual</a>
    </li>
    <!-- Link Auto FS -->
    <li class="<?= ($active_segment == 'flash_sales' && $active_segment_3 == 'settings') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/flash_sales/settings'); ?>"><i class='bx bxs-bot'></i> Pengaturan Auto FS</a>
    </li>

    <!-- [MENU BARU] FEATURED PRODUCTS -->
    <li class="<?= ($active_segment == 'featured') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/featured'); ?>"><i class='bx bxs-star'></i> Featured Products (Hot)</a>
    </li>
    <!-- [AKHIR MENU BARU] -->

    <li class="<?= ($active_segment == 'bonus') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/bonus'); ?>"><i class='bx bxs-gift'></i> Bonus Top Up</a>
    </li>

    <li class="<?= ($active_segment == 'bucks_kaget') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/bucks_kaget'); ?>"><i class='bx bxs-coin-stack'></i> Bucks Kaget</a>
    </li>

    <li class="<?= ($active_segment == 'lucky_spin') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/lucky_spin'); ?>"><i class='bx bxs-dice-5'></i> Lucky Spin</a>
    </li>
    
    <li class="<?= ($active_segment == 'first_bonus') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/first_bonus'); ?>"><i class='bx bxs-star'></i> Bonus Pembelian Pertama</a>
    </li>

    <li class="<?= ($active_segment == 'reward_bank') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/reward_bank'); ?>"><i class='bx bxs-inbox'></i> Bank Hadiah (Event)</a>
    </li>
    <li class="<?= ($active_segment == 'scratch_event') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/scratch_event'); ?>"><i class='bx bxs-magic-wand'></i> Event Gosok Hadiah</a>
    </li>

    <li class="<?= ($active_segment == 'transactions') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/transactions'); ?>"><i class='bx bx-history'></i> Riwayat Transaksi</a>
    </li>
    <hr>
    <li class="<?= ($active_segment == 'affiliates') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/affiliates'); ?>"><i class='bx bxs-user-account'></i> Manajemen Afiliasi</a>
    </li>
    <li class="<?= ($active_segment == 'affiliate_codes') ? 'active' : '' ?>">
        <a href="<?= base_url('admin/affiliate_codes'); ?>"><i class='bx bxs-coupon'></i> Kode Afiliasi</a>
    </li>
</ul>

<div class="sidebar-footer">
    <a href="<?= base_url('admin/auth/logout'); ?>" class="btn-logout"><i class='bx bx-log-out'></i> Logout</a>
</div>
    </div>
    <div class="main-content">
