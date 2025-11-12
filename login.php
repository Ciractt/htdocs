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
    <link rel="stylesheet" href="css/style-dark.css">
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

            <div class="divider">OR</div>

            <form id="riotLoginForm" class="auth-form">
                <input type="hidden" name="action" value="riot_login">

                <div class="form-group">
                    <label for="riot_id">Riot ID</label>
                    <input type="text" id="riot_id" name="riot_id" placeholder="Name#TAG" required>
                    <small>Enter your Riot ID (e.g., PlayerName#NA1)</small>
                </div>

                <button type="submit" class="btn btn-secondary btn-full">Login with Riot</button>
            </form>

            <p class="auth-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/auth.js"></script>
</body>
</html>
