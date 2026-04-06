<div class="container">

    <!-- Announcement Bar logic (tetap sama) -->
    <?php if (isset($announcement_bar) && $announcement_bar['enabled'] && !empty($announcement_bar['text'])): ?>
    <?php
        $gradient_style = '';
        if (!empty($announcement_bar['bg_color_1']) && !empty($announcement_bar['bg_color_2'])) {
            $color1 = html_escape($announcement_bar['bg_color_1']);
            $color2 = html_escape($announcement_bar['bg_color_2']);
            $gradient_style = "style=\"--grad-1: {$color1}; --grad-2: {$color2};\"";
        }
    ?>
    <div class="sale-announcement-box" data-aos="fade-up" <?= $gradient_style; ?>>
        <div class="sale-bg-image"></div>
        <div class="sale-announcement-content">
            <div class="sale-text">
                <?php 
                $main_text = html_escape($announcement_bar['text']);
                $pattern = '/([\d]+%\s*OFF!?)/i';
                $replacement = '<span class="sale-highlight">$1</span>';
                $highlighted_text = preg_replace($pattern, $replacement, $main_text);
                $final_output = nl2br($highlighted_text);
                ?>
                <h2><?= $final_output; ?></h2>
            </div>
            <?php if (!empty($announcement_bar['timer_end'])): ?>
            <div class="sale-timer">
                <p>This sale will end in...</p>
                <div class="timer-grid" id="sale-timer-grid" data-end-time="<?= html_escape($announcement_bar['timer_end']); ?>">
                    <div class="timer-cell"><span id="sale-days">0</span><label>Days</label></div>
                    <div class="timer-cell"><span id="sale-hours">00</span><label>Hrs</label></div>
                    <div class="timer-cell"><span id="sale-mins">00</span><label>Min</label></div>
                    <div class="timer-cell"><span id="sale-secs">00</span><label>Sec</label></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTION FLASH SALE (Existing) -->
    <?php if (isset($flash_sales) && !empty($flash_sales)): ?>
    <div class="flash-sale-section">
        <h3 class="flash-sale-main-title">FLASH SALE</h3>
        <div class="fs-grid-v2">
            <?php foreach($flash_sales as $fs): ?>
                <?php
                    $flash_price = $fs->original_price - ($fs->original_price * $fs->discount_percentage / 100);
                ?>
                <a href="<?= base_url('cart/add_flash_sale/' . $fs->id); ?>" class="fs-card-v2-link" data-aos="fade-up">
                    <div class="fs-card-v2">
                        <div class="fs-card-image-v2">
                            <img src="<?= html_escape($fs->image_url); ?>" alt="<?= html_escape($fs->name); ?>">
                            <span class="fs-discount-badge"><?= $fs->discount_percentage; ?>%</span>
                        </div>
                        <div class="fs-card-body-v2">
                            <h4 class="fs-title-v2">
                                <?php 
                                    if ($fs->category === 'currency') {
                                        echo html_escape($fs->name) . ' (' . ucfirst(html_escape($fs->realm)) . ')';
                                    } else {
                                        echo html_escape($fs->name);
                                    }
                                ?>
                            </h4>
                            <p class="fs-price-v2">Rp <?= number_format($flash_price, 0, ',', '.'); ?></p>
                            <div class="fs-timer" data-end-time="<?= $fs->end_date; ?>">
                                <i class='bx bx-time-five'></i>
                                <strong class="flash-sale-countdown">00:00:00</strong>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- [BARU] SECTION FEATURED PRODUCTS (Hot Deals) -->
    <!-- [MODIFIKASI] Link sekarang memicu popup quantity -->
    <?php if (isset($featured_products) && !empty($featured_products)): ?>
    <div class="flash-sale-section" style="margin-top: 30px; background-color: #261d12;"> 
        <h3 class="flash-sale-main-title" style="color: #b87841;"><i class='bx bxs-hot'></i> HOT DEALS</h3>
        
        <div class="featured-grid">
            
            <?php foreach($featured_products as $fp): ?>
                <?php
                    // Hitung harga diskon featured
                    $featured_price = $fp->original_price - ($fp->original_price * $fp->discount_percentage / 100);
                    $product_name_display = $fp->name;
                ?>
                
                <!-- [MODIFIKASI] Tambahkan data-image-url -->
                <a href="#" 
                   class="fs-card-v2-link js-featured-popup" 
                   data-aos="fade-up"
                   data-featured-id="<?= $fp->id; ?>"
                   data-name="<?= html_escape($product_name_display); ?>"
                   data-price="<?= $featured_price; ?>"
                   data-original-price="<?= $fp->original_price; ?>"
                   data-image-url="<?= html_escape($fp->image_url); ?>"
                   >
                    <div class="fs-card-v2">
                        <div class="fs-card-image-v2">
                            <img src="<?= html_escape($fp->image_url); ?>" alt="<?= html_escape($fp->name); ?>">
                            
                            <?php if($fp->discount_percentage > 0): ?>
                                <span class="fs-discount-badge" style="background: linear-gradient(45deg, #e73453, #db4845); color: #fff;"><?= $fp->discount_percentage; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="fs-card-body-v2">
                            <h4 class="fs-title-v2">
                                <?= html_escape($product_name_display); ?>
                            </h4>
                            <p class="fs-price-v2" style="color: #FFD700;">Rp <?= number_format($featured_price, 0, ',', '.'); ?></p>
                            
                            <div class="fs-timer" style="margin-top: 5px;">
                                <?php if($fp->discount_percentage > 0): ?>
                                    <span style="color: #e73453; font-size: 0.8rem; text-decoration: line-through;">Rp <?= number_format($fp->original_price, 0, ',', '.'); ?></span>
                                <?php else: ?>
                                    <span style="color: #e73453; font-size: 0.8rem;">Best Seller</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

        </div>
    </div>
    <?php endif; ?>
    <!-- [AKHIR] FEATURED SECTION -->

    <div class="main-cards-container">
        <a href="#" class="main-card card-ranks action-btn" data-type="rank">
            <div class="main-card-content">
                <h2>RANKS</h2>
                <p>Click to view ‎ <i class="fa-solid fa-arrow-right-long"></i></p>
            </div>
            <img src="https://i.imgur.com/57NNhTW.png" class="main-card-bg-image main-card-bg-image-ranks" alt="">
        </a>
        <a href="#" class="main-card card-upgrades action-btn" data-type="rank_upgrades">
            <div class="main-card-content">
                <h2>UPGRADES</h2>
                <p>Click to view ‎ <i class="fa-solid fa-arrow-right-long"></i></p>
            </div>
            <img src="https://i.imgur.com/8EyRbey.png" class="main-card-bg-image main-card-bg-image-ranks" alt="">
        </a>
        
        <a href="#" class="main-card card-bucks action-btn" data-type="currency">
            <div class="main-card-content">
                <h2>BUCKS</h2>
                <p>Click to view ‎ <i class="fa-solid fa-arrow-right-long"></i></p>
            </div>
            <img src="<?= base_url('assets/images/bucks.png'); ?>" class="main-card-bg-image main-card-bg-image-bucks" alt="">
        </a>
        <a href="#" class="main-card card-bundles action-btn" data-type='bundles'> <div class="main-card-content">
                <h2>VARIOUS</h2>
                <p>Click to view ‎ <i class="fa-solid fa-arrow-right-long"></i></p>
            </div>
            <img src="https://i.imgur.com/zbF2VxQ.png" class="main-card-bg-image main-card-bg-image-ranks" alt="">
        </a>
    </div>

    <div class="supporters-section-v2">
        <div class="top-supporter-card">
            <h3 class="supporter-v2-title">TOP SUPPORTER</h3>
            <?php if (!empty($top_donator)): 
                $donator = $top_donator[0];
                $username = $donator['player_username'];
                $avatar_url = 'https://minotar.net/avatar/' . rawurlencode($username) . '/80';
                if (strpos($username, '.') === 0) {
                    $avatar_url = 'https://minotar.net/avatar/Steve/80';
                }
            ?>
                <div class="top-supporter-info">
                    <img src="<?= $avatar_url; ?>" alt="Top Supporter Avatar">
                    <div class="top-supporter-details">
                        <strong><?= html_escape($username); ?></strong>
                        <span>Spent the most this month.</span>
                    </div>
                </div>
            <?php else: ?>
                <p>Jadilah Top Supporter Pertama!</p>
            <?php endif; ?>
        </div>

        <div class="recent-supporters-panel">
            <h3 class="supporter-v2-title">RECENT SUPPORTERS</h3>
            <div class="recent-supporters-avatars">
                <?php if (!empty($recent_supporters)): ?>
                    <?php foreach($recent_supporters as $supporter): ?>
                        <?php
                            $username_supporter = $supporter->player_username;
                            $avatar_url_supporter = 'https://minotar.net/avatar/' . rawurlencode($username_supporter) . '/50';
                            if (strpos($username_supporter, '.') === 0) {
                                $avatar_url_supporter = 'https://minotar.net/avatar/Steve/50';
                            }
                        ?>
                        <div class="recent-supporter-item" 
                             data-tippy-content="<strong><?= html_escape($username_supporter); ?></strong><br>Purchase <?= html_escape($supporter->purchased_items); ?>">
                            <img src="<?= $avatar_url_supporter; ?>" alt="Recent Supporter Avatar">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Belum ada pembelian terbaru.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <script>
        tippy('[data-tippy-content]', {
            allowHTML: true,
            theme: 'minehive-dark'
        });
    </script>
</div>
