<div class="popup-overlay" id="loginPopup">
    <div class="popup-container login-popup-container">
        <span class="popup-close">&times;</span>

        <div class="popup-header login-popup-header">
            <h2 class="popup-title">MASUKKAN USERNAME</h2>
            <p class="popup-subtitle">PILIH PLATFORM ANDA</p>
        </div>

        <div class="popup-body">
            <?= form_open('auth/login', ['id' => 'loginForm']); ?>
                
                <div class="form-group">
                    <div class="username-input-container">
                        <img src="https://crafthead.net/helm/c06f89064c8a49119c29ea1dbd1aab82/64" alt="Avatar Preview" id="login-avatar-preview">
                        <input type="text" name="username" id="username-input-field" class="form-control-custom" placeholder="Masukkan Username" required autocomplete="off">
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
                
            <?= form_close(); ?>
        </div>
    </div>
</div>