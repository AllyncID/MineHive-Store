<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | Mine Hive</title>

    <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">    
    <link rel="stylesheet" href="<?= base_url('assets/css/affiliate.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="affiliate-login-page">
    <div class="login-container">
        <div class="login-header">
            <h2>ADMIN PANEL</h2>
            <p>LOGIN TO YOUR ACCOUNT</p>
        </div>
        
        <?php if($this->session->flashdata('error')): ?>
            <div class="error-message"><?= $this->session->flashdata('error'); ?></div>
        <?php endif; ?>

        <form action="<?= base_url('admin/auth/process_login'); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required placeholder="Allync">
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="********">
                </div>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    <?php if ($this->session->flashdata('success')): ?>
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?= $this->session->flashdata('success'); ?>', showConfirmButton: false, timer: 2000 });
    <?php elseif ($this->session->flashdata('error') && !isset($_POST['username'])): // Hanya tampilkan jika bukan dari submit form login ?>
        Swal.fire({ icon: 'error', title: 'Oops...', text: '<?= $this->session->flashdata('error'); ?>' });
    <?php endif; ?>
    </script>
</body>
</html>