<?php
$page_title = isset($title) ? (string) $title : 'Lucky Spin';
if (stripos($page_title, 'MineHive') === false) {
    $page_title .= ' | MineHive';
}
$page_description = !empty($meta_description)
    ? (string) $meta_description
    : 'Ikut Lucky Spin MineHive, masukkan nickname Minecraft, lalu putar hadiah random selama event masih aktif.';
$page_image = base_url('assets/images/opengraph_banner.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#c98c42">
    <meta name="description" content="<?= html_escape($page_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= html_escape(current_url()); ?>">
    <meta property="og:title" content="<?= html_escape($page_title); ?>">
    <meta property="og:site_name" content="MineHive">
    <meta property="og:description" content="<?= html_escape($page_description); ?>">
    <meta property="og:image" content="<?= html_escape($page_image); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= html_escape(current_url()); ?>">
    <meta name="twitter:title" content="<?= html_escape($page_title); ?>">
    <meta name="twitter:description" content="<?= html_escape($page_description); ?>">
    <meta name="twitter:image" content="<?= html_escape($page_image); ?>">
    <title><?= html_escape($page_title); ?></title>

    <link rel="stylesheet" href="<?= base_url('assets/css/lucky-spin.css?v=' . (file_exists('assets/css/lucky-spin.css') ? filemtime('assets/css/lucky-spin.css') : time())); ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="lucky-spin-page">
    <div class="lucky-spin-shell">
        <div class="lucky-spin-card">
            <div class="lucky-spin-copy">
                <span class="lucky-spin-label">LUCKY SPIN</span>
                <h1><?= html_escape($campaign->name); ?></h1>
                <p class="lucky-spin-description"><?= html_escape($campaign->status_message); ?></p>

                <div class="lucky-spin-stats">
                    <div class="lucky-spin-stat">
                        <span>Sisa Kuota</span>
                        <strong id="remainingPlayerSlotsText"><?= number_format((int) $campaign->remaining_player_slots, 0, ',', '.'); ?> orang</strong>
                    </div>
                    <div class="lucky-spin-stat">
                        <span>Spin / Player</span>
                        <strong><?= number_format((int) $campaign->max_spins_per_player, 0, ',', '.'); ?>x</strong>
                    </div>
                    <div class="lucky-spin-stat">
                        <span>Hadiah Aktif</span>
                        <strong id="activeRewardCountText"><?= number_format((int) $campaign->available_rewards, 0, ',', '.'); ?></strong>
                    </div>
                </div>

                <?php if ($campaign->status === 'active'): ?>
                    <button type="button" class="lucky-spin-trigger" id="openSpinModalBtn">Masukkan Nickname & Mulai Spin</button>
                <?php else: ?>
                    <button type="button" class="lucky-spin-trigger is-disabled" disabled>Tidak Bisa Spin</button>
                <?php endif; ?>

                <div class="lucky-spin-note">
                    <?php if (!empty($campaign->expires_at)): ?>
                        <span>Expired pada <?= date('d M Y H:i', strtotime($campaign->expires_at)); ?></span>
                    <?php else: ?>
                        <span>Link ini aktif sampai ditutup admin atau semua hadiah habis.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lucky-wheel-panel">
                <div class="lucky-wheel-frame">
                    <div class="lucky-wheel-pointer"></div>
                    <div class="lucky-wheel" id="luckySpinWheel">
                        <div class="lucky-wheel-labels" id="luckySpinLabels"></div>
                        <div class="lucky-wheel-center">SPIN</div>
                    </div>
                </div>

                <div class="lucky-spin-result-card" id="luckySpinResultCard">
                    <span>Hadiah Terakhir</span>
                    <strong id="luckySpinResultLabel">Belum ada spin</strong>
                    <small id="luckySpinResultMeta">Masukkan nickname lalu mulai spin untuk lihat hasilnya.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="lucky-spin-modal-overlay" id="luckySpinModal">
        <div class="lucky-spin-modal">
            <button type="button" class="lucky-spin-modal-close" id="closeLuckySpinModal" aria-label="Tutup popup">
                <i class="fas fa-times"></i>
            </button>

            <div class="lucky-spin-modal-header">
                <h2>Masukkan Nickname</h2>
                <p>Pilih platform Minecraft-mu lalu spin hadiah random.</p>
            </div>

            <div class="lucky-spin-modal-body">
                <form id="luckySpinForm">
                    <div class="lucky-spin-form-group">
                        <label for="luckySpinUsername">Nickname Minecraft</label>
                        <input type="text" name="username" id="luckySpinUsername" class="lucky-spin-input" placeholder="Masukkan Username" required autocomplete="off">
                    </div>

                    <div class="lucky-spin-platform-grid">
                        <button type="submit" name="platform" value="java" class="lucky-spin-platform-btn">
                            <i class="fas fa-desktop"></i>
                            <span>JAVA EDITION</span>
                        </button>
                        <button type="submit" name="platform" value="bedrock" class="lucky-spin-platform-btn">
                            <i class="fas fa-gamepad"></i>
                            <span>BEDROCK EDITION</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.LUCKY_SPIN = {
            status: <?= json_encode($campaign->status); ?>,
            statusLabel: <?= json_encode($campaign->status_label); ?>,
            statusMessage: <?= json_encode($campaign->status_message); ?>,
            playUrl: <?= json_encode(base_url('lucky-spin/play/' . $campaign->token)); ?>,
            remainingPlayerSlots: <?= json_encode((int) $campaign->remaining_player_slots); ?>,
            maxSpinsPerPlayer: <?= json_encode((int) $campaign->max_spins_per_player); ?>,
            activeRewardCount: <?= json_encode((int) $campaign->available_rewards); ?>,
            rewards: <?= json_encode($rewards); ?>
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= base_url('assets/js/lucky-spin.js?v=' . (file_exists('assets/js/lucky-spin.js') ? filemtime('assets/js/lucky-spin.js') : time())); ?>"></script>
</body>
</html>
