</div> </div> <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>

document.addEventListener('DOMContentLoaded', () => {
    const editorContainer = document.getElementById('perks-editor');
    const hiddenTextarea = document.getElementById('description_hidden');
    
    if (editorContainer && hiddenTextarea) {
        const perkCategories = ['One Time Purchase', 'Misc', 'Commands'];
        let initialData = {};
        try {
            initialData = JSON.parse(hiddenTextarea.value || '{}');
        } catch(e) { console.error('Invalid JSON in description'); }

        // Fungsi untuk membuat satu baris input perk
        function createPerkInput(value = '') {
            const perkInputWrapper = document.createElement('div');
            perkInputWrapper.className = 'perk-input-wrapper';
            perkInputWrapper.innerHTML = `
                <input type="text" class="perk-input" value="${value}">
                <button type="button" class="btn-remove-perk">&times;</button>
            `;
            perkInputWrapper.querySelector('.btn-remove-perk').addEventListener('click', () => {
                perkInputWrapper.remove();
            });
            return perkInputWrapper;
        }

        // Buat struktur editor
        perkCategories.forEach(category => {
            const categoryWrapper = document.createElement('div');
            categoryWrapper.className = 'perks-category';
            categoryWrapper.innerHTML = `<h3>${category}</h3>`;
            
            const perksContainer = document.createElement('div');
            perksContainer.className = 'perks-container';
            
            // Isi dengan data yang sudah ada
            if (initialData[category]) {
                initialData[category].forEach(perkValue => {
                    perksContainer.appendChild(createPerkInput(perkValue));
                });
            }

            const addPerkBtn = document.createElement('button');
            addPerkBtn.type = 'button';
            addPerkBtn.className = 'btn-add-perk';
            addPerkBtn.textContent = '+ Tambah Perk';
            addPerkBtn.addEventListener('click', () => {
                perksContainer.appendChild(createPerkInput());
            });

            categoryWrapper.appendChild(perksContainer);
            categoryWrapper.appendChild(addPerkBtn);
            editorContainer.appendChild(categoryWrapper);
        });

        // Saat form di-submit, kumpulkan data dan masukkan ke textarea tersembunyi
        const form = editorContainer.closest('form');
        form.addEventListener('submit', () => {
            const finalData = {};
            document.querySelectorAll('.perks-category').forEach(categoryWrapper => {
                const categoryName = categoryWrapper.querySelector('h3').textContent;
                const perks = [];
                categoryWrapper.querySelectorAll('.perk-input').forEach(input => {
                    if(input.value.trim() !== '') {
                        perks.push(input.value.trim());
                    }
                });
                if (perks.length > 0) {
                    finalData[categoryName] = perks;
                }
            });
            hiddenTextarea.value = JSON.stringify(finalData);
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const sidebar = document.getElementById('adminSidebar');
    const toggleButton = document.getElementById('adminSidebarToggle');
    const closeButton = document.getElementById('adminSidebarClose');
    const overlay = document.getElementById('adminSidebarOverlay');

    if (!body || !sidebar || !toggleButton || !overlay) {
        return;
    }

    const isMobileLayout = () => window.innerWidth <= 992;
    const setOverlayState = isOpen => {
        overlay.hidden = !isOpen;
        toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    const closeSidebar = () => {
        body.classList.remove('admin-nav-open');
        setOverlayState(false);
    };

    const openSidebar = () => {
        if (!isMobileLayout()) {
            return;
        }

        body.classList.add('admin-nav-open');
        setOverlayState(true);
    };

    const toggleSidebar = () => {
        if (body.classList.contains('admin-nav-open')) {
            closeSidebar();
            return;
        }

        openSidebar();
    };

    toggleButton.addEventListener('click', toggleSidebar);

    if (closeButton) {
        closeButton.addEventListener('click', closeSidebar);
    }

    overlay.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('.sidebar-nav a, .btn-logout').forEach(link => {
        link.addEventListener('click', () => {
            if (isMobileLayout()) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    window.addEventListener('resize', () => {
        if (!isMobileLayout()) {
            closeSidebar();
        }
    });

    setOverlayState(false);
});

    // Inisialisasi flatpickr pada semua elemen dengan class .flatpickr-datetime
    flatpickr(".flatpickr-datetime", {
        enableTime: true, // Mengaktifkan pilihan jam dan menit
        dateFormat: "Y-m-d H:i", // Format yang dikirim ke server (sesuai format database MySQL)
        altInput: true, // Menampilkan format yang lebih ramah di mata pengguna
        altFormat: "F j, Y at H:i", // Format yang ditampilkan (cth: June 24, 2025 at 10:30)
    });
</script>

</body>
</html>
