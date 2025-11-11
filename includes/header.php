<?php
if (!isset($user)) {
    $user = getCurrentUser();
}
?>
<header>
    <div class="container">
        <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>

        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="cards2.php">Cards</a></li>
                <li><a href="community_decks.php">Community Decks</a></li>
                <?php if ($user): ?>
                    <li><a href="collection.php">Collection</a></li>
                    <li><a href="deck_builder.php">Deck Builder</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="user-info">
            <?php if ($user): ?>
                <span>Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <button onclick="logout()" class="btn btn-secondary btn-small">Logout</button>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary btn-small">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>
