document.addEventListener('DOMContentLoaded', () => {
    const config = window.LUCKY_SPIN || {};
    const modal = document.getElementById('luckySpinModal');
    const openButton = document.getElementById('openSpinModalBtn');
    const closeButton = document.getElementById('closeLuckySpinModal');
    const form = document.getElementById('luckySpinForm');
    const usernameInput = document.getElementById('luckySpinUsername');
    const wheel = document.getElementById('luckySpinWheel');
    const labelsLayer = document.getElementById('luckySpinLabels');
    const resultCard = document.getElementById('luckySpinResultCard');
    const resultLabel = document.getElementById('luckySpinResultLabel');
    const resultMeta = document.getElementById('luckySpinResultMeta');
    const remainingPlayerSlotsText = document.getElementById('remainingPlayerSlotsText');

    const rawRewards = Array.isArray(config.rewards) ? config.rewards : [];
    const wheelRewards = buildVisualRewards(rawRewards);
    const palette = ['#9d6034', '#73472d', '#c2873f', '#8c5530', '#b97537', '#6d442d', '#d29c4e', '#8a4f2a'];
    let currentRotation = 0;
    let spinTimer = null;
    let isSpinning = false;
    let lastProfile = {
        username: '',
        platform: ''
    };

    const showAlert = (title, text, icon = 'info') => {
        return Swal.fire({
            title,
            text,
            icon,
            confirmButtonText: 'OK',
            confirmButtonColor: '#9f6438',
            background: '#25170f',
            color: '#FFFFFF'
        });
    };

    function buildVisualRewards(rewards) {
        if (!rewards.length) {
            return Array.from({ length: 8 }, (_, index) => ({
                id: 'placeholder-' + index,
                label: 'Coming Soon',
                reward_type: 'zonk'
            }));
        }

        const minSegments = rewards.length >= 6 ? rewards.length : 6;
        const visualRewards = [];
        let pointer = 0;

        while (visualRewards.length < minSegments) {
            visualRewards.push(rewards[pointer % rewards.length]);
            pointer++;
        }

        return visualRewards;
    }

    function getSegmentAngle() {
        return 360 / Math.max(1, wheelRewards.length);
    }

    function getWheelStartOffset() {
        return -(getSegmentAngle() / 2);
    }

    function getRewardColor(reward, index) {
        if (reward && reward.reward_type === 'zonk') {
            return '#b2353f';
        }

        if (reward && reward.reward_type === 'product') {
            return index % 2 === 0 ? '#8c5530' : '#a76738';
        }

        return palette[index % palette.length];
    }

    function renderWheel() {
        if (!wheel || !labelsLayer || !wheelRewards.length) {
            return;
        }

        const segmentAngle = getSegmentAngle();
        const segmentOffset = getWheelStartOffset();
        const wheelRadius = Math.max(92, Math.round((wheel.offsetWidth || 320) * 0.27));
        const gradientStops = wheelRewards.map((reward, index) => {
            const start = (segmentOffset + (index * segmentAngle)).toFixed(2);
            const end = (segmentOffset + ((index + 1) * segmentAngle)).toFixed(2);
            const color = getRewardColor(reward, index);
            return `${color} ${start}deg ${end}deg`;
        });

        wheel.style.background = `conic-gradient(from 0deg, ${gradientStops.join(', ')})`;
        labelsLayer.innerHTML = '';

        wheelRewards.forEach((reward, index) => {
            const centerAngle = segmentOffset + (index * segmentAngle) + (segmentAngle / 2);
            const label = document.createElement('div');
            label.className = 'lucky-wheel-segment-label';
            label.textContent = reward.label || 'Reward';
            label.style.transform = `translate(-50%, -50%) rotate(${centerAngle}deg) translateY(-${wheelRadius}px) rotate(${-centerAngle}deg)`;
            if (reward && reward.reward_type === 'zonk') {
                label.classList.add('is-zonk');
            }
            labelsLayer.appendChild(label);
        });
    }

    function setModalState(isOpen) {
        if (!modal) return;
        modal.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';

        if (isOpen && usernameInput) {
            usernameInput.value = lastProfile.username || usernameInput.value || '';
            usernameInput.focus();
        }
    }

    function updateResultCard(reward, message) {
        if (!resultLabel || !resultMeta || !reward) {
            return;
        }

        if (resultCard) {
            resultCard.classList.remove('is-bucks', 'is-product', 'is-zonk');
            resultCard.classList.add(`is-${reward.reward_type || 'product'}`);
        }

        resultLabel.textContent = reward.label || 'Hadiah berhasil didapat';
        resultMeta.textContent = message || describeReward(reward);
    }

    function describeReward(reward) {
        if (!reward) {
            return 'Spin selesai.';
        }

        if (reward.reward_type === 'bucks') {
            const amount = Number(reward.bucks_amount || 0);
            return `Hadiah ${amount.toLocaleString('id-ID')} Bucks langsung dikirim ke nickname kamu.`;
        }

        if (reward.reward_type === 'product') {
            return 'Hadiah product sedang diproses dan dikirim ke nickname kamu.';
        }

        return 'Belum beruntung kali ini. Kalau slot masih ada, kamu bisa coba lagi.';
    }

    function updateActionButton(response) {
        if (!openButton) {
            return;
        }

        if (config.status !== 'active') {
            openButton.disabled = true;
            openButton.textContent = 'Tidak Bisa Spin';
            return;
        }

        if (response && typeof response.remaining_player_slots === 'number' && remainingPlayerSlotsText) {
            remainingPlayerSlotsText.textContent = `${response.remaining_player_slots} orang`;
        }

        if (response && response.can_spin_again) {
            openButton.disabled = false;
            openButton.textContent = 'Spin Lagi';
            return;
        }

        if (response && typeof response.remaining_player_slots === 'number' && response.remaining_player_slots <= 0) {
            openButton.disabled = true;
            openButton.textContent = 'Kuota Peserta Habis';
            return;
        }

        openButton.disabled = false;
        openButton.textContent = 'Masukkan Nickname & Mulai Spin';
    }

    function spinToReward(reward, onComplete) {
        if (!wheel || !reward) {
            if (typeof onComplete === 'function') {
                onComplete();
            }
            return;
        }

        const matches = [];
        const rewardId = String(reward.id);
        wheelRewards.forEach((item, index) => {
            if (String(item.id) === rewardId) {
                matches.push(index);
            }
        });

        const targetIndex = matches.length
            ? matches[Math.floor(Math.random() * matches.length)]
            : 0;
        const segmentAngle = getSegmentAngle();
        const segmentOffset = getWheelStartOffset();
        const targetCenter = segmentOffset + (targetIndex * segmentAngle) + (segmentAngle / 2);
        const restingAngle = (360 - targetCenter + 360) % 360;
        const currentModulo = ((currentRotation % 360) + 360) % 360;
        const offset = (restingAngle - currentModulo + 360) % 360;
        const extraTurns = 2160 + Math.floor(Math.random() * 720);
        const finalRotation = currentRotation + extraTurns + offset;

        isSpinning = true;
        wheel.style.transform = `rotate(${finalRotation}deg)`;
        currentRotation = finalRotation;

        clearTimeout(spinTimer);
        spinTimer = window.setTimeout(() => {
            isSpinning = false;
            if (typeof onComplete === 'function') {
                onComplete();
            }
        }, 5300);
    }

    if (openButton && config.status === 'active') {
        openButton.addEventListener('click', () => {
            if (!isSpinning) {
                setModalState(true);
            }
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', () => setModalState(false));
    }

    if (modal) {
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                setModalState(false);
            }
        });
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            setModalState(false);
        }
    });

    if (form) {
        form.addEventListener('submit', event => {
            event.preventDefault();

            if (isSpinning) {
                return;
            }

            const submitter = event.submitter;
            const formData = new FormData(form);

            if (submitter && submitter.name === 'platform' && !formData.has('platform')) {
                formData.append('platform', submitter.value);
            }

            const username = String(formData.get('username') || '').trim();
            const platform = String(formData.get('platform') || '').trim();

            if (!username || !platform) {
                showAlert('Lucky Spin', 'Nickname dan platform wajib diisi.', 'error');
                return;
            }

            lastProfile = { username, platform };

            Swal.fire({
                title: 'Memproses Spin',
                text: 'Sebentar ya, nickname kamu sedang dicek...',
                allowOutsideClick: false,
                showConfirmButton: false,
                background: '#25170f',
                color: '#FFFFFF',
                didOpen: () => Swal.showLoading()
            });

            fetch(config.playUrl || '', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.status === 'success') {
                        setModalState(false);
                        updateActionButton(data);
                        spinToReward(data.reward, () => {
                            updateResultCard(data.reward, data.message);
                            showAlert('Lucky Spin', data.message || describeReward(data.reward), data.reward && data.reward.reward_type === 'zonk' ? 'info' : 'success');
                        });
                        return;
                    }

                    if (['inactive', 'expired', 'finished'].includes(data.status) && openButton) {
                        config.status = data.status;
                        openButton.disabled = true;
                        openButton.textContent = 'Tidak Bisa Spin';
                    }

                    showAlert(config.statusLabel || 'Lucky Spin', data.message || 'Spin gagal diproses.', data.status === 'player_not_found' ? 'error' : 'warning');
                })
                .catch(() => {
                    Swal.close();
                    showAlert('Koneksi Gagal', 'Tidak dapat terhubung ke server. Coba lagi sebentar.', 'error');
                });
        });
    }

    renderWheel();
    updateActionButton();
    window.addEventListener('resize', renderWheel);
});
