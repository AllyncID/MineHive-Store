<!-- ... (Kode sebelum cart summary) ... -->
<?php
    // Cek terlebih dahulu apakah ada item upgrade di dalam keranjang
    $contains_upgrade = false;
    // [BARU] Cek apakah ada item battlepass
    $contains_battlepass = false;
    $contains_bucks_kaget = !empty($bucks_kaget_context['contains']);

    if (!empty($cart['items'])) {
        foreach ($cart['items'] as $item) {
            if (!empty($item['is_upgrade'])) {
                $contains_upgrade = true;
            }
            // [BARU] Cek flag is_battlepass
            if (!empty($item['is_battlepass'])) {
                $contains_battlepass = true;
            }
            
            // Jika sudah ketemu satu dari masing-masing, hentikan loop
            if ($contains_upgrade && $contains_battlepass) {
                break;
            }
        }
    }
?>
<div class="container">
    <div class="cart-container">
        <?php if (empty($cart) || empty($cart['items'])): ?>
            <div class="cart-empty">
                <h2>YOUR CART IS EMPTY</h2>
                <p style="display: block;">Looks like your cart is empty! Add some items to get started.</p>
            </div>
        <?php else: ?>
            <h2 class="cart-no-empty">YOUR CART</h2>
            <p class="cart-no-empty">Here are the items you’ve added to your cart.</p>

            <form action="<?= base_url('payment/checkout'); ?>" method="post">
                <table class="admin-table cart-table">
                     <!-- ... (thead and tbody content remains the same) ... -->
                     <thead>
                         <tr data-cart-item-id="<?= (int) ($item['id'] ?? 0); ?>"<?= !empty($item['is_bucks_kaget']) ? ' data-item-type="bucks-kaget"' : ''; ?>>
                             <th>Produk</th>
                             <th style="text-align: right;">Harga</th>
                             <th style="width: 5%;"></th>
                         </tr>
                     </thead>
                     <tbody>
                         <?php foreach ($cart['items'] as $index => $item): ?>
                         <tr>
                             <td>
                                 <!-- [MODIFIKASI] Tampilkan Quantity (x5) jika lebih dari 1 -->
                                 <?php if(isset($item['quantity']) && $item['quantity'] > 1): ?>
                                     <strong style="color: #ECCA01; margin-right: 5px;"><?= $item['quantity']; ?>x</strong>
                                 <?php endif; ?>
                                 
                                 <?= html_escape($item['name']); ?>
                             </td>
                             <td style="text-align: right;">
                                 <div class="cart-price">
                                     <?php if (isset($item['original_price']) && $item['price'] < $item['original_price']): ?>
                                         <s class="original-price-cart">Rp <?= number_format($item['original_price'], 0, ',', '.'); ?></s>
                                         <span class="final-price">Rp <?= number_format($item['price'], 0, ',', '.'); ?></span>
                                     <?php else: ?>
                                         <span class="final-price">Rp <?= number_format($item['price'], 0, ',', '.'); ?></span>
                                     <?php endif; ?>
                                 </div>
                             </td>
                             <td style="text-align: center;"><a href="<?= base_url('cart/remove/' . $index); ?>" class="remove-btn" title="Hapus Item">&times;</a></td>
                         </tr>
                         <?php endforeach; ?>
                     </tbody>
                </table>

                <div class="cart-bottom-section">
                    <div class="cart-left-panel">
                        
                        <!-- [MODIFIKASI] Logika Pesan Hasutan/Upsell -->
                        <?php if (isset($first_time_buyer_message) && $first_time_buyer_message): ?>
                            <!-- Pesan untuk Pembeli Pertama -->
                            <div class="cart-upsell-notice" data-aos="fade-up" style="background-color: rgba(249, 181, 54, 0.1); border-color: #F9B536;">
                                <i class="fas fa-solid fa-star" style="color: #F9B536;"></i>
                                <span><?= $first_time_buyer_message; ?></span>
                            </div>
                        <?php endif; // [MODIFIKASI] endif ditambahkan di sini ?>

                        <div id="cartUpsellNoticeContainer">
                            <?php if (isset($upsell_message) && $upsell_message): // [MODIFIKASI] Diubah dari elseif menjadi if ?>
                                <!-- Pesan Upsell Diskon Keranjang (tampil untuk semua, termasuk pembeli pertama jika ada) -->
                                <div class="cart-upsell-notice" data-aos="fade-up">
                                    <i class="fas fa-solid fa-money-bill"></i>
                                    <span><?= $upsell_message; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- (AKHIR MODIFIKASI) -->

                         <!-- [PERUBAHAN] Form promo kini hanya menampilkan kode yang sudah terpasang jika ada -->
                         <div class="promo-form-container" id="promo-form-container">
                             <label for="promo_code">Have a Code?</label>
                            
                            <!-- [PERUBAHAN] Tampilkan form input jika SALAH SATU (atau kedua) slot masih kosong -->
                            <div class="promo-form" id="promo-input-form" style="<?= (isset($cart['applied_promo']) && isset($cart['applied_referral'])) ? 'display: none;' : 'display: flex;'; ?>">
                                <input type="text" name="promo_code_input" id="promo_code" placeholder="Enter your code here">
                                <button type="button" id="apply-promo-btn" class="btn btn-secondary btn-apply">Apply</button>
                            </div>

                            <!-- [PERUBAHAN] Wrapper untuk kode yang terpasang -->
                            <div id="applied-codes-wrapper">
                                <!-- Tampilkan kode promo yang terpasang -->
                                <?php if (isset($cart['applied_promo'])): ?>
                                    <div class="applied-code-display" id="applied-promo-display">
                                        <span>Promo: <code><?= html_escape($cart['applied_promo']['code']); ?></code></span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Tampilkan kode referral yang terpasang -->
                                <?php if (isset($cart['applied_referral'])): ?>
                                    <div class="applied-code-display" id="applied-referral-display">
                                        <span>Referral: <code><?= html_escape($cart['applied_referral']['code']); ?></code></span>
                                    </div>
                                <?php endif; ?>
                             </div>
                         </div>
                         
                         <?php if ($contains_bucks_kaget): ?>
                             <div class="gift-panel bucks-kaget-panel" id="bucksKagetCartPanel" data-update-url="<?= base_url('cart/update_bucks_kaget'); ?>">
                                 <div class="bucks-kaget-panel-head">
                                     <div class="bucks-kaget-panel-intro">
                                         <label class="toggle-switch-label">Bucks Kaget Setup</label>
                                         <div class="gift-field-caption">Atur jumlah Bucks yang mau dibagikan, total player, dan masa aktif link claim-nya.</div>
                                     </div>
                                 </div>

                                 <div class="bucks-kaget-form">
                                     <div class="bucks-kaget-input-grid">
                                         <div class="form-group bucks-kaget-field-card">
                                             <label for="bucks_kaget_total_bucks">Total Bucks</label>
                                             <div class="bucks-kaget-stepper">
                                                 <button type="button" class="bucks-kaget-stepper-btn" data-target="bucks_kaget_total_bucks" data-step="-1" aria-label="Kurangi total bucks">-</button>
                                                 <input
                                                     type="number"
                                                     id="bucks_kaget_total_bucks"
                                                     name="bucks_kaget_total_bucks"
                                                     min="<?= (int) (($bucks_kaget_context['config']['min_total_bucks'] ?? 1)); ?>"
                                                     max="<?= (int) (($bucks_kaget_context['config']['max_total_bucks'] ?? 500)); ?>"
                                                     value="<?= (int) (($bucks_kaget_context['form']['total_bucks'] ?? ($bucks_kaget_context['config']['default_total_bucks'] ?? 1))); ?>"
                                                 >
                                                 <button type="button" class="bucks-kaget-stepper-btn" data-target="bucks_kaget_total_bucks" data-step="1" aria-label="Tambah total bucks">+</button>
                                             </div>
                                             <div class="gift-field-caption">
                                                 Minimal <?= (int) (($bucks_kaget_context['config']['min_total_bucks'] ?? 1)); ?> Bucks, maksimal <?= (int) (($bucks_kaget_context['config']['max_total_bucks'] ?? 500)); ?> Bucks.
                                             </div>
                                         </div>

                                         <div class="form-group bucks-kaget-field-card">
                                             <label for="bucks_kaget_total_recipients">Total Player</label>
                                             <div class="bucks-kaget-stepper">
                                                 <button type="button" class="bucks-kaget-stepper-btn" data-target="bucks_kaget_total_recipients" data-step="-1" aria-label="Kurangi total player">-</button>
                                                 <input
                                                     type="number"
                                                     id="bucks_kaget_total_recipients"
                                                     name="bucks_kaget_total_recipients"
                                                     min="<?= (int) (($bucks_kaget_context['config']['min_recipients'] ?? 1)); ?>"
                                                     max="<?= (int) (($bucks_kaget_context['config']['max_recipients'] ?? 100)); ?>"
                                                     value="<?= (int) (($bucks_kaget_context['form']['total_recipients'] ?? ($bucks_kaget_context['config']['default_recipients'] ?? 1))); ?>"
                                                 >
                                                 <button type="button" class="bucks-kaget-stepper-btn" data-target="bucks_kaget_total_recipients" data-step="1" aria-label="Tambah total player">+</button>
                                             </div>
                                             <div class="gift-field-caption">
                                                 Minimal <?= (int) (($bucks_kaget_context['config']['min_recipients'] ?? 1)); ?> player, maksimal <?= (int) (($bucks_kaget_context['config']['max_recipients'] ?? 100)); ?> player.
                                             </div>
                                         </div>

                                         <div class="form-group bucks-kaget-field-card">
                                             <label for="bucks_kaget_expiry_hours">Expired (Jam)</label>
                                             <div class="bucks-kaget-stepper">
                                                 <button type="button" class="bucks-kaget-stepper-btn" data-target="bucks_kaget_expiry_hours" data-step="-1" aria-label="Kurangi masa aktif">-</button>
                                                 <input
                                                     type="number"
                                                     id="bucks_kaget_expiry_hours"
                                                     name="bucks_kaget_expiry_hours"
                                                     min="<?= (int) (($bucks_kaget_context['config']['min_expiry_hours'] ?? 1)); ?>"
                                                     max="<?= (int) (($bucks_kaget_context['config']['max_expiry_hours'] ?? 168)); ?>"
                                                     value="<?= (int) (($bucks_kaget_context['form']['expiry_hours'] ?? ($bucks_kaget_context['config']['default_expiry_hours'] ?? 24))); ?>"
                                                 >
                                                 <button type="button" class="bucks-kaget-stepper-btn" data-target="bucks_kaget_expiry_hours" data-step="1" aria-label="Tambah masa aktif">+</button>
                                             </div>
                                             <div class="gift-field-caption">
                                                 Link claim akan otomatis dibuat setelah pembayaran sukses.
                                             </div>
                                         </div>
                                     </div>

                                     <div class="form-group bucks-kaget-name-card">
                                         <label for="bucks_kaget_name">Nama Link / Campaign</label>
                                         <input
                                             type="text"
                                             id="bucks_kaget_name"
                                             name="bucks_kaget_name"
                                             maxlength="120"
                                             value="<?= html_escape((string) (($bucks_kaget_context['form']['name'] ?? ''))); ?>"
                                             placeholder="Contoh: Bucks Kaget Warga MineHive"
                                         >
                                     </div>

                                     <?php if (!empty($bucks_kaget_context['display_description'])): ?>
                                         <div class="gift-field-caption bucks-kaget-description"><?= nl2br(html_escape((string) $bucks_kaget_context['display_description'])); ?></div>
                                     <?php endif; ?>
                                 </div>
                             </div>
                         <?php endif; ?>
                         
                         <?php // [MODIFIKASI] Logika di-update untuk mengecek battlepass dan bucks kaget juga ?>
                         <?php if (!$contains_upgrade && !$contains_battlepass && !$contains_bucks_kaget): // Tampilkan opsi gift HANYA JIKA TIDAK ADA item upgrade, battlepass, atau bucks kaget ?>
                             <div class="gift-panel">
                                 <label class="toggle-switch-label" for="is_gift_checkbox">Send as a Gift</label>
                                 <label class="toggle-switch">
                                     <input type="checkbox" id="is_gift_checkbox" name="is_gift" value="1">
                                     <span class="slider"></span>
                                 </label>
                                 <div id="gift-recipient-form" class="gift-recipient-form">
                                     <div class="form-group">
                                         <label for="gifting_username">Recipient Username</label>
                                         <div class="gift-search-shell">
                                             <span class="gift-preview-avatar" id="gift-preview-avatar" aria-hidden="true">
                                                 <img id="gift-preview-image" src="https://crafthead.net/helm/c06f89064c8a49119c29ea1dbd1aab82/64" alt="" onerror="this.onerror=null; this.src='https://crafthead.net/helm/c06f89064c8a49119c29ea1dbd1aab82/64';">
                                             </span>
                                             <input
                                                 type="text"
                                                 id="gifting_username"
                                                 name="gifting_username"
                                                 placeholder="Type the player's username"
                                                 autocomplete="off"
                                                 autocapitalize="none"
                                                 spellcheck="false"
                                                 maxlength="32"
                                                 aria-autocomplete="list"
                                                 aria-controls="gift-suggestion-list"
                                                 aria-expanded="false"
                                             >
                                         </div>
                                         <div class="gift-field-caption" id="gift-field-caption">Start with at least 2 characters, then pick the player to avoid typos.</div>
                                         <div class="gift-suggestions" id="gift-suggestions" hidden>
                                             <div class="gift-suggestions-status" id="gift-suggestions-status"></div>
                                             <div class="gift-suggestions-list" id="gift-suggestion-list" role="listbox" aria-label="Recipient username suggestions"></div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         <?php else: // Jika ADA item upgrade ATAU battlepass, tampilkan pesan peringatan ?>
                             <div class="info-box" style="margin-top: 20px; padding: 15px; background-color: rgba(255, 185, 59, 0.1); border-left: 3px solid #ffb93b; color: #f0f0f0;">
                                <?php if ($contains_upgrade): ?>
                                 <p style="margin:0; font-size: 14px;">Pemberian hadiah tidak tersedia jika keranjang berisi Rank Upgrades.</p>
                                <?php elseif ($contains_battlepass): ?>
                                 <p style="margin:0; font-size: 14px;">Pemberian hadiah tidak tersedia jika keranjang berisi Battlepass Level.</p>
                                <?php elseif ($contains_bucks_kaget): ?>
                                 <p style="margin:0; font-size: 14px;">Pemberian hadiah langsung tidak tersedia jika keranjang berisi Bucks Kaget. Setelah pembayaran sukses, kamu akan mendapatkan link claim random untuk dibagikan.</p>
                                <?php endif; ?>
                             </div>
                         <?php endif; ?>
                         <?php // ========================================================== ?>
                    </div>
                    <div class="cart-right-panel">
                        <div class="cart-summary">
                            <h3>Order Summary</h3>
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <!-- ID DITAMBAHKAN DI SINI -->
                                <span id="summary-subtotal">Rp <?= number_format($cart['subtotal'], 0, ',', '.'); ?></span>
                            </div>

                            <!-- Modifikasi Teks Diskon Keranjang di sini -->
                            <!-- ID DITAMBAHKAN DI SINI -->
                            <div class="summary-row discount" id="summary-cart-discount-row" style="<?= ($cart['cart_discount'] > 0) ? 'display: flex;' : 'display: none;'; ?>">
                                <span>
                                    Diskon Belanja 
                                    <!-- ID DITAMBAHKAN DI SINI -->
                                    <span id="summary-cart-discount-percentage"><?= (isset($cart['applied_cart_discount_tier']) ? (int) $cart['applied_cart_discount_tier']['percentage'] : '0'); ?></span>%
                                </span>
                                <!-- ID DITAMBAHKAN DI SINI -->
                                <span id="summary-cart-discount-amount">- Rp <?= number_format($cart['cart_discount'], 0, ',', '.'); ?></span>
                            </div>
                            <!-- Akhir Modifikasi -->

                            <!-- [PERUBAHAN] Diskon Kode Promo -->
                            <div class="summary-row discount" id="summary-promo-discount-row" style="<?= ($cart['promo_discount'] > 0) ? 'display: flex;' : 'display: none;'; ?>">
                                <span id="summary-promo-discount-text">
                                    Diskon Promo
                                    (<code><?= isset($cart['applied_promo']['code']) ? html_escape($cart['applied_promo']['code']) : ''; ?></code>)
                                </span>
                                <span id="summary-promo-discount-amount">- Rp <?= number_format($cart['promo_discount'], 0, ',', '.'); ?></span>
                            </div>
                            
                            <!-- [PERUBAHAN] Diskon Kode Referral -->
                            <div class="summary-row discount" id="summary-referral-discount-row" style="<?= ($cart['referral_discount'] > 0) ? 'display: flex;' : 'display: none;'; ?>">
                                <span id="summary-referral-discount-text">
                                    Diskon
                                    (<code><?= isset($cart['applied_referral']['code']) ? html_escape($cart['applied_referral']['code']) : ''; ?></code>)
                                </span>
                                <span id="summary-referral-discount-amount">- Rp <?= number_format($cart['referral_discount'], 0, ',', '.'); ?></span>
                            </div>

                            <div class="summary-row total">
                                <span>Grand Total</span>
                                <!-- ID DITAMBAHKAN DI SINI -->
                                <span id="summary-grand-total">Rp <?= number_format($cart['grand_total'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="checkout-btn-container">
                                <button type="submit" class="btn btn-primary btn-large btn-apply" style="width:100%;">Checkout</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <!-- ... (Cross-sell section and scripts remain the same) ... -->
    <?php if (!empty($cross_sell_products)): ?>
         <div class="cross-sell-section">
             <!-- ... existing cross-sell code ... -->
             <h3 class="cross-sell-title">You Might Also Like</h3>
             <p class="cross-sell-subtitle">Frequently bought by other players.</p>
             <div class="product-grid">
                 <?php foreach ($cross_sell_products as $product): ?>
                     <div class="product-card-v2" data-aos="fade-up">
                         <!-- ... existing product card code ... -->
                          <div class="product-image">
                              <img src="<?= html_escape($product['image_url']); ?>" alt="<?= html_escape($product['name']); ?>">
                          </div>
                          <div class="product-details">
                              <h3 class="product-title"><?= html_escape($product['name']); ?></h3>
                              <div class="product-price">
                                  <?= number_format($product['final_price'], 0, ',', '.'); ?><span>IDR</span>
                                  <?php if(isset($product['original_price'])): ?>
                                      <span class="original-price">
                                          <s><?= number_format($product['original_price'], 0, ',', '.'); ?> IDR</s>
                                      </span>
                                  <?php endif; ?>
                              </div>
                          </div>
                          <div class="product-actions">
                              <button class="btn-info js-show-perks" data-product-id="<?= html_escape($product['id']); ?>" title="Lihat Keuntungan">
                                  <i class="fas fa-question"></i>
                              </button>
                              <a href="<?= base_url('cart/add/' . $product['id']); ?>" class="btn-add-to-cart btn-ripple">
                                  <i class='bx bx-plus'></i>
                                  <span>ADD TO CART</span>
                              </a>
                              
                          </div>
                     </div>
                 <?php endforeach; ?>
             </div>
         </div>
     <?php endif; ?>
</div>

<!-- Style kustom untuk kode yang diterapkan (opsional, tapi disarankan) -->
<style>
.applied-code-display {
    background-color: #241A0F;
    padding: 12px 15px;
    border-radius: 5px;
    color: var(--text-primary);
    font-weight: 500;
    margin-top: 10px;
}
.applied-code-display code {
    background-color: #1B160D;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: 700;
    color: var(--accent-orange);
}
.bucks-kaget-panel {
    margin-top: 18px;
}
.bucks-kaget-panel-head {
    margin-bottom: 0;
}
.bucks-kaget-panel-intro {
    min-width: 0;
}
.bucks-kaget-panel-intro .toggle-switch-label {
    margin-bottom: 15px !important;
    font-size: inherit;
}
.bucks-kaget-panel-intro .gift-field-caption {
    margin-top: 0;
    max-width: 560px;
    line-height: 1.5;
}
.bucks-kaget-form {
    display: grid;
    gap: 16px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #2e2013;
}
.bucks-kaget-input-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}
.bucks-kaget-panel input[type="text"],
.bucks-kaget-panel input[type="number"] {
    width: 100%;
    background-color: #241A0F;
    border: none;
    color: #ffffff;
    border-radius: 5px;
    padding: 12px 15px;
    box-sizing: border-box;
    font-family: var(--font-body);
    font-weight: 600;
    font-size: 0.98rem;
}
.bucks-kaget-stepper {
    display: flex;
    align-items: stretch;
    background-color: #241A0F;
    border-radius: 5px;
    overflow: hidden;
}
.bucks-kaget-stepper-btn {
    width: 44px;
    min-width: 44px;
    border: none;
    background: transparent;
    color: #d7c1aa;
    font-family: var(--font-body);
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
}
.bucks-kaget-stepper-btn:hover {
    background-color: #2d2012;
    color: #ffffff;
}
.bucks-kaget-stepper-btn:active {
    background-color: #332416;
}
.bucks-kaget-stepper-btn:first-child {
    border-right: 1px solid #2e2013;
}
.bucks-kaget-stepper-btn:last-child {
    border-left: 1px solid #2e2013;
}
.bucks-kaget-stepper input[type="number"] {
    background: transparent;
    padding: 12px 14px;
    text-align: center;
    appearance: textfield;
    -moz-appearance: textfield;
}
.bucks-kaget-stepper input[type="number"]::-webkit-outer-spin-button,
.bucks-kaget-stepper input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.bucks-kaget-panel input[type="text"]:focus,
.bucks-kaget-panel input[type="number"]:focus {
    outline: none;
    box-shadow: none;
}
.bucks-kaget-field-card,
.bucks-kaget-name-card {
    margin: 0;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 0;
}
.bucks-kaget-field-card label,
.bucks-kaget-name-card label {
    display: block;
    margin-bottom: 16px;
    color: var(--text-primary);
    font-size: 0.9rem;
    font-weight: 600;
}
.bucks-kaget-name-card {
    padding: 0;
}
.bucks-kaget-description {
    margin-top: 0;
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0;
    line-height: 1.7;
}
@media (max-width: 768px) {
    .bucks-kaget-input-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
</style>

<script>
 document.addEventListener('DOMContentLoaded', function() {
     const giftCheckbox = document.getElementById('is_gift_checkbox');
     const giftForm = document.getElementById('gift-recipient-form');
     const giftInput = document.getElementById('gifting_username');
     const giftSuggestions = document.getElementById('gift-suggestions');
     const giftSuggestionList = document.getElementById('gift-suggestion-list');
     const giftSuggestionStatus = document.getElementById('gift-suggestions-status');
     const giftCaption = document.getElementById('gift-field-caption');
     const giftPreviewAvatar = document.getElementById('gift-preview-avatar');
     const giftPreviewImage = document.getElementById('gift-preview-image');
     const defaultCaption = 'Start with at least 2 characters, then pick the player to avoid typos.';
     const loadingCaption = 'Searching for matching nicknames...';
     const emptyCaption = 'No close match yet. You can still type the nickname manually.';
     const defaultGiftAvatarUrl = 'https://crafthead.net/helm/c06f89064c8a49119c29ea1dbd1aab82/64';
     let debounceTimer = null;
     let activeIndex = -1;
     let suggestions = [];
     let abortController = null;
     let latestQuery = '';

     function setCaption(message) {
         if (giftCaption) {
             giftCaption.textContent = message;
         }
     }

     function getGiftAvatarUrl(username) {
         const cleanUsername = String(username || '').trim();
         if (!cleanUsername) {
             return defaultGiftAvatarUrl;
         }

         if (cleanUsername.charAt(0) === '.') {
             return defaultGiftAvatarUrl;
         }

         return 'https://crafthead.net/helm/' + encodeURIComponent(cleanUsername) + '/64';
     }

     function refreshGiftPreview(username) {
         if (!giftPreviewImage) {
             return;
         }

         giftPreviewImage.src = getGiftAvatarUrl(username);
     }

     function closeSuggestionPanel() {
         if (!giftSuggestions || !giftSuggestionList || !giftSuggestionStatus || !giftInput) {
             return;
         }

         giftSuggestions.hidden = true;
         giftSuggestionList.innerHTML = '';
         giftSuggestionStatus.innerHTML = '';
         giftInput.setAttribute('aria-expanded', 'false');
         activeIndex = -1;
     }

     function openSuggestionPanel() {
         if (!giftSuggestions || !giftInput) {
             return;
         }

         giftSuggestions.hidden = false;
         giftInput.setAttribute('aria-expanded', 'true');
     }

     function setGiftFormVisibility() {
         if (!giftCheckbox || !giftForm) {
             return;
         }

         const isOpen = giftCheckbox.checked;
         giftForm.style.display = isOpen ? 'block' : 'none';

         if (!isOpen) {
             closeSuggestionPanel();
             if (abortController) {
                 abortController.abort();
                 abortController = null;
             }
         }
     }

     function escapeHtml(value) {
         return String(value).replace(/[&<>\"']/g, function(character) {
             const entities = {
                 '&': '&amp;',
                 '<': '&lt;',
                 '>': '&gt;',
                 '"': '&quot;',
                 "'": '&#039;'
             };

             return entities[character] || character;
         });
     }

     function renderStatus(iconClass, message) {
         if (!giftSuggestionStatus) {
             return;
         }

         giftSuggestionStatus.innerHTML = "<i class='" + iconClass + "'></i><span>" + escapeHtml(message) + "</span>";
         openSuggestionPanel();
     }

     function renderSuggestions() {
         if (!giftSuggestionList || !giftSuggestionStatus) {
             return;
         }

         if (!suggestions.length) {
             activeIndex = -1;
             giftSuggestionList.innerHTML = '';
             renderStatus('bx bx-search-alt-2', 'No matching nickname found yet. You can still continue manually.');
             setCaption(emptyCaption);
             return;
         }

         giftSuggestionStatus.innerHTML = '';
         giftSuggestionList.innerHTML = suggestions.map(function(item, index) {
             const username = escapeHtml(item.username || '');
             const platform = escapeHtml(item.platform || 'Player');
             const avatarUrl = escapeHtml(getGiftAvatarUrl(item.username || ''));
             const activeClass = index === activeIndex ? ' is-active' : '';
             const platformClass = (item.platform || '').toLowerCase() === 'bedrock' ? ' is-bedrock' : ' is-java';

             return `
                 <button
                     type="button"
                     class="gift-suggestion-item${activeClass}"
                     data-index="${index}"
                     role="option"
                     aria-selected="${index === activeIndex ? 'true' : 'false'}"
                 >
                     <span class="gift-suggestion-avatar">
                         <img src="${avatarUrl}" alt="" loading="lazy" onerror="this.onerror=null; this.src='${defaultGiftAvatarUrl}'">
                     </span>
                     <span class="gift-suggestion-copy">
                         <span class="gift-suggestion-name">${username}</span>
                         <span class="gift-suggestion-meta">Registered player</span>
                     </span>
                     <span class="gift-platform-chip${platformClass}">${platform}</span>
                 </button>
             `;
         }).join('');

         openSuggestionPanel();
         setCaption('Pick the right recipient from the list, or keep typing.');
     }

     function selectSuggestion(index) {
         const selected = suggestions[index];
         if (!selected || !giftInput) {
             return;
         }

         giftInput.value = selected.username || '';
         refreshGiftPreview(selected.username || '');
         setCaption('Recipient selected: ' + (selected.username || ''));
         closeSuggestionPanel();
         giftInput.focus();
         giftInput.setSelectionRange(giftInput.value.length, giftInput.value.length);
     }

     function fetchSuggestions(query) {
         if (!giftCheckbox || !giftCheckbox.checked || !giftInput) {
             return;
         }

         latestQuery = query;
         if (abortController) {
             abortController.abort();
         }

         abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
         renderStatus('bx bx-loader-alt bx-spin', 'Searching players...');
         setCaption(loadingCaption);

         const requestOptions = {
             headers: {
                 'X-Requested-With': 'XMLHttpRequest'
             }
         };

         if (abortController) {
             requestOptions.signal = abortController.signal;
         }

         fetch(BASE_URL + 'index.php/cart/search_usernames?q=' + encodeURIComponent(query), requestOptions)
             .then(function(response) {
                 return response.json();
             })
             .then(function(data) {
                 if (!giftInput || giftInput.value.trim() !== latestQuery) {
                     return;
                 }

                 suggestions = Array.isArray(data.suggestions) ? data.suggestions : [];
                 activeIndex = suggestions.length ? 0 : -1;
                 renderSuggestions();
             })
             .catch(function(error) {
                 if (error && error.name === 'AbortError') {
                     return;
                 }

                 console.error('Gift nickname suggestion error:', error);
                 suggestions = [];
                 renderStatus('bx bx-wifi-off', 'Suggestion is unavailable right now.');
                 setCaption('Suggestion is unavailable right now, but you can still type the username manually.');
             });
     }

     function handleGiftInput() {
         if (!giftInput) {
             return;
         }

         const query = giftInput.value.trim();
         refreshGiftPreview(query);
         suggestions = [];
         activeIndex = -1;

         if (!giftCheckbox || !giftCheckbox.checked) {
             closeSuggestionPanel();
             return;
         }

         if (debounceTimer) {
             clearTimeout(debounceTimer);
         }

         if (abortController) {
             abortController.abort();
             abortController = null;
         }

         if (query.length < 2) {
             closeSuggestionPanel();
             setCaption(defaultCaption);
             return;
         }

         debounceTimer = window.setTimeout(function() {
             fetchSuggestions(query);
         }, 180);
     }

     function moveActiveSuggestion(step) {
         if (!suggestions.length) {
             return;
         }

         if (giftSuggestions && giftSuggestions.hidden) {
             openSuggestionPanel();
         }

         activeIndex += step;
         if (activeIndex < 0) {
             activeIndex = suggestions.length - 1;
         } else if (activeIndex >= suggestions.length) {
             activeIndex = 0;
         }

         renderSuggestions();

         const activeItem = giftSuggestionList ? giftSuggestionList.querySelector('.gift-suggestion-item.is-active') : null;
         if (activeItem) {
             activeItem.scrollIntoView({ block: 'nearest' });
         }
     }

     if (giftCheckbox) {
         giftCheckbox.addEventListener('change', function() {
             setGiftFormVisibility();
             if (this.checked && giftInput) {
                 giftInput.focus();
                 setCaption(defaultCaption);
             }
         });
         setGiftFormVisibility();
     }

     if (giftInput) {
         giftInput.addEventListener('input', handleGiftInput);
         giftInput.addEventListener('focus', function() {
             refreshGiftPreview(giftInput.value.trim());
             if (suggestions.length && giftCheckbox && giftCheckbox.checked) {
                 renderSuggestions();
             }
         });
         giftInput.addEventListener('keydown', function(event) {
             if (!giftCheckbox || !giftCheckbox.checked) {
                 return;
             }

             if (event.key === 'ArrowDown') {
                 event.preventDefault();
                 moveActiveSuggestion(1);
                 return;
             }

             if (event.key === 'ArrowUp') {
                 event.preventDefault();
                 moveActiveSuggestion(-1);
                 return;
             }

             if (event.key === 'Enter' && giftSuggestions && !giftSuggestions.hidden && activeIndex >= 0) {
                 event.preventDefault();
                 selectSuggestion(activeIndex);
                 return;
             }

             if (event.key === 'Escape') {
                 closeSuggestionPanel();
             }
         });
         giftInput.addEventListener('blur', function() {
             window.setTimeout(function() {
                 closeSuggestionPanel();
             }, 120);
         });
     }

     if (giftSuggestionList) {
         giftSuggestionList.addEventListener('mousedown', function(event) {
             const suggestionButton = event.target.closest('.gift-suggestion-item');
             if (!suggestionButton) {
                 return;
             }

             event.preventDefault();
             selectSuggestion(Number(suggestionButton.dataset.index));
         });
     }

     if (giftPreviewAvatar) {
         giftPreviewAvatar.addEventListener('click', function() {
             if (giftInput) {
                 giftInput.focus();
             }
         });
     }

     document.addEventListener('click', function(event) {
         if (!giftForm || !giftForm.contains(event.target)) {
             closeSuggestionPanel();
         }
     });

     setCaption(defaultCaption);
     refreshGiftPreview(giftInput ? giftInput.value.trim() : '');
     
     // Logika untuk tombol apply promo SUDAH DIPINDAHKAN ke script.js global
     // Tidak perlu ada di sini lagi agar tidak duplikat
 });
 
 // Membuat objek JavaScript global untuk data perks cross-sell
 window.productPerks = {
     <?php if (!empty($cross_sell_products)): // Pastikan ada produk cross-sell ?>
         <?php $i = 0; foreach ($cross_sell_products as $product): $i++; // Loop produk ?>
             <?php
                // Ambil deskripsi, pastikan valid JSON, jika tidak, anggap objek kosong
                $description_json = (!empty($product['description']) && json_decode($product['description']) !== null) ? $product['description'] : '{}';
             ?>
             "<?= html_escape($product['id']); // Cetak ID produk sebagai key ?>": <?= $description_json // Cetak data JSON perks sebagai value ?>
             <?= ($i < count($cross_sell_products)) ? ',' : ''; // Tambahkan koma jika bukan item terakhir ?>
         <?php endforeach; ?>
     <?php endif; ?>
 };
</script>
