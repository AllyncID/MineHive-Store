<?php
// I'll add a style block to be sure the styling is applied, since this view is loaded within a template.
// These styles are inspired by the admin/login_view.php and affiliate.css
?>
<style>
    .helper-login-page {
        /* Assuming the template doesn't have a background set, or this will override it */
        background-color: #1a1a1a; /* Dark background from admin login */
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh; /* Take up most of the viewport height */
        padding: 20px;
        font-family: 'Poppins', sans-serif;
    }
    .helper-login-container {
        background-color: #2c2c2c;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        width: 100%;
        max-width: 400px;
        border: 1px solid #444;
        text-align: center;
    }
    .helper-login-header h2 {
        color: #fff;
        font-weight: 600;
        font-size: 1.5rem;
        margin: 0 0 5px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .helper-login-header p {
        color: #888;
        font-size: 0.9rem;
        margin: 0 0 30px 0;
    }
    .helper-form-group {
        margin-bottom: 20px;
        text-align: left;
    }
    .helper-form-group label {
        display: block;
        color: #aaa;
        margin-bottom: 8px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    .helper-input-wrapper {
        position: relative;
    }
    .helper-input-wrapper .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }
    .helper-input-wrapper input {
        width: 100%;
        background-color: #1a1a1a;
        border: 1px solid #444;
        border-radius: 5px;
        padding: 12px 12px 12px 40px; /* Padding for icon */
        color: #fff;
        font-size: 1rem;
    }
    .helper-btn-login {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 5px;
        background-color: #b87841; /* Color from previous login button */
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s;
        text-transform: uppercase;
    }
    .helper-btn-login:hover {
        background-color: #a06831;
    }
    .helper-error-message {
        background-color: #c52e2e33;
        color: #ff8a8a;
        border: 1px solid #c52e2e;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
        text-align: center;
    }
</style>

<div class="helper-login-page">
    <div class="helper-login-container">
        <div class="helper-login-header">
            <h2>STAFF ACCESS</h2>
            <p>MASUKKAN KODE AKSES ANDA</p>
        </div>
        
        <?php if($this->session->flashdata('error')): ?>
            <div class="helper-error-message"><?= $this->session->flashdata('error'); ?></div>
        <?php endif; ?>

        <form action="<?= base_url('helper/auth'); ?>" method="post">
            <div class="helper-form-group">
                <label for="password">Kode Akses</label>
                <div class="helper-input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" required placeholder="********" class="form-control">
                </div>
            </div>
            <button type="submit" class="helper-btn-login">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 25px;">
            <a href="<?= base_url(); ?>" style="color: #666; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: 0.3s;">
                &larr; Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if ($this->session->flashdata('success')): ?>
    Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?= $this->session->flashdata('success'); ?>', showConfirmButton: false, timer: 2000 });
<?php elseif ($this->session->flashdata('error')): ?>
    Swal.fire({ icon: 'error', title: 'Oops...', text: '<?= $this->session->flashdata('error'); ?>' });
<?php endif; ?>
</script>