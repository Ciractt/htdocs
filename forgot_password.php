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
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/style-fixes.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="auth-container">
            <h1>Forgot Password</h1>

            <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                Enter your email address and we'll send you instructions to reset your password.
            </p>

            <div id="message" class="message hidden"></div>

            <form id="forgotPasswordForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
            </form>

            <p class="auth-link">
                <a href="login.php">← Back to Login</a>
            </p>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const messageDiv = document.getElementById('message');
            const formData = new FormData(this);
            formData.append('action', 'forgot_password');

            try {
                const response = await fetch('api/password_reset.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                messageDiv.textContent = data.message;
                messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                messageDiv.classList.remove('hidden');

                if (data.success) {
                    this.reset();
                }
            } catch (error) {
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.className = 'message error';
                messageDiv.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
