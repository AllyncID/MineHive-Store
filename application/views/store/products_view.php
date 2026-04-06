<div class="container">
    
    <div class="page-header">
        <a href="<?= base_url(''); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        <h1>
            <?= html_escape(strtoupper($realm_name)); ?> 
            <?php 
                echo strtoupper(str_replace('_', ' ', html_escape($category)));
            ?>
        </h1>
        <a href="#" class="btn btn-secondary more-info"><i class="fas fa-info-circle"></i> More Info</a>
    </div>

    <div class="product-grid">
        <?php if (empty($products)): ?>
            <p>Belum ada produk untuk kategori ini.</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <?php
                    $product_type = strtolower(trim((string) ($product['product_type'] ?? '')));
                ?>
                <div class="product-card-v2">
                    <div class="product-image">
                        <img src="<?= html_escape($product['image_url']); ?>" alt="<?= html_escape($product['name']); ?>">
                    </div>
                    <div class="product-details">
                        <h3 class="product-title"><?= html_escape($product['name']); ?></h3>
                        <div class="product-price">
                            <?= number_format($product['final_price'], 0, ',', '.'); ?><span>IDR</span>
                            
                            <?php if(isset($product['discount_percentage'])): ?>
                                <span class="original-price">
                                    <s><?= number_format($product['original_price'], 0, ',', '.'); ?> IDR</s>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php
                    // --- LOGIKA KEPEMILIKAN DENGAN HIERARKI ---
                    $is_owned = false;
                    if ($category == 'rank_upgrades') {
                        if (!empty($current_rank) && isset($rank_hierarchy[$current_rank]) && isset($product['luckperms_group']) && isset($rank_hierarchy[$product['luckperms_group']])) {
                            $player_rank_weight = $rank_hierarchy[$current_rank];
                            $product_rank_weight = $rank_hierarchy[$product['luckperms_group']];
                            if ($player_rank_weight >= $product_rank_weight) {
                                $is_owned = true;
                            }
                        }
                    }
                    ?>
                    
                    <div class="product-actions" style="flex-direction: column; align-items: stretch;"> <!-- Ubah layout jadi kolom biar muat quantity -->
                        
                        <!-- [BARU] QUANTITY SELECTOR (Hanya untuk non-upgrades, non-battlepass) -->
                        <?php if ($category != 'rank_upgrades' && $category != 'ranks' && $product_type != 'battlepass' && $product_type != 'bucks_kaget' && !$is_owned): ?>
                            <div class="product-quantity-wrapper">
                                <button type="button" class="qty-btn qty-minus">-</button>
                                <input type="number" class="qty-input" value="1" min="1" max="99">
                                <button type="button" class="qty-btn qty-plus">+</button>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; gap: 10px;">
                            <button class="btn-info js-show-perks" 
                                    data-product-id="<?= html_escape($product['id']); ?>"
                                    title="Lihat Keuntungan">
                                <i class="fas fa-question"></i>
                            </button>

                            <?php if ($is_owned): ?>
                                <a class="btn-add-to-cart" disabled style="flex-grow: 1;">OWNED</a>
                            <?php else: ?>
                                
                                <?php if ($product_type == 'battlepass'): ?>
                                    <!-- Battlepass pakai popup sendiri -->
                                    <a class="btn-add-to-cart js-show-battlepass-popup" 
                                            data-product-id="<?= html_escape($product['id']); ?>"
                                            style="flex-grow: 1;">
                                        ADD TO CART
                                    </a>
                                <?php elseif ($product_type == 'bucks_kaget'): ?>
                                    <a class="btn-add-to-cart js-show-bucks-kaget-popup"
                                            data-product-id="<?= html_escape($product['id']); ?>"
                                            style="flex-grow: 1;">
                                        ADD TO CART
                                    </a>
                                <?php else: ?>
                                    <!-- [MODIFIKASI] Tombol Add to Cart sekarang pakai AJAX class -->
                                    <a type="button" 
                                            class="btn-add-to-cart btn-ajax-add" 
                                            data-product-id="<?= html_escape($product['id']); ?>"
                                            data-category="<?= html_escape($category); ?>"
                                            style="flex-grow: 1;">
                                        ADD TO CART
                                    </a>
                                <?php endif; ?>

                            <?php endif; ?>
                        </div>

                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div> 

<script>
    window.storeProducts = {
        <?php
        if (!empty($products)) {
            $product_count = count($products);
            $i = 0;
            foreach ($products as $product) {
                $i++;
                $product_data_for_js = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['final_price'],
                    'original_raw_price' => (float)$product['price'], 
                    'product_type' => strtolower(trim((string) ($product['product_type'] ?? ''))),
                    'description' => $product['description'],
                    'image_url' => $product['image_url'] // [BARU] Tambahkan ini
                ];
                echo '"' . html_escape($product['id']) . '": ' . json_encode($product_data_for_js) . (($i < $product_count) ? ',' : '');
            }
        }
        ?>
    };
</script>
