document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('bucksKagetClaimPopup');
    const openButton = document.getElementById('openClaimPopupBtn');
    const quickClaimButton = document.getElementById('quickClaimBtn');
    const closeButton = popup ? popup.querySelector('.popup-close') : null;
    const form = document.getElementById('bucksKagetClaimForm');
    const usernameInput = document.getElementById('bucksKagetUsernameInput');
    const avatarPreview = document.getElementById('bucksKagetAvatarPreview');
    const status = window.BUCKS_KAGET?.status || 'inactive';
    const claimUrl = window.BUCKS_KAGET?.claimUrl || '';
    const viewer = window.BUCKS_KAGET?.viewer || {};

    const toggleBodyState = isOpen => {
        document.body.classList.toggle('bucks-kaget-modal-open', isOpen);
    };

    const showPopup = () => {
        if (popup) {
            popup.classList.add('show');
            toggleBodyState(true);
        }
    };

    const hidePopup = () => {
        if (popup) {
            popup.classList.remove('show');
            toggleBodyState(false);
        }
    };

    const showAlert = (title, text, variant = 'info') => {
        const normalizedVariant = ['success', 'error', 'warning', 'info'].includes(variant) ? variant : 'info';
        const customClassMap = {
            success: {
                popup: 'custom-success-popup',
                title: 'custom-success-title',
                htmlContainer: 'custom-success-text',
                icon: 'custom-success-icon'
            },
            error: {
                popup: 'custom-error-popup',
                title: 'custom-error-title',
                htmlContainer: 'custom-error-text',
                icon: 'custom-error-icon',
                confirmButton: 'btn btn-secondary'
            },
            warning: {
                popup: 'custom-warning-popup',
                title: 'custom-warning-title',
                htmlContainer: 'custom-warning-text',
                icon: 'custom-warning-icon',
                confirmButton: 'btn btn-secondary'
            },
            info: {
                popup: 'custom-info-popup',
                title: 'custom-info-title',
                htmlContainer: 'custom-info-text',
                confirmButton: 'btn btn-secondary'
            }
        };

        const swalConfig = {
            title,
            text,
            customClass: customClassMap[normalizedVariant],
            confirmButtonText: 'OK',
            background: '#291E12',
            color: '#FFFFFF',
            backdrop: 'rgba(27, 22, 13, 0.85)'
        };

        if (normalizedVariant !== 'info') {
            swalConfig.icon = normalizedVariant;
        }

        if (normalizedVariant === 'warning') {
            swalConfig.iconColor = '#D33';
        }

        return Swal.fire(swalConfig);
    };

    const updateAvatarPreview = value => {
        if (!avatarPreview) {
            return;
        }

        const username = String(value || '').trim();
        let avatarUrl = 'https://crafthead.net/helm/c06f89064c8a49119c29ea1dbd1aab82/64';

        if (username && username.charAt(0) !== '.') {
            avatarUrl = `https://crafthead.net/helm/${encodeURIComponent(username)}/64`;
        }

        avatarPreview.src = avatarUrl;
    };

    const handleClaimResponse = data => {
        if (data.status === 'success') {
            hidePopup();
            showAlert('Bucks Kaget', data.message, 'success');

            if (openButton) {
                openButton.disabled = true;
                openButton.textContent = 'Claim Berhasil';
            }
            if (quickClaimButton) {
                quickClaimButton.disabled = true;
                quickClaimButton.textContent = 'Claim Berhasil';
            }
            return;
        }

        if (data.status === 'player_not_found') {
            showAlert('Nickname Tidak Ditemukan', data.message, 'error');
            return;
        }

        hidePopup();

        let alertTitle = window.BUCKS_KAGET?.statusLabel || 'Bucks Kaget';
        let alertVariant = 'info';

        if (data.status === 'already_claimed') {
            alertTitle = 'Sudah Pernah Claim';
            alertVariant = 'warning';
        } else if (['finished', 'expired', 'inactive'].includes(data.status)) {
            alertTitle = 'Bucks Kaget';
            alertVariant = 'warning';
        } else {
            alertTitle = 'Claim Gagal';
            alertVariant = 'error';
        }

        showAlert(alertTitle, data.message || 'Claim gagal diproses.', alertVariant);

        if (data.status === 'already_claimed') {
            if (openButton) {
                openButton.disabled = true;
                openButton.textContent = 'Sudah Pernah Claim';
            }
            if (quickClaimButton) {
                quickClaimButton.disabled = true;
                quickClaimButton.textContent = 'Sudah Pernah Claim';
            }
        }

        if (['finished', 'expired', 'inactive'].includes(data.status)) {
            if (openButton) {
                openButton.disabled = true;
            }
            if (quickClaimButton) {
                quickClaimButton.disabled = true;
            }
        }
    };

    const submitClaim = formData => {
        Swal.fire({
            title: 'Memproses Claim',
            text: 'Sebentar ya, kami sedang cek nickname kamu...',
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: {
                popup: 'custom-loading-popup',
                title: 'custom-loading-title',
                htmlContainer: 'custom-loading-text'
            },
            backdrop: 'rgba(27, 22, 13, 0.85)',
            didOpen: () => Swal.showLoading()
        });

        return fetch(claimUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(response => response.json())
            .then(handleClaimResponse)
            .catch(() => {
                showAlert('Koneksi Gagal', 'Tidak dapat terhubung ke server. Coba lagi sebentar.', 'error');
            });
    };

    if (usernameInput && avatarPreview) {
        let debounceTimer;
        usernameInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                updateAvatarPreview(this.value);
            }, 250);
        });

        if (viewer.username) {
            updateAvatarPreview(viewer.username);
        }
    }

    if (openButton) {
        openButton.addEventListener('click', showPopup);
    }

    if (quickClaimButton) {
        quickClaimButton.addEventListener('click', () => {
            const username = String(quickClaimButton.dataset.username || viewer.username || '').trim();
            const platform = String(quickClaimButton.dataset.platform || viewer.platform || '').trim();

            if (!username || !platform) {
                showPopup();
                return;
            }

            const formData = new FormData();
            formData.append('username', username);
            formData.append('platform', platform);
            submitClaim(formData);
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', hidePopup);
    }

    if (popup) {
        popup.addEventListener('click', event => {
            if (event.target === popup) {
                hidePopup();
            }
        });
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && popup && popup.classList.contains('show')) {
            hidePopup();
        }
    });

    if (status !== 'active') {
        showAlert('Bucks Kaget', window.BUCKS_KAGET?.statusMessage || 'Link ini tidak bisa diclaim lagi.', 'warning');
    }

    if (!form || !claimUrl) {
        return;
    }

    form.addEventListener('submit', event => {
        event.preventDefault();

        const submitter = event.submitter;
        const formData = new FormData(form);

        if (submitter && submitter.name === 'platform' && !formData.has('platform')) {
            formData.append('platform', submitter.value);
        }

        if (!formData.get('username') || !formData.get('platform')) {
            showAlert('Bucks Kaget', 'Nickname dan platform wajib diisi.', 'error');
            return;
        }

        submitClaim(formData);
    });
});
