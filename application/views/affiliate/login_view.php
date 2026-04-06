<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Login</title>
        <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">

    <link rel="stylesheet" href="<?= base_url('assets/css/affiliate.css?v=1.0.3'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="affiliate-login-page">
    <div class="login-container">
        <div class="login-header">
            <h2>AFFILIATE PORTAL</h2>
            <p>LOGIN TO YOUR ACCOUNT</p>
        </div>
        
        <?php if($this->session->flashdata('error')): ?>
            <div class="error-message"><?= $this->session->flashdata('error'); ?></div>
        <?php endif; ?>

        <form action="<?= base_url('affiliate/auth/process_login'); ?>" method="post">
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
</body>
</html>