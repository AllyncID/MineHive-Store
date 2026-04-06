</div> 

    </div> 
    <div class="container footer-section">
        <div class="info-footer-container">
            <div class="info-column">
                <h3>About Us</h3>
                <p>Jelajahi dunia MineHive yang luas bersama komunitas pemain yang aktif dan ramah. Bangun, bertualang, dan taklukkan semua tantangan bersama teman-teman barumu.
                </p>
                
                <div class="footer-widget" style="margin-top: 40px;">
                    <h3>Designed By Allync</h3>
                    <p>Menciptakan sistem dan desain Store MineHive agar pemain dapat belanja dengan cepat dan nyaman.
                    </p>
                </div>
            </div>
            <div class="info-column">
                <h3>Support MineHive</h3>
                <p>Dukungan Anda membantu kami untuk terus berkembang, menambahkan fitur baru, dan menjaga server tetap berjalan dengan lancar untuk semua pemain.</p>
                <a href="https://discord.com/invite/BC8HRRMqFS" target="_blank" class="btn btn-discord">
                    <img src="<?= base_url('assets/images/discord.svg'); ?>" alt="Discord Icon">
                    OUR DISCORD
                </a>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <p>© MineHive 2025. All Rights Reserved.</p>
        <p>We are not affiliated with Mojang AB.</p>
    </footer>
    
    
<!-- [MODIFIKASI] CART TOAST NOTIFICATION (Design Baru Ala Social Proof) -->
<div id="cartToast" class="cart-toast">
    <div class="cart-toast-avatar">
        <!-- Ikon keranjang dengan lingkaran seperti avatar -->
        <i class='bx bxs-cart-add'></i>
    </div>
    <div class="cart-toast-content">
        <p>
            <strong class="item-name">Item ditambahkan!</strong>
            Item telah ditambahkan ke keranjang.
            <br>
            <span id="toastItemCount" style="color: #F9B536;">0 Items</span> dengan total
            <span id="toastCartTotal" style="color: #F9B536;">Rp 0</span>
        </p>
    </div>
</div>
<!-- [AKHIR] CART TOAST -->

<!-- EVENT POPUP (BONUS TOP UP) -->
<?php if (isset($promo_popup) && $promo_popup['is_enabled'] == 1 && !empty($promo_popup['title'])): ?>
<div id="eventPopupOverlay" class="popup-overlay event-popup-overlay">
    <div class="event-popup-container">
        <!-- Close Button -->
        <button class="event-popup-close" aria-label="Close">&times;</button>
        
        <!-- Gambar Popup (Jika Ada) -->
        <?php if (!empty($promo_popup['image_url'])): ?>
            <div class="event-popup-image-wrapper">
                <img src="<?= html_escape($promo_popup['image_url']); ?>" alt="<?= html_escape($promo_popup['title']); ?>" class="event-popup-image">
            </div>
        <?php else: ?>
            <!-- Fallback Icon jika tidak ada gambar -->
            <div class="event-popup-icon-wrapper">
                <i class='bx bxs-gift event-icon'></i>
            </div>
        <?php endif; ?>
        
        <!-- Header / Title -->
        <div class="event-popup-header">
            <h2><?= html_escape($promo_popup['title']); ?></h2>
        </div>

        <!-- Body Text -->
        <?php if (!empty($promo_popup['description'])): ?>
        <div class="event-popup-body">
            <?php 
                $descSafe = html_escape($promo_popup['description']);
                $descFormatted = nl2br($descSafe);
                $descFinal = preg_replace('/\*(.*?)\*/', '<span class="tier-highlight">$1</span>', $descFormatted);
            ?>
            <p><?= $descFinal; ?></p>
        </div>
        <?php endif; ?>

        <!-- Tiers List (SCROLLABLE & STYLED) -->
        <div class="event-popup-tiers">
            <?php 
            for ($i = 1; $i <= 5; $i++) {
                $tierKey = 'promo_tier_' . $i;
                if (!empty($promo_popup[$tierKey])) {
                    $parts = explode('|', $promo_popup[$tierKey]);
                    $tierTitle = isset($parts[0]) ? trim($parts[0]) : '';
                    $tierDescRaw  = isset($parts[1]) ? trim($parts[1]) : '';

                    if (empty($tierDescRaw) && !empty($tierTitle)) {
                        $tierDescRaw = $tierTitle;
                        $tierTitle = "BONUS TIER $i"; 
                    }

                    $formattedDesc = html_escape($tierDescRaw);
                    $formattedDesc = preg_replace('/\*(.*?)\*/', '<span class="tier-highlight">$1</span>', $formattedDesc);

                    if (!empty($tierTitle) || !empty($tierDescRaw)) {
            ?>
                        <div class="event-tier-item">
                            <div class="tier-title">
                                <?= html_escape($tierTitle); ?>
                            </div>
                            <div class="tier-desc">
                                <?= $formattedDesc; ?>
                            </div>
                        </div>
            <?php 
                    }
                }
            }
            ?>
        </div>

        <!-- Buttons -->
        <div class="event-popup-actions">
            <?php if(!empty($promo_popup['button_link']) && $promo_popup['button_link'] != '#'): ?>
                <a href="<?= html_escape($promo_popup['button_link']); ?>" class="btn btn-event-action">
                    <?= html_escape($promo_popup['button_text']); ?>
                </a>
            <?php endif; ?>
            <button class="btn btn-secondary btn-event-close">DAPATKAN DISKON</button>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- END EVENT POPUP -->

<div id="perksPopup" class="popup-overlay scrollbar-transparent">
    <div class="popup-content scrollbar-transparent">
        <h2 id="perksPopupTitle"></h2>
        <ul id="perksPopupList" class="perks-list scrollbar-transparent">
            </ul>
    </div>
</div>

<div id="socialProofPopup" class="social-proof-popup">
    <div class="avatar">
        <img id="socialProofAvatar" src="https://minotar.net/avatar/Steve/40" alt="Avatar">
    </div>
    <div class="content">
        <p id="socialProofText"></p>
    </div>
</div>

<div id="loading-overlay" class="loading-overlay">
    <div class="spinner"></div>
</div>

<?php $this->load->view('partials/login_popup'); ?>
<?php $this->load->view('partials/realm_popup'); ?>

<div class="popup-overlay" id="scratchCardPopup">
    <div class="popup-container scratch-card-container">
        <div class="popup-header scratch-header">
             <h2 class="popup-title" id="scratchCardTitle">Kamu Dapat Hadiah!</h2>
             <p class="popup-subtitle" id="scratchCardSubtitle">Gosok kartu di bawah untuk melihat hadiahmu!</p>
        </div>
        <div class="popup-body scratch-body">
            <div id="scratchCardCanvasContainer" class="scratch-card-canvas">
                <div id="scratchCardResult" class="scratch-card-result">
                    <i class='bx bxs-gift'></i>
                    <h3 id="scratchCardResultName">Hadiahnya Adalah...</h3>
                    <p id="scratchCardResultValue"></p>
                    <button id="scratchCardCopyButton" class_exists="btn btn-primary" style="display:none;">Salin Kode</button>
                </div>
            </div>
            <button id="scratchCardDoneButton" class="btn btn-secondary" style="display:none; margin-top: 20px;">Mantap, Tutup!</button>
        </div>
    </div>
</div>

<div id="battlepassPopup" class="popup-overlay">
    <div class="popup-container battlepass-popup-container">
        <span class="popup-close">&times;</span>
        <div class="popup-header">
            <h2 class="popup-title" id="bpPopupTitle">Battlepass Level</h2>
            <p class="popup-subtitle" id="bpPopupSubtitle">Select Amount of Level</p>
        </div>
        <div class="popup-body">
            <div class="bp-quantity-selector">
                <button type="button" class="bp-btn bp-btn-minus" id="bpBtnMinus" aria-label="Kurangi Kuantitas">-</button>
                <input type="number" id="bpInputQty" value="1" min="1" max="10000" aria-label="Kuantitas">
                <button type="button" class="bp-btn bp-btn-plus" id="bpBtnPlus" aria-label="Tambah Kuantitas">+</button>
            </div>
            <div class="bp-price-summary">
                <div class="bp-price-row">
                    <span id="bpPricePerLevelText">Price Per Qty:</span>
                    <span id="bpPricePerLevelValue">Rp 0</span>
                </div>
                <div class="bp-price-row bp-savings-row" id="bpSavingsRow" style="display: none; color: #2EE61D;">
                    <span>You Save:</span>
                    <span id="bpSavingsValue" style="font-weight:bold;">Rp 0</span>
                </div>
                <div class="bp-price-row bp-total-row" style="align-items: flex-end;">
                    <span>Grand Total:</span>
                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                        <span id="bpOriginalPrice" style="text-decoration: line-through; font-size: 0.85rem; color: #7f8c8d; display: none; margin-bottom: -5px;">Rp 0</span>
                        <span id="bpTotalPriceValue">Rp 0</span>
                    </div>
                </div>
            </div>
            <form id="bpAddToCartForm" action="<?= base_url('cart/add_battlepass'); ?>" method="POST" onsubmit="return false;">
                <input type="hidden" name="product_id" id="bpInputProductId">
                <input type="hidden" name="quantity" id="bpInputHiddenQty" value="1">
                <button type="submit" class="btn btn-primary btn-add-to-cart-bp" id="bpSubmitButton">
                    <i class='bx bx-plus'></i>
                    <span>Add To Cart</span>
                </button>
            </form>
        </div>
    </div>
</div>

<div id="bucksKagetPopup" class="popup-overlay">
    <div class="popup-container battlepass-popup-container bucks-kaget-popup-container">
        <span class="popup-close">&times;</span>
        <div class="popup-header">
            <h2 class="popup-title" id="bkPopupTitle">Bucks Kaget</h2>
            <p class="popup-subtitle">Atur total Bucks dan jumlah player penerima sebelum masuk cart.</p>
        </div>
        <div class="popup-body bucks-kaget-popup-body">
            <div class="bucks-kaget-popup-grid">
                <div class="form-group bk-popup-field">
                    <label for="bkInputTotalBucks" class="bk-popup-label">Total Bucks</label>
                    <input type="number" id="bkInputTotalBucks" class="bk-popup-input" min="1" max="500" value="10" inputmode="numeric">
                    <small id="bkTotalBucksHint" class="bk-popup-hint">Minimal 1 Bucks.</small>
                </div>
                <div class="form-group bk-popup-field">
                    <label for="bkInputRecipients" class="bk-popup-label">Total Player</label>
                    <input type="number" id="bkInputRecipients" class="bk-popup-input" min="1" max="100" value="10" inputmode="numeric">
                    <small id="bkRecipientsHint" class="bk-popup-hint">Minimal 1 player.</small>
                </div>
            </div>

            <div class="bp-price-summary bk-price-summary">
                <div class="bp-price-row">
                    <span>Price Per Bucks:</span>
                    <span id="bkPricePerBuckValue">Rp 0</span>
                </div>
                <div class="bp-price-row bp-savings-row" id="bkSavingsRow" style="display: none; color: #2EE61D;">
                    <span>You Save:</span>
                    <span id="bkSavingsValue" style="font-weight:bold;">Rp 0</span>
                </div>
                <div class="bp-price-row bp-total-row bk-total-row" style="align-items: flex-end;">
                    <span>Grand Total:</span>
                    <div class="bk-total-values">
                        <span id="bkOriginalPrice" style="text-decoration: line-through; font-size: 0.85rem; color: #7f8c8d; display: none; margin-bottom: -5px;">Rp 0</span>
                        <span id="bkTotalPriceValue">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="gift-field-caption bk-tier-note" id="bkTierLabel">Harga dasar masih berlaku untuk jumlah ini.</div>

            <form id="bkAddToCartForm" action="<?= base_url('cart/add_bucks_kaget'); ?>" method="POST" onsubmit="return false;">
                <input type="hidden" name="product_id" id="bkInputProductId">
                <input type="hidden" name="total_bucks" id="bkInputHiddenTotalBucks" value="10">
                <input type="hidden" name="total_recipients" id="bkInputHiddenRecipients" value="10">
                <button type="submit" class="btn btn-primary btn-add-to-cart-bp" id="bkSubmitButton">
                    <i class='bx bx-plus'></i>
                    <span>Add To Cart</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- [BARU] POPUP PEMILIHAN REALM UNTUK HOT DEALS (STYLE BARU) -->
<div id="realmChoicePopup" class="popup-overlay">
    <div class="popup-container realm-popup-container-v2">
        <span class="popup-close">&times;</span>
        <div class="popup-header realm-popup-header-v2">
            <h2 class="popup-title">SELECT YOUR REALM</h2>
            <p class="popup-subtitle">Which realm will this item be purchased for?</p>
        </div>
        <div class="popup-body">
            <?php
            $featured_realm_choices = [
                [
                    'slug' => 'survival',
                    'label' => 'SURVIVAL',
                    'image' => 'https://i.imgur.com/nwvK5M2.png'
                ],
                [
                    'slug' => 'skyblock',
                    'label' => 'SKYBLOCK',
                    'image' => 'https://i.imgur.com/bFFGxIM.png'
                ],
                [
                    'slug' => 'oneblock',
                    'label' => 'ONEBLOCK',
                    'image' => 'https://i.imgur.com/OLMGnfc.png'
                ],
            ];
            ?>
            <div class="realm-grid-v2">
                <?php foreach ($featured_realm_choices as $realm_choice): ?>
                    <a href="#" class="realm-card-v2 realm-choice-btn" data-realm="<?= html_escape($realm_choice['slug']); ?>">
                        <div class="realm-card-bg-v2" style="background-image: url('<?= html_escape($realm_choice['image']); ?>');"></div>
                        <div class="realm-card-overlay-v2"></div>
                        <div class="realm-card-title-subtitle-card">
                            <h3 class="realm-card-title-v2"><?= html_escape($realm_choice['label']); ?></h3>
                            <p class="realm-card-subtitle">Pilih Realm Ini</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<!-- [AKHIR] POPUP PEMILIHAN REALM -->

<!-- [BARU] POPUP QUANTITY UNTUK FEATURED PRODUCT -->
<div id="featuredPopup" class="popup-overlay">
    <div class="popup-container battlepass-popup-container"> 
        <span class="popup-close">&times;</span>
        <div class="popup-header">
            <h2 class="popup-title" id="ftPopupTitle">Featured Product</h2>
            
            <!-- [BARU] Icon Produk Featured -->
            <img id="ftPopupImage" src="" alt="Product Icon" class="popup-product-icon" style="display: none;">
            
            <p class="popup-subtitle">Select Quantity</p>
        </div>
        <div class="popup-body">
            <div class="bp-quantity-selector">
                <button type="button" class="bp-btn bp-btn-minus" id="ftBtnMinus">-</button>
                <input type="number" id="ftInputQty" value="1" min="1" max="50">
                <button type="button" class="bp-btn bp-btn-plus" id="ftBtnPlus">+</button>
            </div>
            <div class="bp-price-summary">
                <div class="bp-price-row">
                    <span>Price Per Unit:</span>
                    <span id="ftPricePerUnit">Rp 0</span>
                </div>
                <div class="bp-price-row bp-total-row" style="align-items: flex-end;">
                    <span>Grand Total:</span>
                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                        <span id="ftTotalPriceValue">Rp 0</span>
                    </div>
                </div>
            </div>
            
            <!-- Form Featured -->
            <form id="ftAddToCartForm" action="" method="POST">
                <input type="hidden" name="realm" id="ftInputHiddenRealm">
                <input type="hidden" name="quantity" id="ftInputHiddenQty" value="1">
                <button type="submit" class="btn btn-primary btn-add-to-cart-bp" id="ftSubmitButton">
                    <i class='bx bx-plus'></i>
                    <span>Add To Cart</span>
                </button>
            </form>
            
        </div>
    </div>
</div>
<!-- [AKHIR] POPUP FEATURED -->

<script>
    const BASE_URL = "<?= base_url(); ?>";
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/scratchcard-js@1.5.0/dist/scratchcard.min.js"></script> 

<script src="<?= base_url('assets/js/script.js?v=V1.15' . filemtime('assets/js/script.js')); ?>"></script>
<script src="<?= base_url('assets/js/snow.js?v=1.0.0' . (file_exists('assets/js/snow.js') ? filemtime('assets/js/snow.js') : '')); ?>"></script>
<?php if (!empty($page_scripts) && is_array($page_scripts)): ?>
    <?php foreach ($page_scripts as $script_url): ?>
        <script src="<?= html_escape((string) $script_url); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<style>
    .snowflake {
        position: fixed;
        top: -10vh;
        left: 0;
        width: var(--size);
        height: var(--size);
        background: white;
        border-radius: 50%;
        pointer-events: none;
        z-index: 9998; 
        animation: fall linear infinite;
        animation-duration: var(--fall-duration);
        animation-delay: var(--fall-delay);
    }

    @keyframes fall {
        from { transform: translate(var(--start-x), -10vh); }
        to { transform: translate(calc(var(--start-x) + var(--drift)), 110vh); }
    }

    /* Reuse Battlepass CSS for Featured Popup */
    .battlepass-popup-container {
        width: 100%;
        max-width: 450px; 
        background-color: #291E12;
        padding: 30px 40px;
        border-radius: 5px;
        position: relative;
        transform: scale(1);
    }
    .battlepass-popup-container .popup-header {
        text-align: center;
        margin-bottom: 25px;
    }
    .battlepass-popup-container .popup-title {
        color: var(--text-primary);
        font-size: 1.4rem;
        margin: 0;
    }
    .battlepass-popup-container .popup-subtitle {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin: 5px 0 0 0;
        font-weight: 600;
        font-family: 'Montserrat', sans-serif;
    }
    .bp-quantity-selector {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 25px; 
    }
    .bp-btn {
        background-color: #4B3420;
        border: none;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
        width: 50px;
        height: 50px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .bp-btn:hover { background-color: #764E39; }
    #bpInputQty, #ftInputQty {
        width: 100px; height: 50px; text-align: center; font-size: 1.5rem; font-weight: 700; color: #fff;
        background-color: #1B160D; border: 1px solid #4B3420; border-radius: 5px; margin: 0 -1px; -moz-appearance: textfield;
    }
    #bpInputQty::-webkit-outer-spin-button, #bpInputQty::-webkit-inner-spin-button, 
    #ftInputQty::-webkit-outer-spin-button, #ftInputQty::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .bp-btn-minus { border-radius: 5px; margin-right: 16px; }
    .bp-btn-plus { border-radius: 5px; margin-left: 16px; }
    .bp-price-summary { background-color: #1B160D; border-radius: 5px; padding: 15px 20px; margin-bottom: 25px; }
    .bp-price-row {
        display: flex; justify-content: space-between; align-items: center; padding: 8px 0; color: var(--text-secondary); font-size: 0.95rem; font-weight: 600; font-family: 'Montserrat', sans-serif;
    }
    .bp-price-row span:last-child { font-weight: 600; color: var(--text-primary); }
    .bp-savings-row { color: #2EE61D; }
    .bp-savings-row span:last-child { color: #2EE61D; }
    .bp-total-row { border-top: 1px solid #4B3420; margin-top: 5px; padding-top: 15px; font-size: 1.1rem; font-weight: 700; color: var(--text-primary); }
    .bp-total-row #bpTotalPriceValue, .bp-total-row #ftTotalPriceValue { font-size: 1.3rem; color: #ECCA01; }
    .btn-add-to-cart-bp {
        width: 100%; padding: 15px; font-size: 1.1rem; gap: 10px; font-family: 'Poppins', sans-serif; font-weight: 700; background-color: #93421F; transition: all 0.3s ease-out;
    }
    .btn-add-to-cart-bp:hover { background-color: #ad5028; }
    .bucks-kaget-popup-container {
        max-width: 560px;
        padding: 34px 34px 30px;
    }
    .bucks-kaget-popup-body {
        display: grid;
        gap: 18px;
    }
    .bucks-kaget-popup-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .bk-popup-field {
        margin: 0;
        padding: 14px 16px;
        background-color: #1B160D;
        border: 1px solid #3c2918;
        border-radius: 5px;
    }
    .bk-popup-label {
        display: block;
        color: #f2e7da;
        font-family: 'Montserrat', sans-serif;
        font-size: 0.92rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .bk-popup-input {
        width: 100%;
        min-height: 50px;
        padding: 12px 14px;
        background-color: #120d08;
        border: 1px solid #4B3420;
        border-radius: 5px;
        color: #FFFFFF;
        font-family: 'Montserrat', sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        box-sizing: border-box;
        outline: none;
        appearance: textfield;
        -moz-appearance: textfield;
    }
    .bk-popup-input::-webkit-outer-spin-button,
    .bk-popup-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .bk-popup-input:focus {
        border-color: #b87841;
        box-shadow: 0 0 0 2px rgba(184, 120, 65, 0.18);
    }
    .bk-popup-hint {
        display: block;
        margin-top: 10px;
        color: #b8aa9c;
        font-family: 'Montserrat', sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 1.6;
    }
    .bk-price-summary {
        margin-bottom: 0;
        padding: 18px 20px;
    }
    .bucks-kaget-popup-container .bp-price-row {
        font-size: 0.98rem;
    }
    .bucks-kaget-popup-container .bp-price-row span:first-child {
        color: #e9d7c4;
    }
    .bucks-kaget-popup-container .bp-price-row span:last-child {
        font-weight: 700;
    }
    .bk-total-row {
        padding-top: 18px;
    }
    .bk-total-values {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }
    #bkOriginalPrice {
        text-decoration: line-through;
        font-size: 0.9rem;
        color: #8f857a;
        display: none;
        margin-bottom: 2px !important;
    }
    #bkPricePerBuckValue {
        color: #f8e4bd;
    }
    #bkTotalPriceValue {
        font-size: 1.85rem;
        line-height: 1;
        color: #FFFFFF;
    }
    .bk-tier-note {
        margin-bottom: 0 !important;
        color: #d8bc95;
        font-family: 'Montserrat', sans-serif;
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.6;
    }
    @media (max-width: 768px) {
        .battlepass-popup-container { width: 70%; padding: 25px 20px; }
        .bp-btn { width: 45px; height: 45px; font-size: 1.3rem; }
        #bpInputQty, #ftInputQty { width: 80px; height: 45px; font-size: 1.3rem; }
        .bp-price-summary { padding: 15px; }
        .bp-price-row { font-size: 0.7rem; }
        .bp-total-row { font-size: 0.7rem; }
        .bp-total-row #bpTotalPriceValue, .bp-total-row #ftTotalPriceValue { font-size: 0.8rem; }
        .btn-add-to-cart-bp { padding: 14px; font-size: 0.8rem; }
        .bucks-kaget-popup-container {
            width: calc(100% - 32px);
            max-width: 560px;
            padding: 24px 20px 22px;
        }
        .bucks-kaget-popup-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .bucks-kaget-popup-container .popup-header {
            margin-bottom: 18px;
        }
        .bucks-kaget-popup-container .popup-title {
            font-size: 2rem;
        }
        .bucks-kaget-popup-container .popup-subtitle {
            font-size: 1rem;
            line-height: 1.5;
        }
        .bucks-kaget-popup-container .bp-price-summary {
            padding: 16px;
        }
        .bucks-kaget-popup-container .bp-price-row {
            font-size: 0.95rem;
        }
        .bucks-kaget-popup-container .bp-total-row {
            font-size: 1rem;
        }
        #bkTotalPriceValue {
            font-size: 1.55rem;
        }
        .bk-popup-input {
            min-height: 48px;
            font-size: 1rem;
        }
        .bk-popup-hint {
            font-size: 0.82rem;
        }
    }
</style>

<script>
    if(document.querySelector(".flatpickr-datetime")) {
        flatpickr(".flatpickr-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            altInput: true,
            altFormat: "F j, Y at H:i",
        });
    }

<?php if ($this->session->flashdata('success')): ?>
    Swal.fire({
        timer: 2500, title: 'Berhasil!', text: '<?= $this->session->flashdata('success'); ?>', showConfirmButton: false,
        customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text', icon: 'custom-success-icon' },
        backdrop: `rgba(27, 22, 13, 0.85)`
    });
<?php elseif ($this->session->flashdata('error')): ?>
    Swal.fire({
        title: 'Oops...', text: '<?= $this->session->flashdata('error'); ?>', showConfirmButton: true, confirmButtonText: 'Mengerti',
        customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-secondary' },
        backdrop: `rgba(27, 22, 13, 0.85)`
    });
<?php elseif ($this->session->flashdata('info')): ?>
    Swal.fire({
        title: 'Info', text: '<?= $this->session->flashdata('info'); ?>', showConfirmButton: true, confirmButtonText: 'OK',
        customClass: { popup: 'custom-info-popup', title: 'custom-info-title', htmlContainer: 'custom-info-text', icon: 'custom-info-icon', confirmButton: 'btn btn-secondary' },
        backdrop: `rgba(27, 22, 13, 0.85)`
    });
<?php endif; ?>

document.addEventListener('DOMContentLoaded', function() {
    console.log("Script transisi halaman SUKSES DIMUAT!"); 
    const body = document.body;
    document.querySelectorAll('a').forEach(link => {
        if (link.hostname === window.location.hostname && 
            link.getAttribute('target') !== '_blank' &&
            !link.href.includes('#') &&
            link.href.indexOf('javascript:') !== 0
           ) {
            link.addEventListener('click', function(e) {
                e.preventDefault(); 
                body.classList.add('is-loading');
                setTimeout(() => { window.location.href = this.href; }, 300);
            });
        }
    });
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) { body.classList.remove('is-loading'); }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const mcPlayerCountEl = document.getElementById('mc-player-count');
    const discordOnlineCountEl = document.getElementById('discord-online-count');
    if (mcPlayerCountEl) {
        fetch('https://api.mcsrvstat.us/2/minehive.id')
            .then(response => { if (!response.ok) throw new Error('Network response was not ok'); return response.json(); })
            .then(data => {
                if (data && data.online && data.players.online !== undefined) { mcPlayerCountEl.textContent = data.players.online; } 
                else if (data && data.online) { mcPlayerCountEl.textContent = '0'; } 
                else { mcPlayerCountEl.textContent = 'N/A'; }
            }).catch(error => { console.error('Error fetching MC status:', error); mcPlayerCountEl.textContent = 'N/A'; });
    }
    if (discordOnlineCountEl) {
        fetch('https://discord.com/api/v9/invites/BC8HRRMqFS?with_counts=true')
            .then(response => { if (!response.ok) throw new Error('Network response was not ok'); return response.json(); })
            .then(data => {
                if (data && data.approximate_presence_count) { discordOnlineCountEl.textContent = data.approximate_presence_count; } 
                else { discordOnlineCountEl.textContent = 'N/A'; }
            }).catch(error => { console.error('Error fetching Discord status:', error); discordOnlineCountEl.textContent = 'N/A'; });
    }
});
</script>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
</body>
</html>
