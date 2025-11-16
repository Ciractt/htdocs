<?php
if (!isset($user)) {
    $user = getCurrentUser();
}

// Get user avatar or use default
$avatarUrl = $user && !empty($user['avatar_url'])
    ? htmlspecialchars($user['avatar_url'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['username'] ?? 'User') . '&background=667eea&color=fff&size=128';

// You can set a logo URL here or in config
$logoUrl = SITE_URL . '/assets/logo.png'; // Update this path to your actual logo
?>
<header>
    <div class="header-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <!-- Uncomment when you have a logo -->
            <!-- <img src="<?php echo $logoUrl; ?>" alt="Logo" class="logo-image"> -->
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
            <span>☰</span>
        </button>

        <!-- Main Navigation -->
        <nav id="mainNav">
            <ul>
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    Home
                </a></li>

                <!-- Cards Dropdown -->
                <li class="nav-item-dropdown">
                    <a href="cards2.php" class="dropdown-trigger <?php echo in_array(basename($_SERVER['PHP_SELF']), ['cards.php', 'cards2.php', 'alts.php']) ? 'active' : ''; ?>">
                        Cards
                        <span class="dropdown-icon">▼</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="cards2.php">Card Database</a>
                        <a href="alts.php">Alt Arts</a>
                    </div>
                </li>

                <!-- Decks Dropdown -->
                <li class="nav-item-dropdown">
                    <a href="community_decks.php" class="dropdown-trigger <?php echo in_array(basename($_SERVER['PHP_SELF']), ['community_decks.php', 'view_deck.php']) ? 'active' : ''; ?>">
                        Decks
                        <span class="dropdown-icon">▼</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="community_decks.php">Community Decks</a>
                        <?php if ($user): ?>
                            <div class="dropdown-divider"></div>
                            <a href="deck_builder.php">Deck Builder</a>
                        <?php endif; ?>
                    </div>
                </li>

                <?php if ($user): ?>
                    <li><a href="collection.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'collection.php' ? 'active' : ''; ?>">
                        Collection
                    </a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- User Info Section -->
        <div class="user-info">
            <?php if ($user): ?>
                <div class="user-dropdown">
                    <div class="user-profile">
                        <img src="<?php echo $avatarUrl; ?>"
                             alt="<?php echo htmlspecialchars($user['username']); ?>"
                             class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>

                    <div class="user-dropdown-menu">
                        <div class="user-dropdown-header">
                            <div class="user-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="user-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>

                        <a href="account.php">
                            <span>👤</span>
                            Account Settings
                        </a>
                        <a href="collection.php">
                            <span>📚</span>
                            My Collection
                        </a>
                        <a href="deck_builder.php">
                            <span>🎴</span>
                            Deck Builder
                        </a>

                        <div class="dropdown-divider"></div>

                        <button onclick="logout()" class="logout-btn">
                            <span>🚪</span>
                            Logout
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary btn-small">Login</a>
                <a href="register.php" class="btn btn-primary btn-small">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const mainNav = document.getElementById('mainNav');

    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mainNav.contains(e.target) && !mobileToggle.contains(e.target)) {
                mainNav.classList.remove('active');
            }
        });
    }
});
</script>
