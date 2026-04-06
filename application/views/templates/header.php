<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        $page_title = isset($title) ? (string) $title : 'MineHive';
        $og_title = !empty($meta_title) ? (string) $meta_title : $page_title;
        $og_desc = !empty($meta_description)
            ? (string) $meta_description
            : 'Toko resmi MineHive. Temukan berbagai rank, currency, dan item eksklusif untuk meningkatkan pengalaman bermain Anda di server kami.';
        $og_image = !empty($meta_image) ? (string) $meta_image : base_url('assets/images/opengraph_banner.jpg');
        $theme_color = !empty($theme_color) ? (string) $theme_color : '#ffbd00';
    ?>
    <meta name="description" content="<?= html_escape($og_desc); ?>">
    <meta name="theme-color" content="<?= html_escape($theme_color); ?>">
    
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= html_escape(current_url()); ?>">
    <meta property="og:title" content="<?= html_escape($og_title); ?>">
    <meta property="og:site_name" content="MineHive Store">
    <meta property="og:description" content="<?= html_escape($og_desc); ?>">
    <meta property="og:image" content="<?= html_escape($og_image); ?>">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= html_escape(current_url()); ?>">
    <meta name="twitter:title" content="<?= html_escape($og_title); ?>">
    <meta name="twitter:description" content="<?= html_escape($og_desc); ?>">
    <meta name="twitter:image" content="<?= html_escape($og_image); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate" />

    <title><?= html_escape($page_title); ?></title>

    <link rel="stylesheet" href="<?= base_url('assets/css/style.css?v=1.0.98'); ?>"> <!-- Versi dinaikkan -->
    <?php if (!empty($page_stylesheets) && is_array($page_stylesheets)): ?>
        <?php foreach ($page_stylesheets as $stylesheet_url): ?>
            <link rel="stylesheet" href="<?= html_escape((string) $stylesheet_url); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Rubik+Mono+One&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&display=swap" rel="stylesheet">
        
    <!-- [PENAMBAHAN FONT] Font Rubik One ditambahkan di sini -->
    <link href="https://fonts.googleapis.com/css2?family=Rubik+One&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    

</head>
<body class="<?= html_escape(trim((string) ($body_class ?? ''))); ?>" data-is-logged-in="<?= $this->session->userdata('is_logged_in') ? 'true' : 'false'; ?>">
    
    <!-- PENGUMUMAN LAMA DIHAPUS DARI SINI -->

    <!-- [PERUBAHAN BESAR] Server Stats Bar -->
    <?php
        // [DIHAPUS] Semua logika PHP untuk cURL, cache, dan get_instance()
        // dihapus dari sini. Akan digantikan oleh JavaScript.
    ?>
    <div class="server-stats-bar">
        <div class="container stats-bar-container">
            <div class="stat-item">
                <i class="fas fa-user"></i>
                <!-- [DIUBAH] Angka diganti dengan span ID dan placeholder "..." -->
                <span><span id="mc-player-count">0</span> Playing</span>
            </div>
            <div class="stat-item">
                <i class="fab fa-discord"></i>
                <!-- [DIUBAH] Angka diganti dengan span ID dan placeholder "..." -->
                <span><span id="discord-online-count">0</span> Online</span>
            </div>
        </div>
    </div>
    <!-- [AKHIR] Server Stats Bar -->


    <header class="main-branding-header">
        <div class="container">
            <a href="<?= base_url(); ?>">
                <img src="<?= base_url('assets/images/logo.png'); ?>" alt="Server Logo" class="main-logo">
            </a>
        </div>
    </header>

    <nav class="navbar">
        <div class="container navbar-container">
                <div class="navbar-left">
                    <?php if ($this->session->userdata('is_logged_in')): ?>
                        <?php
                            $session_username = $this->session->userdata('username');
                
                            // Cek apakah ini akun Bedrock (diawali dengan titik)
                            if (strpos($session_username, '.') === 0) {
                                // Untuk Bedrock, tetap tampilkan helm default (Steve)
                                $avatar_url = 'https://crafthead.net/avatar/c06f89064c8a49119c29ea1dbd1aab82/64';
                            } else {
                                // Untuk Java, gunakan helm pemain dari Crafthead
                                // rawurlencode() lebih aman untuk nama di dalam URL
                                $avatar_url = 'https://crafthead.net/helm/' . rawurlencode($session_username) . '/32';
                            }
                            
                            // Ambil badge dari session
                            $affiliate_badge = $this->session->userdata('affiliate_badge');
                        ?>
                
                        <a href="<?= base_url('transaction'); ?>" class="navbar-profile">
                            <img src="<?= $avatar_url; ?>" 
                                 alt="Player Avatar" 
                                 onerror="this.onerror=null; this.src='<?= base_url('assets/images/default_avatar.png'); ?>'">
                            <span>Logged in as<br>
                                <strong>
                                    <?= html_escape($session_username); ?>
                                </strong>
                                
                                <!-- BADGE DIPINDAH KELUAR DARI <strong> -->
                                <!-- AKHIR BADGE -->
                            </span>
                        </a>
                
                    <?php else: ?>
                
                        <div class="navbar-profile">
                            <img src="https://crafthead.net/avatar/c06f89064c8a49119c29ea1dbd1aab82/64" alt="Guest Avatar"> <span>Logged in as<br><strong>Guest</strong></span>
                        </div>
                
                    <?php endif; ?>
                </div>
            <div class="navbar-right">
                <ul class="nav-links">
                    <?php if (!$this->session->userdata('is_logged_in')): ?>
                        <li><a id="loginBtn" class="btn btn-secondary">Login</a></li>
                    <?php else: ?>
                         <li><a href="<?= base_url('logout'); ?>" class="btn btn-secondary">Logout</a></li>
                    <?php endif; ?>
                    <li><a href="<?= base_url('cart'); ?>" class="btn btn-primary btn-view-cart"><span class="view-text">View </span>Cart (<span id="navCartCount"><?= count($this->session->userdata('cart')['items'] ?? []); ?></span>)</a></li>                </ul>
            </div>
        </div>
    </nav>

    <!-- [BARU] Server Stats Bar (TELAH DIPINDAN KE ATAS) -->

    <div class="main-page-content">
