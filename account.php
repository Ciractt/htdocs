<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();

// Get account stats
$stmt = $pdo->prepare("SELECT COUNT(*) as deck_count FROM decks WHERE user_id = ?");
$stmt->execute([$user['id']]);
$deck_count = $stmt->fetch()['deck_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as collection_count FROM user_collections WHERE user_id = ?");
$stmt->execute([$user['id']]);
$collection_count = $stmt->fetch()['collection_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/style-fixes.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .settings-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .settings-section h2 {
            margin: 0 0 1.5rem 0;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 1rem;
        }

        .account-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #666;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .danger-zone {
            border: 2px solid #e74c3c;
            background: #fff5f5;
        }

        .danger-zone h2 {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="settings-container">
            <h1>Account Settings</h1>

            <!-- Account Overview -->
            <div class="settings-section">
                <h2>Account Overview</h2>
                <div class="account-stats">
                    <div class="stat-box">
                        <span class="stat-value"><?php echo $deck_count; ?></span>
                        <span class="stat-label">Decks Created</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-value"><?php echo $collection_count; ?></span>
                        <span class="stat-label">Cards Owned</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-value">
                            <?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?>
                        </span>
                        <span class="stat-label">Last Login</span>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="settings-section">
                <h2>Account Information</h2>
                <div id="accountInfoMessage" class="message hidden"></div>

                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>

                <button class="btn btn-primary" onclick="showChangeEmailModal()" style="margin-top: var(--spacing-lg);">
                    Change Email
                </button>
            </div>

            <!-- Change Password -->
            <div class="settings-section">
                <h2>Change Password</h2>
                <div id="passwordMessage" class="message hidden"></div>

                <form id="changePasswordForm" class="auth-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small>At least 8 characters, 1 number, and 1 symbol</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>

            <!-- Danger Zone -->
            <div class="settings-section danger-zone">
                <h2>⚠️ Danger Zone</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-lg);">
                    Once you delete your account, there is no going back. Please be certain.
                </p>
                <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                    Delete Account
                </button>
            </div>
        </div>
    </main>

    <!-- Change Email Modal -->
    <div id="changeEmailModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeChangeEmailModal()">×</span>
            <h2>Change Email Address</h2>
            <div id="emailModalMessage" class="message hidden"></div>

            <form id="changeEmailForm" class="auth-form">
                <div class="form-group">
                    <label for="new_email">New Email Address</label>
                    <input type="email" id="new_email" name="new_email" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password_email">Confirm Password</label>
                    <input type="password" id="confirm_password_email" name="confirm_password" required>
                    <small>Enter your current password to confirm</small>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Change Email</button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Change Password
        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'change_password');

            try {
                const response = await fetch('api/account.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                showMessage('passwordMessage', data.message, data.success ? 'success' : 'error');

                if (data.success) {
                    this.reset();
                }
            } catch (error) {
                showMessage('passwordMessage', 'An error occurred', 'error');
            }
        });

        // Change Email Modal
        function showChangeEmailModal() {
            document.getElementById('changeEmailModal').classList.add('active');
        }

        function closeChangeEmailModal() {
            document.getElementById('changeEmailModal').classList.remove('active');
        }

        document.getElementById('changeEmailForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'change_email');

            try {
                const response = await fetch('api/account.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                showMessage('emailModalMessage', data.message, data.success ? 'success' : 'error');

                if (data.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            } catch (error) {
                showMessage('emailModalMessage', 'An error occurred', 'error');
            }
        });

        // Delete Account
        function confirmDeleteAccount() {
            const confirmed = confirm('Are you absolutely sure you want to delete your account?\n\nThis will permanently delete:\n- Your account\n- All your decks\n- Your collection data\n\nThis action CANNOT be undone!');

            if (confirmed) {
                const doubleCheck = prompt('Type "DELETE" in capital letters to confirm:');

                if (doubleCheck === 'DELETE') {
                    deleteAccount();
                } else {
                    alert('Account deletion cancelled.');
                }
            }
        }

        async function deleteAccount() {
            const formData = new FormData();
            formData.append('action', 'delete_account');

            try {
                const response = await fetch('api/account.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.href = 'index.php';
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('An error occurred while deleting your account');
            }
        }

        // Helper function
        function showMessage(elementId, message, type) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.className = 'message ' + type;
            element.classList.remove('hidden');

            setTimeout(() => {
                element.classList.add('hidden');
            }, 5000);
        }

        // Close modal on outside click
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('changeEmailModal');
            if (e.target === modal) {
                closeChangeEmailModal();
            }
        });
    </script>
</body>
</html>
