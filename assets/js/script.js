// Menunggu seluruh struktur halaman (DOM) siap sebelum menjalankan JavaScript
document.addEventListener('DOMContentLoaded', () => {

    //==================================================
    // LOGIKA TRIGGER POPUP EVENT BONUS
    //==================================================
    const eventPopupOverlay = document.getElementById('eventPopupOverlay');
    
    if (eventPopupOverlay) {
        
        // Fungsi untuk menampilkan popup
        const showEventPopup = () => {
            eventPopupOverlay.classList.add('show');
        };

        // Trigger 1: Waktu (3-5 detik setelah load)
        const delay = Math.floor(Math.random() * (5000 - 3000 + 1) + 3000); // Random 3000-5000ms
        const timerTrigger = setTimeout(() => {
            showEventPopup();
        }, delay);

        // Trigger 2: Scroll
        const scrollTrigger = () => {
            if (window.scrollY > 300) { // Jika scroll lebih dari 300px
                showEventPopup();
                // Hapus listener setelah trigger agar tidak berat
                window.removeEventListener('scroll', scrollTrigger);
                // Batalkan timer waktu agar tidak double trigger (meski class 'show' handle visual, ini best practice)
                clearTimeout(timerTrigger);
            }
        };
        window.addEventListener('scroll', scrollTrigger);

        // Logic Tutup Popup
        const closeEventPopup = () => {
            eventPopupOverlay.classList.remove('show');
        };

        // Listener Tombol Close (X)
        const closeXBtn = eventPopupOverlay.querySelector('.event-popup-close');
        if (closeXBtn) closeXBtn.addEventListener('click', closeEventPopup);

        // Listener Tombol Close (Secondary)
        const closeSecBtn = eventPopupOverlay.querySelector('.btn-event-close');
        if (closeSecBtn) closeSecBtn.addEventListener('click', closeEventPopup);

        // Listener Klik di luar area popup
        eventPopupOverlay.addEventListener('click', (e) => {
            if (e.target === eventPopupOverlay) {
                closeEventPopup();
            }
        });
    }
    //==================================================
    // AKHIR LOGIKA POPUP EVENT
    //==================================================


    //==================================================
    // FUNGSI-FUNGSI UTAMA (didefinisikan di awal agar bisa diakses semua)
    //==================================================

    /**
     * Menampilkan popup dengan menambahkan class 'show'
     * @param {HTMLElement} popup - Elemen popup yang akan ditampilkan
     */
    const showPopup = (popup) => {
        if (popup) popup.classList.add('show');
    };

    /**
     * Menyembunyikan popup dengan menghapus class 'show'
     * @param {HTMLElement} popup - Elemen popup yang akan disembunyikan
     */
    const hidePopup = (popup) => {
        if (popup) popup.classList.remove('show');
    };


    //==================================================
    // PEMILIHAN ELEMEN-ELEMEN PENTING
    //==================================================
    const loginPopup = document.getElementById('loginPopup');
    const realmPopup = document.getElementById('realmPopup');
    const perksPopup = document.getElementById('perksPopup');
    const realmChoicePopup = document.getElementById('realmChoicePopup'); // [BARU]

    // [BARU] Elemen Popup Battlepass
    const battlepassPopup = document.getElementById('battlepassPopup');
    const bpPopupTitle = document.getElementById('bpPopupTitle');
    const bpBtnMinus = document.getElementById('bpBtnMinus');
    const bpBtnPlus = document.getElementById('bpBtnPlus');
    const bpInputQty = document.getElementById('bpInputQty');
    
    const bpPricePerLevelValue = document.getElementById('bpPricePerLevelValue');
    const bpPricePerLevelText = document.getElementById('bpPricePerLevelText'); // Ambil label harga
    
    // [MODIFIKASI] Elemen untuk tampilan hemat & harga coret
    const bpSavingsRow = document.getElementById('bpSavingsRow');
    const bpSavingsValue = document.getElementById('bpSavingsValue');
    const bpOriginalPrice = document.getElementById('bpOriginalPrice');
    
    const bpTotalPriceValue = document.getElementById('bpTotalPriceValue');
    const bpAddToCartForm = document.getElementById('bpAddToCartForm');
    const bpInputProductId = document.getElementById('bpInputProductId');
    const bpInputHiddenQty = document.getElementById('bpInputHiddenQty');
    const bpSubmitButton = document.getElementById('bpSubmitButton');

    const bucksKagetPopup = document.getElementById('bucksKagetPopup');
    const bkPopupTitle = document.getElementById('bkPopupTitle');
    const bkInputTotalBucks = document.getElementById('bkInputTotalBucks');
    const bkInputRecipients = document.getElementById('bkInputRecipients');
    const bkPricePerBuckValue = document.getElementById('bkPricePerBuckValue');
    const bkSavingsRow = document.getElementById('bkSavingsRow');
    const bkSavingsValue = document.getElementById('bkSavingsValue');
    const bkOriginalPrice = document.getElementById('bkOriginalPrice');
    const bkTotalPriceValue = document.getElementById('bkTotalPriceValue');
    const bkTierLabel = document.getElementById('bkTierLabel');
    const bkInputProductId = document.getElementById('bkInputProductId');
    const bkInputHiddenTotalBucks = document.getElementById('bkInputHiddenTotalBucks');
    const bkInputHiddenRecipients = document.getElementById('bkInputHiddenRecipients');
    const bkSubmitButton = document.getElementById('bkSubmitButton');
    const bkAddToCartForm = document.getElementById('bkAddToCartForm');
    const bkTotalBucksHint = document.getElementById('bkTotalBucksHint');
    const bkRecipientsHint = document.getElementById('bkRecipientsHint');

    // [FITUR BARU] Elemen Popup Featured Product
    const featuredPopup = document.getElementById('featuredPopup');
    const ftPopupTitle = document.getElementById('ftPopupTitle');
    const ftBtnMinus = document.getElementById('ftBtnMinus');
    const ftBtnPlus = document.getElementById('ftBtnPlus');
    const ftInputQty = document.getElementById('ftInputQty');
    const ftPricePerUnit = document.getElementById('ftPricePerUnit');
    const ftTotalPriceValue = document.getElementById('ftTotalPriceValue');
    const ftAddToCartForm = document.getElementById('ftAddToCartForm');
    const ftInputHiddenQty = document.getElementById('ftInputHiddenQty');
    const ftInputHiddenRealm = document.getElementById('ftInputHiddenRealm'); // [BARU]
    const ftSubmitButton = document.getElementById('ftSubmitButton');
    
    // Variabel global untuk menyimpan data featured product saat ini
    let currentFeaturedProduct = null;

    const loginForm = document.getElementById('loginForm');
    const isLoggedIn = document.body.dataset.isLoggedIn === 'true';
    // --- Elemen untuk Flash Sale ---
    const fsGrid = document.querySelector('.fs-grid-v2'); // Target grid yang berisi item
    let fsScrollWrapper; // Akan dibuat jika diperlukan

    // [FITUR BARU] Elemen Kartu Gosok
    const scratchPopup = document.getElementById('scratchCardPopup');
    const scratchCardTitle = document.getElementById('scratchCardTitle');
    const scratchCardSubtitle = document.getElementById('scratchCardSubtitle');
    const scratchCardCanvasContainer = document.getElementById('scratchCardCanvasContainer');
    const scratchCardResult = document.getElementById('scratchCardResult');
    const scratchCardResultName = document.getElementById('scratchCardResultName');
    const scratchCardResultValue = document.getElementById('scratchCardResultValue');
    const scratchCopyButton = document.getElementById('scratchCardCopyButton');
    const scratchDoneButton = document.getElementById('scratchCardDoneButton');
    // [PERUBAHAN] Variabel global untuk menyimpan ID hadiah yg sedang tampil
    let currentWonRewardId = null; 
    let activeScratchCard = null; // Variabel untuk menyimpan instance kartu gosok
    let currentBpProduct = null; // [BARU] Untuk menyimpan data produk BP yang aktif
    let currentBucksKagetProduct = null;
    // =================================


    //==================================================
    // FUNGSI HELPER BARU (Battlepass & Formatting)
    //==================================================

    /**
     * [BARU] Fungsi untuk format angka ke Rupiah (tanpa prefix)
     * @param {number} num Angka yang akan diformat
     * @returns {string} String angka terformat (e.g., "10.000")
     */
    const formatRupiah = (num) => {
        return new Intl.NumberFormat('id-ID').format(num);
    };

    const normalizeProductType = (value) => String(value || '').trim().toLowerCase();

    const getDefaultBucksKagetPriceTiers = () => ([
        { min_qty: 1, price_per_buck: 7000 },
        { min_qty: 10, price_per_buck: 6850 },
        { min_qty: 25, price_per_buck: 6700 },
        { min_qty: 50, price_per_buck: 6550 },
        { min_qty: 100, price_per_buck: 6400 },
        { min_qty: 200, price_per_buck: 6250 }
    ]);

    const parseBucksKagetConfig = (product) => {
        const fallback = {
            default_total_bucks: 10,
            min_total_bucks: 1,
            max_total_bucks: 500,
            default_recipients: 10,
            min_recipients: 1,
            max_recipients: 100,
            price_tiers: getDefaultBucksKagetPriceTiers()
        };

        if (!product || typeof product.description !== 'string' || product.description.trim() === '') {
            return fallback;
        }

        try {
            const parsed = JSON.parse(product.description);
            if (!parsed || Array.isArray(parsed) || typeof parsed !== 'object') {
                return fallback;
            }

            const rawTiers = Array.isArray(parsed.price_tiers) ? parsed.price_tiers : [];
            const normalizedTiers = rawTiers
                .map((tier) => ({
                    min_qty: Math.max(1, parseInt(tier.min_qty, 10) || 1),
                    price_per_buck: Math.max(1, parseFloat(tier.price_per_buck || tier.price_per_level || 0) || 0)
                }))
                .filter((tier) => tier.price_per_buck > 0)
                .sort((a, b) => a.min_qty - b.min_qty);

            return {
                default_total_bucks: Math.max(1, parseInt(parsed.default_total_bucks || parsed.total_bucks || fallback.default_total_bucks, 10) || fallback.default_total_bucks),
                min_total_bucks: Math.max(1, parseInt(parsed.min_total_bucks || fallback.min_total_bucks, 10) || fallback.min_total_bucks),
                max_total_bucks: Math.max(1, parseInt(parsed.max_total_bucks || fallback.max_total_bucks, 10) || fallback.max_total_bucks),
                default_recipients: Math.max(1, parseInt(parsed.default_recipients || fallback.default_recipients, 10) || fallback.default_recipients),
                min_recipients: Math.max(1, parseInt(parsed.min_recipients || fallback.min_recipients, 10) || fallback.min_recipients),
                max_recipients: Math.max(1, parseInt(parsed.max_recipients || fallback.max_recipients, 10) || fallback.max_recipients),
                price_tiers: normalizedTiers.length ? normalizedTiers : fallback.price_tiers
            };
        } catch (error) {
            return fallback;
        }
    };

    const updateBucksKagetPopupPrice = () => {
        if (!currentBucksKagetProduct || !bkInputTotalBucks || !bkInputRecipients) return;

        const config = parseBucksKagetConfig(currentBucksKagetProduct);
        let totalBucks = parseInt(bkInputTotalBucks.value, 10);
        let recipients = parseInt(bkInputRecipients.value, 10);

        if (isNaN(totalBucks) || totalBucks < config.min_total_bucks) {
            totalBucks = config.min_total_bucks;
        }
        if (totalBucks > config.max_total_bucks) {
            totalBucks = config.max_total_bucks;
        }

        if (isNaN(recipients) || recipients < config.min_recipients) {
            recipients = config.min_recipients;
        }
        if (recipients > config.max_recipients) {
            recipients = config.max_recipients;
        }
        if (recipients > totalBucks) {
            recipients = totalBucks;
        }

        bkInputTotalBucks.value = totalBucks;
        bkInputRecipients.value = recipients;
        bkInputHiddenTotalBucks.value = totalBucks;
        bkInputHiddenRecipients.value = recipients;

        const currentFinalPrice = parseFloat(currentBucksKagetProduct.price || 0);
        const dbRawPrice = parseFloat(currentBucksKagetProduct.original_raw_price || 0);
        let discountRatio = dbRawPrice > 0 ? (currentFinalPrice / dbRawPrice) : 1.0;
        if (discountRatio > 1) discountRatio = 1.0;

        let tierMinQty = 1;
        let pricePerBuckRaw = dbRawPrice > 0 ? dbRawPrice : 7000;
        const sortedTiers = config.price_tiers.slice().sort((a, b) => a.min_qty - b.min_qty);
        for (let i = sortedTiers.length - 1; i >= 0; i--) {
            if (totalBucks >= sortedTiers[i].min_qty) {
                tierMinQty = sortedTiers[i].min_qty;
                pricePerBuckRaw = parseFloat(sortedTiers[i].price_per_buck || pricePerBuckRaw);
                break;
            }
        }

        const pricePerBuckFinal = pricePerBuckRaw * discountRatio;
        const totalPrice = totalBucks * pricePerBuckFinal;
        const originalPriceTotal = totalBucks * pricePerBuckRaw;
        const savings = Math.round(originalPriceTotal - totalPrice);

        bkPricePerBuckValue.textContent = `Rp ${formatRupiah(Math.round(pricePerBuckFinal))}`;
        bkTotalPriceValue.textContent = `Rp ${formatRupiah(Math.round(totalPrice))}`;

        if (savings > 100) {
            bkSavingsValue.textContent = `Rp ${formatRupiah(savings)}`;
            bkSavingsRow.style.display = 'flex';
            bkOriginalPrice.style.display = 'block';
            bkOriginalPrice.textContent = `Rp ${formatRupiah(Math.round(originalPriceTotal))}`;
        } else {
            bkSavingsRow.style.display = 'none';
            bkOriginalPrice.style.display = 'none';
        }

        bkTierLabel.textContent = tierMinQty > 1
            ? `Tier diskon aktif mulai ${formatRupiah(tierMinQty)} Bucks.`
            : 'Harga dasar masih berlaku untuk jumlah ini.';

        if (bkTotalBucksHint) {
            bkTotalBucksHint.textContent = `Minimal ${formatRupiah(config.min_total_bucks)} Bucks, maksimal ${formatRupiah(config.max_total_bucks)} Bucks.`;
        }
        if (bkRecipientsHint) {
            bkRecipientsHint.textContent = `Minimal ${formatRupiah(config.min_recipients)} player, maksimal ${formatRupiah(config.max_recipients)} player.`;
        }
    };

    const openBucksKagetPopup = (productId) => {
        const product = window.storeProducts[productId];
        if (!product || normalizeProductType(product.product_type) !== 'bucks_kaget') {
            console.error('Produk Bucks Kaget tidak ditemukan atau tipe salah:', productId);
            return;
        }

        currentBucksKagetProduct = product;
        const config = parseBucksKagetConfig(product);

        bkPopupTitle.textContent = product.name || 'Bucks Kaget';
        bkInputProductId.value = product.id;
        bkInputTotalBucks.min = config.min_total_bucks;
        bkInputTotalBucks.max = config.max_total_bucks;
        bkInputRecipients.min = config.min_recipients;
        bkInputRecipients.max = config.max_recipients;
        bkInputTotalBucks.value = config.default_total_bucks;
        bkInputRecipients.value = Math.min(config.default_total_bucks, config.default_recipients);

        updateBucksKagetPopupPrice();
        showPopup(bucksKagetPopup);
    };

    /**
     * [BARU] Fungsi untuk menghitung dan update harga popup battlepass
     * [PERBAIKAN] Sekarang menghitung rasio diskon global terhadap harga tier
     */
    const updateBattlepassPrice = () => {
        if (!currentBpProduct) return;

        let qty = parseInt(bpInputQty.value, 10);
        if (isNaN(qty) || qty < 1) {
            qty = 1;
            bpInputQty.value = 1;
        }

        let config = [];
        try {
            // Deskripsi berisi JSON config
            config = JSON.parse(currentBpProduct.description);
            if (!Array.isArray(config)) config = [];
        } catch (e) {
            console.error("Config battlepass tidak valid:", e, currentBpProduct.description);
            config = [];
        }

        // Urutkan config dari min_qty terkecil ke terbesar
        config.sort((a, b) => a.min_qty - b.min_qty);

        // --- [LOGIKA DISKON BARU] ---
        // currentBpProduct.price adalah harga SETELAH diskon global/flash sale
        // currentBpProduct.original_raw_price adalah harga database asli
        
        let currentFinalPrice = parseFloat(currentBpProduct.price);
        let dbRawPrice = parseFloat(currentBpProduct.original_raw_price);
        
        // Hitung Rasio Diskon (misal: 0.9 jika diskon 10%)
        // Jika dbRawPrice 0 atau error, anggap rasio 1.0
        let discountRatio = (dbRawPrice > 0) ? (currentFinalPrice / dbRawPrice) : 1.0;
        
        // Pastikan rasio tidak lebih dari 1 (tidak mungkin harga naik karena diskon)
        if (discountRatio > 1) discountRatio = 1.0;

        // Harga dasar (base) untuk perhitungan
        // Jika tidak ada tier yang cocok, gunakan harga final saat ini
        let pricePerLevel = currentFinalPrice;
        // Harga dasar tanpa diskon (untuk referensi coret)
        let basePriceUndiscounted = dbRawPrice;

        // Loop untuk mencari tier harga yang pas dari JSON
        let tierFound = false;
        for (let i = config.length - 1; i >= 0; i--) {
            if (qty >= config[i].min_qty) {
                // Harga tier MENTAH dari JSON
                let rawTierPrice = parseFloat(config[i].price_per_level);
                
                // [PENTING] Terapkan rasio diskon global ke harga tier JSON
                pricePerLevel = rawTierPrice * discountRatio;
                
                // Simpan harga tier mentah untuk referensi "Harga Asli"
                basePriceUndiscounted = rawTierPrice;
                
                tierFound = true;
                break;
            }
        }

        const totalPrice = qty * pricePerLevel;
        const originalPriceTotal = qty * basePriceUndiscounted; // Total harga jika tanpa diskon
        
        // [HITUNG POTONGAN] Selisih antara harga normal total vs harga diskon total
        // Kita bulatkan agar tampilan rapi
        const discountAmount = Math.round(originalPriceTotal - totalPrice);

        // Update UI Popup
        bpPricePerLevelValue.textContent = `Rp ${formatRupiah(Math.round(pricePerLevel))}`;
        bpTotalPriceValue.textContent = `Rp ${formatRupiah(Math.round(totalPrice))}`;

        // Tampilkan info hemat jika ada diskon (baik dari tier maupun global)
        if (discountAmount > 100) { // Toleransi pembulatan 100 perak
            // === JIKA ADA DISKON ===
            bpPricePerLevelText.textContent = "Discount Price:"; 
            bpSavingsValue.textContent = `Rp ${formatRupiah(discountAmount)}`;
            bpSavingsRow.style.display = 'flex';
            bpOriginalPrice.textContent = `Rp ${formatRupiah(Math.round(originalPriceTotal))}`;
            bpOriginalPrice.style.display = 'none';
        } else {
            // === JIKA TIDAK ADA DISKON ===
            bpPricePerLevelText.textContent = "Price per Qty:"; 
            bpSavingsRow.style.display = 'none'; 
            bpOriginalPrice.style.display = 'none';
        }

        // Update hidden form field
        bpInputHiddenQty.value = qty;
    };

    /**
     * [BARU] Fungsi untuk membuka popup battlepass
     * @param {string} productId ID produk battlepass
     */
    const openBattlepassPopup = (productId) => {
        // Cek data produk di 'kamus' JS
        const product = window.storeProducts[productId];
        if (!product || normalizeProductType(product.product_type) !== 'battlepass') {
            console.error("Produk battlepass tidak ditemukan atau tipe salah:", productId);
            return;
        }

        currentBpProduct = product; // Simpan produk aktif
        
        bpPopupTitle.textContent = `${product.name}`;
        bpInputProductId.value = product.id;
        bpInputQty.value = 1; // Reset ke 1
        
        // [BARU] Set Gambar Battlepass
        const bpImage = document.getElementById('bpPopupImage');
        if (bpImage) {
            if (product.image_url) {
                bpImage.src = product.image_url;
                bpImage.style.display = 'block';
            } else {
                bpImage.style.display = 'none';
            }
        }

        updateBattlepassPrice(); // Hitung harga awal
        showPopup(battlepassPopup); // Tampilkan popup
    };

    /**
     * [BARU] Fungsi untuk membuka popup quantity featured product setelah realm dipilih
     */
    const openFeaturedQuantityPopup = () => {
        if (!currentFeaturedProduct || !currentFeaturedProduct.realm) return;

        // Reset UI Popup
        ftPopupTitle.textContent = currentFeaturedProduct.name;
        ftInputQty.value = 1;
        ftInputHiddenQty.value = 1;
        if(ftInputHiddenRealm) ftInputHiddenRealm.value = currentFeaturedProduct.realm; // Set hidden input realm

        // Set Gambar Featured
        const ftImage = document.getElementById('ftPopupImage');
        if (ftImage) {
            if (currentFeaturedProduct.imageUrl) {
                ftImage.src = currentFeaturedProduct.imageUrl;
                ftImage.style.display = 'block';
            } else {
                ftImage.style.display = 'none';
            }
        }
        
        // Update URL action form secara dinamis
        ftAddToCartForm.action = BASE_URL + 'cart/add_featured/' + currentFeaturedProduct.id;
        
        // Update Harga
        updateFeaturedPrice();
        
        // Tampilkan popup quantity
        showPopup(featuredPopup);
    };

    /**
     * [FITUR BARU] Update harga di Popup Featured Product
     */
    const updateFeaturedPrice = () => {
        if (!currentFeaturedProduct) return;

        let qty = parseInt(ftInputQty.value, 10);
        if (isNaN(qty) || qty < 1) {
            qty = 1;
            ftInputQty.value = 1;
        }

        const pricePerUnit = parseFloat(currentFeaturedProduct.price);
        const totalPrice = qty * pricePerUnit;

        // Update UI
        ftPricePerUnit.textContent = `Rp ${formatRupiah(Math.round(pricePerUnit))}`;
        ftTotalPriceValue.textContent = `Rp ${formatRupiah(Math.round(totalPrice))}`;
        
        // Update hidden input
        ftInputHiddenQty.value = qty;
    };


    //==================================================
    // EVENT LISTENERS (Pendengar Aksi Pengguna)
    //==================================================

    // --- Form Login (saat disubmit) ---
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const submitter = e.submitter;

            if (submitter && submitter.name === 'platform') {
                if (!formData.has('platform')) {
                    formData.append('platform', submitter.value);
                }
            }

            // Validasi sederhana
            if (!formData.get('username') || !formData.get('platform')) {
                 Swal.fire({
                    title: 'Error', text: 'Pastikan username diisi dan platform dipilih.',
                    customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                    backdrop: `rgba(27, 22, 13, 0.85)`
                });
                return;
            }

            // Tampilkan popup loading
            Swal.fire({
                title: 'Mencari Pemain', text: 'Mohon tunggu...',
                showConfirmButton: false, allowOutsideClick: false,
                customClass: { popup: 'custom-loading-popup', loader: 'custom-loading-loader', title: 'custom-loading-title', htmlContainer: 'custom-loading-text' },
                backdrop: `rgba(27, 22, 13, 0.85)`,
                didOpen: () => { Swal.showLoading(); }
            });

            // Kirim data ke server
            fetch(BASE_URL + 'index.php/auth/login', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // 'data.username' adalah nama dengan casing yang BENAR dari server
                        Swal.fire({
                            timer: 2000, title: 'Login Berhasil!', text: 'Selamat datang kembali, ' + data.username + '!',
                            showConfirmButton: false,
                            customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text', icon: 'custom-success-icon' },
                            backdrop: `rgba(27, 22, 13, 0.85)`
                        }).then(() => {
                            // Refresh halaman untuk memuat semua data session baru, termasuk nama yang benar.
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Login Gagal', text: data.message,
                            showConfirmButton: true, confirmButtonText: 'Mengerti',
                            customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                            backdrop: `rgba(27, 22, 13, 0.85)`
                        });
                    }
                }).catch(error => {
                    console.error('Fetch Error:', error);
                    Swal.fire({
                        title: 'Koneksi Gagal', text: 'Tidak dapat terhubung ke server.',
                        customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });
                });
        });
    }


    // --- Event Delegation untuk SEMUA Aksi Klik di Halaman ---
    // Satu listener untuk meng-handle semua tombol
    document.addEventListener('click', function(e) {

        // 1. Aksi untuk tombol login utama di navbar
        if (e.target.closest('#loginBtn')) {
            e.preventDefault();
            showPopup(loginPopup);
        }

        // 2. Aksi untuk tombol close (X) di semua popup
        if (e.target.matches('.popup-close')) {
            hidePopup(e.target.closest('.popup-overlay'));
        }

        // 3. Aksi untuk menutup popup saat klik di luar area konten
        if (e.target.matches('.popup-overlay')) {
            // [PERBAIKAN] Jangan tutup popup gosok kalau diklik di overlay
            if (!e.target.matches('#scratchCardPopup')) {
                 hidePopup(e.target);
            }
        }

        // 4. Aksi untuk tombol ".action-btn" (memilih kategori RANKS/BUCKS)
        const actionButton = e.target.closest('.action-btn');
        if (actionButton) {
            e.preventDefault();
            if (isLoggedIn) {
                const categoryType = actionButton.dataset.type;

                // Currency sekarang global (langsung buka list tanpa pilih realm)
                if (categoryType === 'currency') {
                    window.location.href = BASE_URL + 'currency';
                    return;
                }

                if (realmPopup && categoryType) {
                    const realmLinks = realmPopup.querySelectorAll('.realm-card-v2');
                    realmLinks.forEach(link => {
                        const baseHref = link.dataset.baseHref;
                        if (baseHref) {
                            link.href = `${baseHref}/${categoryType}`;
                        }
                    });
                    showPopup(realmPopup);
                }
            } else {
                showPopup(loginPopup);
            }
        }
        // === INI BAGIAN UNTUK MENUTUP POPUP SECARA OTOMATIS ===
        // Logikanya terpisah dan hanya berjalan saat kartu realm diklik
        const realmCardLink = e.target.closest('.realm-card-v2');
        if (realmCardLink) {
            hidePopup(realmPopup); // <-- Tugasnya hanya menutup popup
        }

        // 5. Aksi untuk tombol info/perks ".js-show-perks"
        // [PERBAIKAN UNTUK MASALAH DESKRIPSI HILANG]
        const perksButton = e.target.closest('.js-show-perks');
        if (perksButton && perksPopup) {
            e.preventDefault();
            const perksPopupTitle = document.getElementById('perksPopupTitle');
            const perksPopupList = document.getElementById('perksPopupList');

            const productId = perksButton.dataset.productId;
            
            // Ambil data produk dari JS
            const productData = (window.storeProducts && window.storeProducts[productId]) ? window.storeProducts[productId] : null;
            let perksData = {};
            let isSimpleDescription = false;
            let descriptionText = "";

            if (productData) {
                // Cek apakah ada deskripsi
                if (productData.description && productData.description.trim() !== "") {
                    try {
                        // Coba parse sebagai JSON
                        const parsed = JSON.parse(productData.description);
                        
                        // Cek apakah ini Array (Config Bundle/Battlepass) atau Object (Perks)
                        if (Array.isArray(parsed)) {
                            // Jika Array, ini BUKAN perks standard.
                            // Jangan ditampilkan sebagai perks rusak.
                            isSimpleDescription = true;
                            descriptionText = "Paket ini berisi item spesial."; // Default message
                        } else if (
                            parsed &&
                            typeof parsed === 'object' &&
                            (
                                normalizeProductType(productData.product_type) === 'bucks_kaget' ||
                                Object.prototype.hasOwnProperty.call(parsed, 'default_total_bucks') ||
                                Object.prototype.hasOwnProperty.call(parsed, 'price_tiers')
                            )
                        ) {
                            isSimpleDescription = true;
                            const defaultTotalBucks = Number(parsed.default_total_bucks || parsed.total_bucks || 0);
                            const defaultRecipients = Number(parsed.default_recipients || 0);
                            const configLines = [];

                            if (typeof parsed.display_description === 'string' && parsed.display_description.trim() !== '') {
                                configLines.push(parsed.display_description.trim());
                            }
                            if (defaultTotalBucks > 0) {
                                configLines.push('Default pembelian: ' + defaultTotalBucks.toLocaleString('id-ID') + ' Bucks');
                            }
                            if (defaultRecipients > 0) {
                                configLines.push('Default dibagi ke ' + defaultRecipients.toLocaleString('id-ID') + ' pemain');
                            }

                            descriptionText = configLines.length
                                ? configLines.join('\n')
                                : 'Produk ini akan membuat link Bucks Kaget yang bisa dibagikan setelah pembayaran selesai.';
                        } else if (
                            parsed &&
                            typeof parsed === 'object' &&
                            (parsed.bundle_mode === 'custom_commands' || Array.isArray(parsed.commands))
                        ) {
                            const bundleDisplayDescription = typeof parsed.display_description === 'string'
                                ? parsed.display_description.trim()
                                : "";

                            if (bundleDisplayDescription !== "") {
                                try {
                                    const parsedBundleDescription = JSON.parse(bundleDisplayDescription);
                                    if (
                                        parsedBundleDescription &&
                                        typeof parsedBundleDescription === 'object' &&
                                        !Array.isArray(parsedBundleDescription)
                                    ) {
                                        perksData = parsedBundleDescription;
                                    } else {
                                        isSimpleDescription = true;
                                        descriptionText = bundleDisplayDescription;
                                    }
                                } catch (bundleDescriptionError) {
                                    isSimpleDescription = true;
                                    descriptionText = bundleDisplayDescription;
                                }
                            } else {
                                isSimpleDescription = true;
                                descriptionText = "Paket ini berisi item spesial.";
                            }
                        } else {
                            // Jika Object, anggap ini Perks valid
                            perksData = parsed;
                        }
                    } catch(e) {
                        // JIKA ERROR PARSE: Berarti ini Teks Biasa (Deskripsi Manual)
                        // Tampilkan teks ini!
                        isSimpleDescription = true;
                        descriptionText = productData.description;
                    }
                } else {
                    isSimpleDescription = true;
                    descriptionText = "Tidak ada deskripsi tersedia.";
                }
            }
            
            const card = perksButton.closest('.product-card-v2, .fs-card-v2-link'); 
            const rankName = card ? (card.querySelector('.fs-title-v2') ? card.querySelector('.fs-title-v2').textContent : card.querySelector('.product-title').textContent) : 'Item Details'; 

            perksPopupTitle.textContent = rankName + ' Details'; // Ubah 'Perks' jadi 'Details' agar lebih umum
            perksPopupList.innerHTML = '';

            if (isSimpleDescription) {
                // --- TAMPILKAN DESKRIPSI SEDERHANA ---
                const listItem = document.createElement('li');
                // Gunakan innerHTML agar bisa render <br> jika ada
                listItem.innerHTML = descriptionText.replace(/\n/g, "<br>"); 
                listItem.style.justifyContent = 'center'; // Tengahkan teks
                listItem.style.textAlign = 'center';
                perksPopupList.appendChild(listItem);
            
            } else {
                // --- TAMPILKAN PERKS KATEGORI (LOGIKA LAMA) ---
                const categories = Object.keys(perksData);
                if (categories.length > 0 && Object.values(perksData).some(arr => Array.isArray(arr) && arr.length > 0)) {
                    categories.forEach(category => {
                        if (perksData[category] && perksData[category].length > 0) {
                            const categoryTitle = document.createElement('h3');
                            categoryTitle.className = 'perks-category-title';
                            categoryTitle.textContent = category;
                            perksPopupList.appendChild(categoryTitle);
                            perksData[category].forEach(perkText => {
                                const listItem = document.createElement('li');
                                listItem.innerHTML = `${perkText.trim()}`; 
                                perksPopupList.appendChild(listItem);
                            });
                        }
                    });
                } else {
                    const listItem = document.createElement('li');
                    listItem.textContent = 'Tidak ada detail keuntungan yang tersedia untuk produk ini.';
                    perksPopupList.appendChild(listItem);
                }
            }
            showPopup(perksPopup);
        }

        // [BARU] 5B. Aksi untuk tombol ".js-show-battlepass-popup"
        const bpButton = e.target.closest('.js-show-battlepass-popup');
        if (bpButton) {
            e.preventDefault();
            const productId = bpButton.dataset.productId;
            openBattlepassPopup(productId); // Panggil fungsi helper baru
        }

        const bkButton = e.target.closest('.js-show-bucks-kaget-popup');
        if (bkButton) {
            e.preventDefault();
            const productId = bkButton.dataset.productId;
            openBucksKagetPopup(productId);
        }

        // [MODIFIKASI] 5C. Aksi untuk Featured Product Popup (Langkah 1: Pilih Realm)
        const ftButton = e.target.closest('.js-featured-popup');
        if (ftButton) {
            e.preventDefault();
            
            // Ambil data dari atribut
            const featuredId = ftButton.dataset.featuredId;
            const name = ftButton.dataset.name;
            const price = ftButton.dataset.price;
            const imageUrl = ftButton.dataset.imageUrl;
            
            // Simpan data global sementara
            currentFeaturedProduct = {
                id: featuredId,
                name: name,
                price: parseFloat(price),
                imageUrl: imageUrl,
                realm: null // Realm akan diisi nanti
            };
            
            // Tampilkan popup pemilihan realm
            showPopup(realmChoicePopup);
        }

        // [BARU] 5D. Aksi untuk Tombol Pilihan Realm di Popup
        const realmChoiceBtn = e.target.closest('.realm-choice-btn');
        if (realmChoiceBtn && currentFeaturedProduct) {
            e.preventDefault();

            // Simpan realm yang dipilih
            currentFeaturedProduct.realm = realmChoiceBtn.dataset.realm;

            // Sembunyikan popup realm
            hidePopup(realmChoicePopup);

            // Buka popup quantity
            openFeaturedQuantityPopup();
        }


        // 6. Aksi untuk tombol "Gunakan" Kode Promo
        // --- INI ADALAH BLOK YANG DIUBAH TOTAL ---
        const applyPromoBtn = e.target.closest('#apply-promo-btn');
        if (applyPromoBtn) {
            e.preventDefault();
            
            const promoCodeInput = document.getElementById('promo_code');
            const code = promoCodeInput.value.trim();

            if (!code) {
                Swal.fire({
                    title: 'Oops...', text: 'Kolom kode promo tidak boleh kosong.',
                    customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                    backdrop: `rgba(27, 22, 13, 0.85)`
                });
                return;
            }

            // Tampilkan loading
            Swal.fire({
                title: 'Menerapkan Kode', text: 'Mohon tunggu...',
                showConfirmButton: false, allowOutsideClick: false,
                customClass: { popup: 'custom-loading-popup', loader: 'custom-loading-loader', title: 'custom-loading-title', htmlContainer: 'custom-loading-text' },
                backdrop: `rgba(27, 22, 13, 0.85)`,
                didOpen: () => { Swal.showLoading(); }
            });

            // Siapkan data untuk dikirim
            const formData = new FormData();
            formData.append('promo_code', code);

            // Kirim data via fetch (AJAX)
            fetch(BASE_URL + 'index.php/cart/apply_promo', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Tampilkan notifikasi sukses
                    Swal.fire({
                        timer: 2000, title: 'Berhasil!', text: data.message,
                        showConfirmButton: false,
                        customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text', icon: 'custom-success-icon' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });

                    // Update ringkasan belanja di HTML
                    updateCartSummary(data.cart);

                } else if (data.status === 'info') {
                    // Tampilkan notifikasi info (kode valid tapi tidak berlaku)
                    Swal.fire({
                        title: 'Info', text: data.message,
                        showConfirmButton: true, confirmButtonText: 'OK',
                        customClass: { popup: 'custom-info-popup', title: 'custom-info-title', htmlContainer: 'custom-info-text', icon: 'custom-info-icon', confirmButton: 'btn btn-secondary' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });
                    
                    // Tetap update ringkasan (karena diskon mungkin jadi 0)
                    updateCartSummary(data.cart);

                } else {
                    // Tampilkan notifikasi error
                    Swal.fire({
                        title: 'Gagal', text: data.message,
                        showConfirmButton: true, confirmButtonText: 'Mengerti',
                        customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    title: 'Koneksi Gagal', text: 'Tidak dapat terhubung ke server.',
                    customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                    backdrop: `rgba(27, 22, 13, 0.85)`
                });
            });
        }

        // [FITUR BARU] Aksi untuk Tombol Kartu Gosok
        if (scratchDoneButton && e.target.closest('#scratchCardDoneButton')) {
            // [PERUBAHAN] Panggil fungsi baru untuk nandain di DB
            if (currentWonRewardId) {
                fetch(BASE_URL + 'index.php/rewards/mark_as_claimed/' + currentWonRewardId);
                currentWonRewardId = null; // Reset
            }
            hidePopup(scratchPopup);
        }
        if (scratchCopyButton && e.target.closest('#scratchCardCopyButton')) {
            const codeToCopy = e.target.dataset.code;
            if (!codeToCopy) return;

            // Trik copy ke clipboard
            const tempInput = document.createElement('input');
            document.body.appendChild(tempInput);
            tempInput.value = codeToCopy;
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            // Ganti teks tombol
            e.target.textContent = 'Berhasil Disalin!';
            e.target.disabled = true;

            // Reset setelah 2 detik
            setTimeout(() => {
                e.target.textContent = 'Salin Kode';
                e.target.disabled = false;
            }, 2000);
        }
        // =================================
    });

    // =======================================================
    // === [BARU] FUNGSI HELPER UNTUK ANIMASI HARGA        ===
    // =======================================================

    /**
     * Mengubah string harga (cth: "- Rp 10.000") menjadi angka (cth: -10000).
     * @param {string} str String harga.
     * @returns {number} Angka yang sudah diparsing.
     */
    function parsePriceString(str) {
        if (!str) return 0;
        const isNegative = str.includes('-');
        const numStr = str.replace(/[Rp.\s-]/g, '');
        const number = parseInt(numStr, 10) || 0;
        return isNegative ? -number : number;
    }

    /**
     * Mengubah angka (cth: -10000) menjadi string harga (cth: "- Rp 10.000").
     * @param {number} num Angka.
     * @returns {string} String harga yang diformat.
     */
    function formatPrice(num) {
        const prefix = num < 0 ? "- Rp " : "Rp ";
        const absoluteNum = Math.abs(num);
        return prefix + absoluteNum.toLocaleString('id-ID');
    }

    /**
     * [BARU] Menampilkan "damage text" ala RPG untuk diskon.
     * @param {number} amount - Jumlah diskon (misal: -10000).
     * @param {string} type - Tipe diskon ('cart', 'promo', 'referral').
     */
    function showDamageText(amount, type) {
        // Kita akan menempelkan "damage text" di dalam .cart-summary
        const container = document.querySelector('.cart-summary'); 
        if (!container || amount === 0) return;

        const textEl = document.createElement('div');
        textEl.className = 'damage-text-popup';
        // formatPrice sudah bisa menangani angka positif (merah) atau negatif (hijau)
        textEl.textContent = formatPrice(amount); 

        // [BARU] Atur posisi vertikal berdasarkan tipe diskon
        // Ini adalah posisi 'top' relatif terhadap .cart-summary
        if (type === 'cart') {
            textEl.style.top = '80px'; // Dekat baris diskon keranjang
        } else if (type === 'promo') {
            textEl.style.top = '110px'; // Dekat baris diskon promo
        } else if (type === 'referral') {
            textEl.style.top = '140px'; // Dekat baris diskon referral
        }

        // Ganti warna jadi hijau jika diskon (angka negatif)
        if (amount < 0) {
            textEl.style.color = '#22C55E'; // Hijau
            textEl.style.background = 'rgba(34, 197, 94, 0.1)';
            textEl.style.borderColor = 'rgba(34, 197, 94, 0.5)';
        } else {
             textEl.style.color = '#EF4444'; // Merah (jika diskon dicabut/berkurang)
             textEl.style.background = 'rgba(239, 68, 68, 0.1)';
             textEl.style.borderColor = 'rgba(239, 68, 68, 0.5)';
        }

        // Hapus elemen ini secara otomatis setelah animasi selesai
        textEl.addEventListener('animationend', () => {
            textEl.remove();
        });

        // Tempelkan elemen "damage text" ke dalam container
        container.appendChild(textEl);
    }

    /**
     * Menganimasikan transisi angka pada sebuah elemen HTML.
     * @param {string} elementId ID elemen yang akan dianimasikan.
     * @param {number} start Angka awal.
     * @param {number} end Angka tujuan.
     * @param {number} duration Durasi animasi dalam milidetik.
     */
    function animateValue(elementId, start, end, duration = 800) {
        const element = document.getElementById(elementId);
        if (!element) return;

        // Jika tidak ada perubahan, langsung set nilainya
        if (start === end) {
            element.textContent = formatPrice(end);
            return;
        }

        // --- EFEK FLASH CSS ---
        // 1. Hapus kelas lama jika ada (untuk re-trigger)
        element.classList.remove('price-update-flash');
        // 2. Trik untuk memaksa reflow browser
        void element.offsetWidth; 
        // 3. Tambahkan kelas animasi
        if (start !== end) { // Hanya flash jika nilainya berubah
            element.classList.add('price-update-flash');
        }
        // --- AKHIR EFEK FLASH ---

        let startTime = null;
        const range = end - start;

        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            // Menggunakan ease-out-cubic untuk efek yang lebih halus
            const easeOutProgress = 1 - Math.pow(1 - progress, 3); 
            
            const currentValue = Math.floor(easeOutProgress * range + start);
            
            element.textContent = formatPrice(currentValue);
            
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                // Pastikan nilai akhir adalah nilai yang tepat
                element.textContent = formatPrice(end);
                // Hapus kelas flash setelah selesai
                setTimeout(() => element.classList.remove('price-update-flash'), 400);
            }
        };
        requestAnimationFrame(step);
    }

    // =======================================================
    // === [MODIFIKASI] FUNGSI UPDATE CART SUMMARY         ===
    // =======================================================
    
    // [PERUBAHAN BESAR] Fungsi untuk update HTML keranjang kini menangani 3 diskon
    function updateCartSummary(cartData) {

        // --- Ambil OLD values dari DOM ---
        const oldSubtotal = parsePriceString(document.getElementById('summary-subtotal').textContent);
        const oldGrandTotal = parsePriceString(document.getElementById('summary-grand-total').textContent);
        const oldCartDiscount = parsePriceString(document.getElementById('summary-cart-discount-amount').textContent);
        const oldPromoDiscount = parsePriceString(document.getElementById('summary-promo-discount-amount').textContent);
        const oldReferralDiscount = parsePriceString(document.getElementById('summary-referral-discount-amount').textContent);
        
        // --- Ambil NEW values dari cartData ---
        const newSubtotal = parsePriceString(cartData.subtotal);
        const newGrandTotal = parsePriceString(cartData.grand_total);
        const newCartDiscount = parsePriceString(cartData.cart_discount);
        const newPromoDiscount = parsePriceString(cartData.promo_discount);
        const newReferralDiscount = parsePriceString(cartData.referral_discount);

        // --- [BARU] Hitung perbedaan untuk damage text ---
        // Kita hitung selisihnya. Jika diskon baru lebih kecil (lebih negatif), selisihnya akan negatif.
        const cartDiscountDiff = newCartDiscount - oldCartDiscount;
        const promoDiscountDiff = newPromoDiscount - oldPromoDiscount;
        const referralDiscountDiff = newReferralDiscount - oldReferralDiscount;

        // --- Animasikan nilai ---
        // Durasi normal 800ms, Grand Total 1200ms
        animateValue('summary-subtotal', oldSubtotal, newSubtotal, 800);
        animateValue('summary-grand-total', oldGrandTotal, newGrandTotal, 1200);
        animateValue('summary-cart-discount-amount', oldCartDiscount, newCartDiscount, 800);
        animateValue('summary-promo-discount-amount', oldPromoDiscount, newPromoDiscount, 800);
        animateValue('summary-referral-discount-amount', oldReferralDiscount, newReferralDiscount, 800);

        // --- [BARU] Tampilkan damage text JIKA ada perbedaan ---
        // Kita panggil fungsi baru ini
        if (cartDiscountDiff !== 0) {
            showDamageText(cartDiscountDiff, 'cart');
        }
        if (promoDiscountDiff !== 0) {
            showDamageText(promoDiscountDiff, 'promo');
        }
        if (referralDiscountDiff !== 0) {
            showDamageText(referralDiscountDiff, 'referral');
        }

        // --- Logika untuk menampilkan/menyembunyikan baris diskon (ini bisa instan) ---

        // Update Diskon Keranjang
        const cartDiscountRow = document.getElementById('summary-cart-discount-row');
        if (cartData.cart_discount_percentage > 0) {
            document.getElementById('summary-cart-discount-percentage').textContent = cartData.cart_discount_percentage;
            cartDiscountRow.style.display = 'flex';
        } else {
            cartDiscountRow.style.display = 'none';
        }

        // [PERUBAHAN LOGIKA DI SINI] - Ini adalah logika lama yang disalin kembali
        // Update Tampilan Form Promo
        const promoInputForm = document.getElementById('promo-input-form');
        const appliedCodesWrapper = document.getElementById('applied-codes-wrapper');
        
        let appliedCodeHTML = '';

        // Update Diskon Promo
        const promoDiscountRow = document.getElementById('summary-promo-discount-row');
        if (cartData.applied_promo_data) {
            const discount = cartData.applied_promo_data;
            document.getElementById('summary-promo-discount-text').innerHTML = `Diskon (<code>${discount.code}</code>)`;
            promoDiscountRow.style.display = 'flex';
            
            appliedCodeHTML += `<div class="applied-code-display" id="applied-promo-display"><span>Promo: <code>${discount.code}</code></span></div>`;
        } else {
            promoDiscountRow.style.display = 'none';
        }

        // Update Diskon Referral
        const referralDiscountRow = document.getElementById('summary-referral-discount-row');
        if (cartData.applied_referral_data) {
            const discount = cartData.applied_referral_data;
            document.getElementById('summary-referral-discount-text').innerHTML = `Diskon (<code>${discount.code}</code>)`;
            referralDiscountRow.style.display = 'flex';

            appliedCodeHTML += `<div class="applied-code-display" id="applied-referral-display"><span>Referral: <code>${discount.code}</code></span></div>`;
        } else {
            referralDiscountRow.style.display = 'none';
        }

        // 1. Update wrapper kode yang terpasang
        if (appliedCodesWrapper) {
            appliedCodesWrapper.innerHTML = appliedCodeHTML;
        }

        // 2. Sembunyikan form input HANYA JIKA kedua kode sudah terpasang
        if (promoInputForm) {
            if (cartData.applied_promo_data && cartData.applied_referral_data) {
                promoInputForm.style.display = 'none'; // Sembunyikan form
            } else {
                promoInputForm.style.display = 'flex'; // Tampilkan form
                // [Tambahan] Kosongkan field input setelah berhasil
                const promoCodeInput = document.getElementById('promo_code');
                if (promoCodeInput) {
                    promoCodeInput.value = '';
                }
            }
        }

        const upsellNoticeContainer = document.getElementById('cartUpsellNoticeContainer');
        if (upsellNoticeContainer) {
            if (cartData.upsell_message) {
                upsellNoticeContainer.innerHTML = `
                    <div class="cart-upsell-notice">
                        <i class="fas fa-solid fa-money-bill"></i>
                        <span>${cartData.upsell_message}</span>
                    </div>
                `;
            } else {
                upsellNoticeContainer.innerHTML = '';
            }
        }

        if (cartData.bucks_kaget_item && cartData.bucks_kaget_item.id) {
            const bucksKagetRow = document.querySelector(`tr[data-cart-item-id="${cartData.bucks_kaget_item.id}"][data-item-type="bucks-kaget"]`);
            if (bucksKagetRow) {
                const cartPriceWrap = bucksKagetRow.querySelector('.cart-price');
                const finalPriceEl = bucksKagetRow.querySelector('.final-price');
                let originalPriceEl = bucksKagetRow.querySelector('.original-price-cart');

                if (finalPriceEl && cartData.bucks_kaget_item.price) {
                    finalPriceEl.textContent = cartData.bucks_kaget_item.price;
                }

                if (cartPriceWrap) {
                    if (cartData.bucks_kaget_item.original_price) {
                        if (!originalPriceEl) {
                            originalPriceEl = document.createElement('s');
                            originalPriceEl.className = 'original-price-cart';
                            cartPriceWrap.insertBefore(originalPriceEl, finalPriceEl || cartPriceWrap.firstChild);
                        }
                        originalPriceEl.textContent = cartData.bucks_kaget_item.original_price;
                    } else if (originalPriceEl) {
                        originalPriceEl.remove();
                    }
                }
            }
        }
    }

    function updateBucksKagetCartPanel(payload) {
        if (!payload) {
            return;
        }

        const totalBucksInput = document.getElementById('bucks_kaget_total_bucks');
        const recipientsInput = document.getElementById('bucks_kaget_total_recipients');
        const expiryInput = document.getElementById('bucks_kaget_expiry_hours');
        const nameInput = document.getElementById('bucks_kaget_name');

        if (typeof payload.total_bucks !== 'undefined' && totalBucksInput) {
            totalBucksInput.value = payload.total_bucks;
        }
        if (typeof payload.total_recipients !== 'undefined' && recipientsInput) {
            recipientsInput.value = payload.total_recipients;
        }
        if (typeof payload.expiry_hours !== 'undefined' && expiryInput) {
            expiryInput.value = payload.expiry_hours;
        }
        if (typeof payload.name !== 'undefined' && nameInput) {
            nameInput.value = payload.name;
        }
    }

    const bucksKagetCartPanel = document.getElementById('bucksKagetCartPanel');
    if (bucksKagetCartPanel) {
        const updateUrl = bucksKagetCartPanel.dataset.updateUrl || '';
        const totalBucksInput = document.getElementById('bucks_kaget_total_bucks');
        const recipientsInput = document.getElementById('bucks_kaget_total_recipients');
        const expiryInput = document.getElementById('bucks_kaget_expiry_hours');
        const nameInput = document.getElementById('bucks_kaget_name');
        const stepperButtons = bucksKagetCartPanel.querySelectorAll('.bucks-kaget-stepper-btn');
        let updateTimer = null;

        const queueBucksKagetUpdate = (delay = 220) => {
            if (!updateUrl) {
                return;
            }

            if (updateTimer) {
                clearTimeout(updateTimer);
            }

            updateTimer = window.setTimeout(() => {
                const formData = new FormData();
                if (nameInput) formData.append('name', nameInput.value || '');
                if (totalBucksInput) formData.append('total_bucks', totalBucksInput.value || '');
                if (recipientsInput) formData.append('total_recipients', recipientsInput.value || '');
                if (expiryInput) formData.append('expiry_hours', expiryInput.value || '');

                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        return;
                    }

                    if (data.cart) {
                        updateCartSummary(data.cart);
                    }
                    if (data.bucks_kaget) {
                        updateBucksKagetCartPanel(data.bucks_kaget);
                    }
                })
                .catch(error => {
                    console.error('Gagal update Bucks Kaget:', error);
                });
            }, delay);
        };

        [totalBucksInput, recipientsInput, expiryInput].forEach((input) => {
            if (!input) return;
            input.addEventListener('input', () => queueBucksKagetUpdate(180));
            input.addEventListener('change', () => queueBucksKagetUpdate(0));
        });

        stepperButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.dataset.target || '';
                const direction = Number(button.dataset.step || 0);
                const input = targetId ? document.getElementById(targetId) : null;

                if (!input || !direction) {
                    return;
                }

                const min = input.min !== '' ? Number(input.min) : Number.NEGATIVE_INFINITY;
                const max = input.max !== '' ? Number(input.max) : Number.POSITIVE_INFINITY;
                const step = input.step && input.step !== 'any' ? Number(input.step) || 1 : 1;
                const currentValue = input.value !== '' ? Number(input.value) : (Number.isFinite(min) ? min : 0);

                if (!Number.isFinite(currentValue)) {
                    return;
                }

                const nextValue = Math.min(max, Math.max(min, currentValue + (step * direction)));
                if (nextValue === currentValue) {
                    return;
                }

                input.value = String(nextValue);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.focus();
                input.select();
            });
        });

        if (nameInput) {
            nameInput.addEventListener('input', () => queueBucksKagetUpdate(320));
            nameInput.addEventListener('change', () => queueBucksKagetUpdate(0));
        }
    }

    // --- Copy IP Address ---
    const copyIpBtn = document.getElementById('copy-ip-btn');
    if (copyIpBtn) {
        copyIpBtn.addEventListener('click', function() {
            const ipAddress = this.dataset.ip;

            navigator.clipboard.writeText(ipAddress).then(() => {
                Swal.fire({
                    icon: 'success', title: 'IP Disalin!', text: ipAddress + ' telah disalin ke clipboard.',
                    timer: 2000, showConfirmButton: false,
                    customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text'},
                    backdrop: `rgba(27, 22, 13, 0.85)`
                });
            }).catch(err => {
                console.error('Gagal menyalin IP: ', err);
            });
        });
    }

    // --- KODE BARU: Countdown Timer untuk Box Sale di Home ---
    const saleTimerGrid = document.getElementById('sale-timer-grid');
    if (saleTimerGrid) {
        const endTimeString = saleTimerGrid.getAttribute('data-end-time');
        const saleDaysEl = document.getElementById('sale-days');
        const saleHoursEl = document.getElementById('sale-hours');
        const saleMinsEl = document.getElementById('sale-mins');
        const saleSecsEl = document.getElementById('sale-secs');
        
        if (endTimeString && saleDaysEl && saleHoursEl && saleMinsEl && saleSecsEl) {
            const compatibleEndTimeString = endTimeString.replace(' ', 'T');
            const endTime = new Date(compatibleEndTimeString).getTime();
            
            if (!isNaN(endTime)) {
                const timerInterval = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = endTime - now;
                    
                    if (distance < 0) {
                        clearInterval(timerInterval);
                        saleDaysEl.textContent = '0';
                        saleHoursEl.textContent = '00';
                        saleMinsEl.textContent = '00';
                        saleSecsEl.textContent = '00';
                        // Opsional: Sembunyikan timer atau tampilkan pesan "BERAKHIR"
                        // saleTimerGrid.closest('.sale-timer').innerHTML = "<p>SALE HAS ENDED</p>";
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    saleDaysEl.textContent = days;
                    saleHoursEl.textContent = ('0' + hours).slice(-2);
                    saleMinsEl.textContent = ('0' + minutes).slice(-2);
                    saleSecsEl.textContent = ('0' + seconds).slice(-2);
                }, 1000);
            }
        }
    }
    // --- AKHIR KODE BARU ---

    // --- Countdown Timer untuk Announcement Bar (LAMA) ---
    // Kode ini tidak akan menemukan elemennya lagi, tapi tidak apa-apa (tidak error)
    const timerElement = document.getElementById('announcement-timer');
    if (timerElement) {
        // ... (logika timer lama, biarkan saja)
    }

    // --- LOGIKA UNTUK LIVE AVATAR PREVIEW ---
    const usernameInput = document.getElementById('username-input-field');
    const avatarPreview = document.getElementById('login-avatar-preview');
    if (usernameInput && avatarPreview) {
        let debounceTimer;
        usernameInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                let username = this.value.trim();
                let avatarUrl = 'https://crafthead.net/helm/c06f89064c8a49119c29ea1dbd1aab82/64'; // Default
                if (username && username.charAt(0) !== '.') {
                    avatarUrl = `https://crafthead.net/helm/${encodeURIComponent(username)}/64`;
                }
                avatarPreview.src = avatarUrl;
            }, 350);
        });
    }

    // --- Countdown Timer untuk SEMUA KARTU FLASH SALE ---
    const flashSaleTimers = document.querySelectorAll('.flash-sale-countdown');
    flashSaleTimers.forEach(timerEl => {
        const timerContainer = timerEl.closest('.fs-timer');
        if (!timerContainer) return; // Skip if structure is wrong

        const endTimeAttr = timerContainer.dataset.endTime;
        if (!endTimeAttr) {
            timerContainer.style.display = 'none'; // Hide timer if no end time
            return;
        }

        const endTime = new Date(endTimeAttr.replace(' ', 'T')).getTime(); // Ensure compatibility

        if (!isNaN(endTime)) {
            const timerInterval = setInterval(function() {
                const now = new Date().getTime();
                const distance = endTime - now;
                if (distance < 0) {
                    clearInterval(timerInterval);
                    timerEl.textContent = "BERAKHIR!";
                     // Optionally hide the entire card or disable the link
                     const cardLink = timerContainer.closest('.fs-card-v2-link');
                     if (cardLink) {
                         // cardLink.style.display = 'none'; // Option 1: Hide
                         cardLink.style.opacity = '0.6';    // Option 2: Dim
                         cardLink.style.pointerEvents = 'none'; // Option 3: Disable click
                         cardLink.removeAttribute('href'); // Remove link target
                     }
                    return;
                }
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                let timerString = ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2);
                if (days > 0) timerString = days + "d " + timerString;
                timerEl.textContent = timerString;
            }, 1000);
        } else {
            timerContainer.style.display = 'none'; // Hide timer if end time is invalid
        }
    });


    // --- AOS Initialization ---
    AOS.init({ duration: 800, once: false, offset: 50 });

    // --- Promo Popup Logic ---
    const promoPopup = document.getElementById('promoPopupOverlay');
    const closeBtn = document.querySelector('.popup-close-btn');
    if (promoPopup) {
        const closePromoPopup = () => promoPopup.classList.remove('active');
        if (closeBtn) closeBtn.addEventListener('click', closePromoPopup);
        promoPopup.addEventListener('click', (event) => { if (event.target === promoPopup) closePromoPopup(); });
    }

    // ===============================================
    // === LOGIKA BARU UNTUK AUTO SLIDE FLASH SALE ===
    // ===============================================
    if (fsGrid) {
        const items = fsGrid.querySelectorAll('.fs-card-v2-link'); // Select items inside the grid

        // Hanya jalankan jika ada item
        if (items.length > 3) { // Hanya aktifkan jika item lebih dari 3

            // 1. Buat Wrapper Baru Secara Dinamis
            fsScrollWrapper = document.createElement('div');
            fsScrollWrapper.className = 'fs-scroll-wrapper'; // Beri class untuk styling CSS
            fsGrid.parentNode.insertBefore(fsScrollWrapper, fsGrid); // Masukkan wrapper sebelum grid
            fsScrollWrapper.appendChild(fsGrid); // Pindahkan grid ke dalam wrapper

            // 2. Tambahkan CSS yang diperlukan secara dinamis
             const styleId = 'flash-sale-scroll-style';
             if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                style.innerHTML = `
                    .fs-scroll-wrapper {
                        overflow-x: auto; /* Aktifkan scroll horizontal */
                        padding-bottom: 15px; /* Ruang untuk scrollbar */
                        scroll-behavior: smooth; /* Efek scroll halus */
                        /* Styling scrollbar (opsional) */
                        &::-webkit-scrollbar { height: 8px; }
                        &::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
                        &::-webkit-scrollbar-thumb { background-color: var(--border-color, #4B3420); border-radius: 4px; }
                        &::-webkit-scrollbar-thumb:hover { background-color: var(--accent-orange, #764E39); }
                    }
                    .fs-grid-v2 {
                        display: flex; /* Ubah display grid menjadi flex */
                        gap: 20px; /* Jaga jarak antar item */
                        width: max-content; /* Lebar menyesuaikan konten flex */
                    }
                    .fs-card-v2-link { /* Targetkan link pembungkus kartu */
                       min-width: 320px; /* Lebar minimum item */
                       flex-shrink: 0; /* Mencegah item menyusut */
                    }
                    /* Hapus aturan grid-template-columns jika ada dari CSS lama */
                    @media (min-width: 769px) { /* Pastikan ini tidak mengganggu mobile */
                      .flash-sale-section .fs-grid-v2 {
                          grid-template-columns: none !important; /* Nonaktifkan grid di desktop */
                      }
                    }

                `;
                document.head.appendChild(style);
             }


            // 3. Logika Auto Slide (Sama seperti sebelumnya)
            let scrollInterval;
            let scrollAmount = 0;
            const scrollSpeed = 3000; // Jeda 3 detik

            const startScrolling = () => {
                if (items.length === 0) return;

                const itemWidth = items[0].offsetWidth;
                const gap = parseInt(window.getComputedStyle(fsGrid).gap) || 20;
                const itemScrollWidth = itemWidth + gap;

                // Hentikan interval lama jika ada
                clearInterval(scrollInterval);

                scrollInterval = setInterval(() => {
                    let currentScroll = fsScrollWrapper.scrollLeft;
                    let targetScroll = currentScroll + itemScrollWidth;

                    // Periksa apakah target scroll melebihi batas scroll maksimum
                    // scrollWidth = total lebar konten; clientWidth = lebar area yg terlihat
                    // Kurangi sedikit toleransi (misal 5 pixel) untuk mengatasi masalah pembulatan
                    if (targetScroll >= fsScrollWrapper.scrollWidth - fsScrollWrapper.clientWidth - 5) {
                         fsScrollWrapper.scrollTo({ left: 0, behavior: 'smooth' }); // Kembali ke awal
                    } else {
                         fsScrollWrapper.scrollBy({ left: itemScrollWidth, behavior: 'smooth' }); // Geser ke item berikutnya
                    }
                }, scrollSpeed);
            };

            const stopScrolling = () => {
                clearInterval(scrollInterval);
            };

            startScrolling(); // Mulai scrolling

            // Jeda saat hover
            fsScrollWrapper.addEventListener('mouseenter', stopScrolling);
            fsScrollWrapper.addEventListener('mouseleave', startScrolling);

        } else {
             console.log("Flash Sale items <= 3, auto slide disabled.");
        }
    } else {
         // Elemen 'fs-grid-v2' mungkin tidak ada jika tidak ada flash sale aktif
    }
    // ===============================================
    // === AKHIR LOGIKA AUTO SLIDE FLASH SALE      ===
    // ===============================================


    // =======================================================
    // === [BARU] LOGIKA SOCIAL PROOF POPUP                ===
    // =======================================================
    
    const socialProofPopup = document.getElementById('socialProofPopup');
    const socialProofAvatar = document.getElementById('socialProofAvatar');
    const socialProofText = document.getElementById('socialProofText');

    // Cek apakah elemen popup ada di halaman ini
    if (socialProofPopup && socialProofAvatar && socialProofText) {
        
        let purchaseQueue = []; // Antrian pembelian yang akan ditampilkan
        let currentPurchaseIndex = 0; // Indeks item di antrian
        let isPopupVisible = false; // Status untuk mencegah popup tumpang tindih

        // --- Fungsi 1: Mengambil data pembelian terbaru dari server ---
        const fetchSocialProof = () => {
            fetch(BASE_URL + 'index.php/home/get_recent_purchases')
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        // Update antrian dengan data baru
                        purchaseQueue = data;
                        currentPurchaseIndex = 0; // Reset indeks
                    }
                })
                .catch(error => {
                    console.error('Error fetching social proof:', error);
                });
        };

        // --- Fungsi 2: Menampilkan popup berikutnya dari antrian ---
        const showNextSocialProof = () => {
            // Jangan tampilkan jika popup sudah aktif atau antrian kosong
            if (isPopupVisible || purchaseQueue.length === 0) {
                return;
            }

            // Ambil item dari antrian
            const purchase = purchaseQueue[currentPurchaseIndex];
            if (!purchase) return;

            // Siapkan konten
            const username = purchase.username;
            const realm = purchase.realm;
            const items = purchase.items; // Ambil item pertama saja

            // Tentukan avatar (handle Bedrock)
            let avatarUrl = `https://minotar.net/avatar/${encodeURIComponent(username)}/40`;
            if (username && username.startsWith('.')) {
                avatarUrl = 'https://minotar.net/avatar/Steve/40'; // Avatar default untuk Bedrock
            }
            
            // Set konten ke HTML
            socialProofAvatar.src = avatarUrl;
            socialProofText.innerHTML = `<strong class="item-name">${escapeHTML(username)}</strong> dari realm <strong>${escapeHTML(realm)}</strong> Baru saja membeli <span class="item-name">${escapeHTML(items)}</span>`;

            // Tampilkan popup
            socialProofPopup.classList.add('show');
            isPopupVisible = true;

            // Sembunyikan setelah 7 detik
            setTimeout(() => {
                socialProofPopup.classList.remove('show');
                isPopupVisible = false;
            }, 7000); // 7 detik

            // Pindah ke indeks berikutnya, atau kembali ke 0 jika sudah di akhir
            currentPurchaseIndex = (currentPurchaseIndex + 1) % purchaseQueue.length;
        };

        // --- Helper: Fungsi untuk escape HTML (keamanan) ---
        const escapeHTML = (str) => {
            if (!str) return '';
            return str.replace(/[&<>"']/g, function(m) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[m];
            });
        };


        // --- Inisialisasi & Interval ---
        
        // 1. Langsung ambil data saat halaman dimuat
        fetchSocialProof();
        
        // 2. Set interval untuk mengambil data baru setiap 30 detik
        // (Sesuai saran Anda: 15 detik, tapi 30 lebih ramah server)
        setInterval(fetchSocialProof, 30000); // Ambil data baru setiap 30 detik

        // 3. Set interval untuk *menampilkan* popup berikusasasatnya
        // Jarak antar popup = 10 detik (interval) + 7 detik (tampil) = 17 detik
        setInterval(showNextSocialProof, 10000); // Cek untuk tampilkan tiap 10 detik
    }

    // ===============================================
    // === AKHIR LOGIKA SOCIAL PROOF               ===
    // ===============================================


    // =======================================================
    // === [FITUR BARU] LOGIKA CEK HADIAH GOSOK            ===
    // =======================================================
    
    // Cek hadiah hanya jika user login
    if (isLoggedIn && scratchPopup) {
        
        // Fungsi untuk menampilkan popup dan inisialisasi kartu gosok
        function showScratchPopup(reward) {
            // 1. Hancurkan instance kartu lama jika ada (untuk reset)
            if (activeScratchCard) {
                activeScratchCard.destroy();
                activeScratchCard = null;
            }
            
            // 2. Reset tampilan tombol
            scratchDoneButton.style.display = 'none';
            scratchCopyButton.style.display = 'none';
            scratchCopyButton.disabled = false;
            scratchCopyButton.textContent = 'Salin Kode';
            
            // 3. Isi data hadiah di belakang kartu
            scratchCardResultName.textContent = reward.display_name;
            if (reward.reward_type === 'promo') {
                scratchCardResultValue.textContent = reward.reward_value;
                scratchCopyButton.dataset.code = reward.reward_value; // Simpan kode di tombol
            } else {
                scratchCardResultValue.textContent = 'Hadiah akan dikirim langsung ke game!';
            }

            // 4. Buat kartu gosok baru
            // Pastikan elemen canvas-container sudah bersih
            scratchCardCanvasContainer.innerHTML = ''; // Hapus canvas lama
            
            // Buat elemen canvas baru
            const canvasEl = document.createElement('canvas');
            canvasEl.id = 'scratchCardCanvasElement'; // Beri ID baru
            canvasEl.width = 300;
            canvasEl.height = 150;
            scratchCardCanvasContainer.appendChild(canvasEl);

            activeScratchCard = new ScratchCard('#' + canvasEl.id, {
                scratchType: SCRATCH_TYPE.BRUSH,
                containerWidth: 300,
                containerHeight: 150,
                imageForwardSrc: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', // Placeholder
                
                // [PERBAIKAN] Ganti kode PHP dengan variabel JS
                imageBackgroundSrc: BASE_URL + 'assets/images/cover.png', 

                clearZoneRadius: 25,
                percentToFinish: 40, // Anggap selesai setelah 40% digosok
                callback: () => {
                    // Ini dipanggil saat 40% area sudah digosok
                    if (reward.reward_type === 'promo') {
                        scratchCopyButton.style.display = 'inline-block';
                    }
                    scratchDoneButton.style.display = 'inline-block';
                }
            });
            
            // Inisialisasi kartu gosok
            activeScratchCard.init().then(() => {
                // Setelah gambar cover berhasil di-load, tampilkan popup
                showPopup(scratchPopup);
            }).catch((error) => {
                console.error("Gagal inisialisasi kartu gosok:", error);
            });
        }

        // Fungsi untuk cek ke server apakah ada hadiah
        function checkUnclaimedRewards() {
            // [PERBAIKAN] Cek dulu apakah library ScratchCard SUDAH SIAP
            if (typeof ScratchCard === 'undefined' || typeof SCRATCH_TYPE === 'undefined') {
                // Jika belum siap, tunggu 500ms dan coba lagi.
                console.log("Menunggu library ScratchCard...");
                setTimeout(checkUnclaimedRewards, 500);
                return;
            }
            // Jika sudah siap, lanjutkan...
            console.log("Library ScratchCard siap, cek hadiah...");

            fetch(BASE_URL + 'index.php/rewards/check_unclaimed')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // [PERUBAHAN] Simpan ID unik hadiah yg kita menangkan
                        currentWonRewardId = data.won_reward_id; 
                        // Jika ada hadiah, tampilkan popup
                        showScratchPopup(data.reward);
                    } else if (data.status === 'nothing') {
                        // Tidak ada hadiah, diam saja
                    } else {
                        // Error (misal: not logged in), diam saja
                    }
                })
                .catch(err => {
                    console.error("Gagal cek hadiah:", err);
                });
        }
        
        // Langsung cek hadiah saat halaman dimuat
        checkUnclaimedRewards();
    }
    // ===============================================
    // === AKHIR LOGIKA KARTU GOSOK                ===
    // ===============================================

    // [BARU] Event Listeners untuk Popup Battlepass
    if (battlepassPopup) {
        bpBtnMinus.addEventListener('click', () => {
            let qty = parseInt(bpInputQty.value, 10);
            if (qty > 1) {
                bpInputQty.value = qty - 1;
                updateBattlepassPrice();
            }
        });

        bpBtnPlus.addEventListener('click', () => {
            let qty = parseInt(bpInputQty.value, 10);
            if (isNaN(qty)) qty = 0;
            bpInputQty.value = qty + 1;
            updateBattlepassPrice();
        });

        bpInputQty.addEventListener('change', () => { // 'change' lebih baik drpd 'input' untuk validasi akhir
            let qty = parseInt(bpInputQty.value, 10);
            if (isNaN(qty) || qty < 1) {
                bpInputQty.value = 1;
            }
            updateBattlepassPrice();
        });
        
        // [BARU] Tambah listener untuk 'input' agar harga update real-time saat diketik
        bpInputQty.addEventListener('input', () => {
            // Kita panggil updateBattlepassPrice, tapi fungsinya sudah pintar
            // untuk menangani nilai kosong atau non-angka saat user sedang mengetik
            updateBattlepassPrice();
        });

        // [MODIFIKASI] Event listeners untuk quick buttons dihapus

        bpAddToCartForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Tampilkan loading
            Swal.fire({
                title: 'Menambahkan ke Keranjang', text: 'Mohon tunggu...',
                showConfirmButton: false, allowOutsideClick: false,
                customClass: { popup: 'custom-loading-popup', loader: 'custom-loading-loader', title: 'custom-loading-title', htmlContainer: 'custom-loading-text' },
                backdrop: `rgba(27, 22, 13, 0.85)`,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData(bpAddToCartForm);

            fetch(bpAddToCartForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Tutup popup battlepass
                    hidePopup(battlepassPopup);
                    // Tampilkan notifikasi sukses
                    Swal.fire({
                        timer: 2000, title: 'Berhasil!', text: data.message,
                        showConfirmButton: false,
                        customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text', icon: 'custom-success-icon' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    }).then(() => {
                        // Redirect ke keranjang
                        window.location.href = BASE_URL + 'cart';
                    });
                } else {
                    // Tampilkan notifikasi error
                    Swal.fire({
                        title: 'Gagal', text: data.message,
                        showConfirmButton: true, confirmButtonText: 'Mengerti',
                        customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    title: 'Koneksi Gagal', text: 'Tidak dapat terhubung ke server.',
                    customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                    backdrop: `rgba(27, 22, 13, 0.85)`
                });
            });
        });
    }
    // [AKHIR BARU]

    if (bucksKagetPopup) {
        const handleBucksKagetInput = () => {
            updateBucksKagetPopupPrice();
        };

        bkInputTotalBucks.addEventListener('change', handleBucksKagetInput);
        bkInputTotalBucks.addEventListener('input', handleBucksKagetInput);
        bkInputRecipients.addEventListener('change', handleBucksKagetInput);
        bkInputRecipients.addEventListener('input', handleBucksKagetInput);

        bkAddToCartForm.addEventListener('submit', (e) => {
            e.preventDefault();

            Swal.fire({
                title: 'Menambahkan ke Keranjang', text: 'Mohon tunggu...',
                showConfirmButton: false, allowOutsideClick: false,
                customClass: { popup: 'custom-loading-popup', loader: 'custom-loading-loader', title: 'custom-loading-title', htmlContainer: 'custom-loading-text' },
                backdrop: `rgba(27, 22, 13, 0.85)`,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData(bkAddToCartForm);

            fetch(bkAddToCartForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    hidePopup(bucksKagetPopup);
                    Swal.fire({
                        timer: 1800, title: 'Berhasil!', text: data.message,
                        showConfirmButton: false,
                        customClass: { popup: 'custom-success-popup', title: 'custom-success-title', htmlContainer: 'custom-success-text', icon: 'custom-success-icon' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    }).then(() => {
                        window.location.href = data.redirect_url || (BASE_URL + 'cart');
                    });
                } else if (data.status === 'redirect') {
                    window.location.href = data.url;
                } else {
                    Swal.fire({
                        title: 'Gagal', text: data.message,
                        showConfirmButton: true, confirmButtonText: 'Mengerti',
                        customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    title: 'Koneksi Gagal', text: 'Tidak dapat terhubung ke server.',
                    customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                    backdrop: `rgba(27, 22, 13, 0.85)`
                });
            });
        });
    }

    // [FITUR BARU] Event Listeners untuk Popup Featured Product
    if (featuredPopup) {
        ftBtnMinus.addEventListener('click', () => {
            let qty = parseInt(ftInputQty.value, 10);
            if (qty > 1) {
                ftInputQty.value = qty - 1;
                updateFeaturedPrice();
            }
        });

        ftBtnPlus.addEventListener('click', () => {
            let qty = parseInt(ftInputQty.value, 10);
            if (isNaN(qty)) qty = 0;
            ftInputQty.value = qty + 1;
            updateFeaturedPrice();
        });

        ftInputQty.addEventListener('change', () => {
            let qty = parseInt(ftInputQty.value, 10);
            if (isNaN(qty) || qty < 1) {
                ftInputQty.value = 1;
            }
            updateFeaturedPrice();
        });
        
        ftInputQty.addEventListener('input', () => {
            updateFeaturedPrice();
        });

        // Submit form untuk Featured Product
        ftAddToCartForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Tampilkan loading (opsional, karena kita pakai toast)
            /*
            Swal.fire({
                title: 'Menambahkan...', text: 'Mohon tunggu...',
                showConfirmButton: false, allowOutsideClick: false,
                customClass: { popup: 'custom-loading-popup', loader: 'custom-loading-loader', title: 'custom-loading-title', htmlContainer: 'custom-loading-text' },
                backdrop: `rgba(27, 22, 13, 0.85)`,
                didOpen: () => { Swal.showLoading(); }
            });
            */
            // Ubah text tombol jadi loading
            const originalBtnText = ftSubmitButton.innerHTML;
            ftSubmitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            ftSubmitButton.style.pointerEvents = 'none';

            const formData = new FormData(ftAddToCartForm);

            fetch(ftAddToCartForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Tutup popup
                hidePopup(featuredPopup);
                // Reset tombol
                ftSubmitButton.innerHTML = originalBtnText;
                ftSubmitButton.style.pointerEvents = 'auto';

                if (data.status === 'success') {
                    // Tampilkan Cart Toast
                    showCartToast(data.cart_count, data.cart_total);
                } else if (data.status === 'redirect') {
                    window.location.href = data.url;
                } else {
                    Swal.fire({
                        title: 'Gagal', text: data.message,
                        customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                hidePopup(featuredPopup);
                ftSubmitButton.innerHTML = originalBtnText;
                ftSubmitButton.style.pointerEvents = 'auto';
                Swal.fire({
                    title: 'Koneksi Gagal', text: 'Tidak dapat terhubung ke server.',
                    customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text', icon: 'custom-error-icon', confirmButton: 'btn btn-primary' },
                    backdrop: `rgba(27, 22, 13, 0.85)`
                });
            });
        });
    }

    // ==========================================================
    // LOGIKA QUANTITY SELECTOR (DI CARD PRODUK)
    // ==========================================================
    document.querySelectorAll('.product-quantity-wrapper').forEach(wrapper => {
        const input = wrapper.querySelector('.qty-input');
        const btnMinus = wrapper.querySelector('.qty-minus');
        const btnPlus = wrapper.querySelector('.qty-plus');

        btnMinus.addEventListener('click', () => {
            let val = parseInt(input.value) || 1;
            if (val > 1) input.value = val - 1;
        });

        btnPlus.addEventListener('click', () => {
            let val = parseInt(input.value) || 1;
            input.value = val + 1;
        });
        
        input.addEventListener('change', () => {
            if (parseInt(input.value) < 1) input.value = 1;
        });
    });

    // ==========================================================
    // LOGIKA AJAX ADD TO CART (DENGAN TOAST NOTIFICATION)
    // ==========================================================
    const cartToast = document.getElementById('cartToast');
    const toastItemCount = document.getElementById('toastItemCount');
    const toastCartTotal = document.getElementById('toastCartTotal');
    const navCartCount = document.getElementById('navCartCount');

    const updateNavCartCount = (count) => {
        if (!navCartCount) return;

        const parsedCount = Number(count);
        navCartCount.textContent = Number.isFinite(parsedCount) ? String(parsedCount) : '0';
    };

    // Fungsi menampilkan Toast
    const showCartToast = (count, total) => {
        updateNavCartCount(count);
        if (!cartToast) return;
        
        // Update isi text
        if(toastItemCount) toastItemCount.textContent = count + " Items";
        if(toastCartTotal) toastCartTotal.textContent = total;

        // Tambah class show agar animasi turun
        cartToast.classList.add('show');

        // Auto hide setelah 3 detik
        setTimeout(() => { cartToast.classList.remove('show'); }, 3000);
    };

    // Event Delegation untuk tombol Add to Cart AJAX
    document.addEventListener('click', function(e) {
        // Cari tombol dengan class 'btn-ajax-add'
        const btn = e.target.closest('.btn-ajax-add');
        if (btn) {
            e.preventDefault();

            const productId = btn.dataset.productId;
            const category = btn.dataset.category;
            
            // Cari quantity input terdekat (di dalam card yang sama)
            const card = btn.closest('.product-card-v2');
            let quantity = 1;
            
            if (card) {
                const qtyInput = card.querySelector('.qty-input');
                if (qtyInput) {
                    quantity = parseInt(qtyInput.value) || 1;
                }
            }

            // Animasi Loading Tombol (Optional UX)
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;

            // Siapkan Data
            const formData = new FormData();
            formData.append('quantity', quantity);

            // Fetch ke Cart Controller
            fetch(BASE_URL + 'index.php/cart/add/' + productId + '/' + category, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Tandai sebagai AJAX untuk CI
                }
            })
            .then(response => response.json())
            .then(data => {
                // Kembalikan tombol
                btn.innerHTML = originalText;
                btn.disabled = false;

                if (data.status === 'success') {
                    // Tampilkan Toast Notification dari Atas
                    showCartToast(data.cart_count, data.cart_total);

                } else if (data.status === 'redirect') {
                    // Jika session habis/belum login
                    window.location.href = data.url;
                } else {
                    // Error lain
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message,
                        customClass: { popup: 'custom-error-popup', title: 'custom-error-title', htmlContainer: 'custom-error-text' },
                        backdrop: `rgba(27, 22, 13, 0.85)`
                    });
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerHTML = originalText;
                btn.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal menghubungi server.',
                    customClass: { popup: 'custom-error-popup' }
                });
            });
        }
    });

}); // Akhir dari DOMContentLoaded
