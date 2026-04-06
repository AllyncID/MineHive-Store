<?php
$viewer = is_array($viewer ?? null) ? $viewer : [];
$viewer_is_logged_in = !empty($viewer['is_logged_in']);
$viewer_username = trim((string) ($viewer['username'] ?? ''));
$viewer_platform = trim((string) ($viewer['platform'] ?? ''));
$viewer_can_quick_claim = $viewer_is_logged_in && $viewer_username !== '' && $viewer_platform !== '';
?>

<section class="bucks-kaget-page-view">
    <div class="container bucks-kaget-shell">
        <div class="page-header bucks-kaget-page-header">
            <a href="<?= base_url(); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>

            <div class="bucks-kaget-page-title">
                <h1>Bucks Kaget</h1>
            </div>

            <div class="bucks-kaget-header-stats">
                <span class="bucks-kaget-status-badge status-<?= html_escape((string) $campaign->status); ?>">
                    <?= html_escape((string) $campaign->status_label); ?>
                </span>
            </div>
        </div>

        <div class="cart-container bucks-kaget-card">
            <span class="bucks-kaget-label">Bucks Kaget</span>
            <h2><?= html_escape((string) $campaign->name); ?></h2>
            <p class="bucks-kaget-description"><?= html_escape((string) $campaign->status_message); ?></p>

            <div class="bucks-kaget-stats">
                <div class="bucks-kaget-stat">
                    <span>Total Bucks</span>
                    <strong><?= number_format((int) $campaign->total_allocated_bucks, 0, ',', '.'); ?></strong>
                </div>
                <div class="bucks-kaget-stat">
                    <span>Sisa Slot</span>
                    <strong><?= number_format((int) $campaign->remaining_slots, 0, ',', '.'); ?></strong>
                </div>
                <div class="bucks-kaget-stat">
                    <span>Status</span>
                    <strong><?= html_escape((string) $campaign->status_label); ?></strong>
                </div>
            </div>

            <?php if ($campaign->status === 'active' && $viewer_can_quick_claim): ?>
                <div class="bucks-kaget-actions">
                    <button
                        type="button"
                        class="btn btn-primary bucks-kaget-trigger"
                        id="quickClaimBtn"
                        data-username="<?= html_escape($viewer_username); ?>"
                        data-platform="<?= html_escape($viewer_platform); ?>"
                    >
                        Claim Sekarang
                    </button>
                    <button type="button" class="btn btn-secondary bucks-kaget-trigger-alt" id="openClaimPopupBtn">Pakai Akun Lain</button>
                </div>
            <?php else: ?>
                <p class="bucks-kaget-helper-text">
                    Kalau kamu sudah login di MineHive, link ini bisa langsung dipakai tanpa isi ulang nickname.
                    Kalau belum, masukkan nickname Minecraft dulu lalu claim.
                </p>
                <div class="bucks-kaget-actions">
                    <button type="button" class="btn btn-primary bucks-kaget-trigger" id="openClaimPopupBtn">Masukkan Nickname & Claim</button>
                </div>
            <?php endif; ?>

            <?php if ($campaign->status !== 'active'): ?>
                <div class="bucks-kaget-actions">
                    <button type="button" class="btn btn-secondary bucks-kaget-trigger" disabled>Tidak Bisa Diclaim</button>
                </div>
            <?php endif; ?>

            <div class="bucks-kaget-note">
                <?php if (!empty($campaign->expires_at)): ?>
                    <span>Expired pada <?= date('d M Y H:i', strtotime($campaign->expires_at)); ?></span>
                <?php else: ?>
                    <span>Link ini akan berhenti saat semua bagian random sudah habis.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="popup-overlay" id="bucksKagetClaimPopup">
    <div class="popup-container login-popup-container bucks-kaget-claim-popup-container">
        <span class="popup-close">&times;</span>

        <div class="popup-header login-popup-header">
            <h2 class="popup-title">Bucks Kaget</h2>
            <p class="popup-subtitle">Masukkan nickname Minecraftmu</p>
        </div>

        <div class="popup-body">
            <form id="bucksKagetClaimForm">
                <div class="form-group">
                    <div class="username-input-container">
                        <img src="https://crafthead.net/helm/c06f89064c8a49119c29ea1dbd1aab82/64" alt="Avatar Preview" id="bucksKagetAvatarPreview">
                        <input
                            type="text"
                            name="username"
                            id="bucksKagetUsernameInput"
                            class="form-control-custom"
                            placeholder="Masukkan Username"
                            value="<?= html_escape($viewer_username); ?>"
                            required
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div class="platform-buttons-container">
                    <button type="submit" name="platform" value="java" class="platform-btn java">
                        <i class="fas fa-desktop"></i> JAVA EDITION
                    </button>
                    <button type="submit" name="platform" value="bedrock" class="platform-btn bedrock">
                        <i class="fas fa-gamepad"></i> BEDROCK EDITION
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.BUCKS_KAGET = {
        status: <?= json_encode($campaign->status); ?>,
        statusLabel: <?= json_encode($campaign->status_label); ?>,
        statusMessage: <?= json_encode($campaign->status_message); ?>,
        claimUrl: <?= json_encode(base_url('bucks-kaget/claim/' . $campaign->token)); ?>,
        viewer: {
            isLoggedIn: <?= $viewer_is_logged_in ? 'true' : 'false'; ?>,
            username: <?= json_encode($viewer_username); ?>,
            platform: <?= json_encode($viewer_platform); ?>
        }
    };
</script>
