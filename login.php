<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/style-fixes.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="auth-container">
            <h1>Login</h1>

            <div id="message" class="message hidden"></div>

            <form id="loginForm" class="auth-form">
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </form>

            <p class="auth-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>

            <p class="auth-link">
                <a href="forgot_password.php">Forgot your password?</a>
            </p>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/auth.js"></script>
</body>
</html>
