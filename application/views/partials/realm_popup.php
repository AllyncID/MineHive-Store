<div class="popup-overlay" id="realmPopup">
    <div class="popup-container realm-popup-container-v2">
        <span class="popup-close" onclick="hidePopup(this.closest('.popup-overlay'))">&times;</span>

        <div class="popup-header realm-popup-header-v2">
            <h2 class="popup-title">SELECT YOUR REALM</h2>
            <p class="popup-subtitle">CHOOSE YOUR REALM</p>
        </div>

        <div class="popup-body">
            <?php
            $realm_cards = [
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
                <?php foreach ($realm_cards as $realm_card): ?>
                    <a href="#" class="realm-card-v2" data-base-href="<?= base_url($realm_card['slug']); ?>">
                        <div class="realm-card-bg-v2" style="background-image: url('<?= html_escape($realm_card['image']); ?>');"></div>
                        <div class="realm-card-overlay-v2"></div>
                        <div class="realm-card-title-subtitle-card">
                            <h3 class="realm-card-title-v2"><?= html_escape($realm_card['label']); ?></h3>
                            <p class="realm-card-subtitle">Click to view products</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
