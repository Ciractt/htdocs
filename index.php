<?php
require_once 'config.php';

$pdo = getDB();

// Get featured cards
$stmt = $pdo->query("SELECT * FROM cards WHERE is_featured = TRUE ORDER BY RAND() LIMIT 6");
$featured_cards = $stmt->fetchAll();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Home</title>

    <!-- Single CSS File -->
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Welcome to <?php echo SITE_NAME; ?></h1>
                <p>Build your collection, craft powerful decks, and master the game.</p>
                <?php if (!$user): ?>
                    <div class="hero-actions">
                        <a href="register.php" class="btn btn-primary btn-large">
                            Get Started Free
                        </a>
                        <a href="cards2.php" class="btn btn-secondary btn-large">
                            Browse Cards
                        </a>
                    </div>
                <?php else: ?>
                    <div class="hero-actions">
                        <a href="deck_builder.php" class="btn btn-primary btn-large">
                            Build a Deck
                        </a>
                        <a href="collection.php" class="btn btn-secondary btn-large">
                            View Collection
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>


        <!-- Featured Cards Section -->
        <?php if (!empty($featured_cards)): ?>
        <section class="featured-section">
            <div class="section-header">
                <h2 class="section-title">Featured Cards</h2>
                <a href="cards2.php" class="btn btn-secondary btn-small">View All →</a>
            </div>
            <div class="card-gallery">
                <?php foreach ($featured_cards as $card): ?>
                    <div class="gallery-card-item"
                         data-rarity="<?php echo strtolower($card['rarity']); ?>"
                         onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card), ENT_QUOTES, 'UTF-8'); ?>)">
                        <div class="gallery-card-image">
                            <?php if ($card['card_art_url']): ?>
                                <img src="<?php echo htmlspecialchars($card['card_art_url']); ?>"
                                     alt="<?php echo htmlspecialchars($card['name']); ?>"
                                     draggable="false"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="card-placeholder-full">
                                    <div class="placeholder-content">
                                        <span class="placeholder-name"><?php echo htmlspecialchars($card['name']); ?></span>
                                        <span class="placeholder-type"><?php echo $card['card_type']; ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Latest News Section -->
        <section class="featured-section">
            <div class="section-header">
                <h2 class="section-title">Latest News</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">📢</span>
                    <h3>New Set Released!</h3>
                    <p>The latest expansion is now available with 150+ new cards to collect and powerful new mechanics to master.</p>
                    <a href="#" class="btn btn-secondary btn-small">Read More</a>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">🏆</span>
                    <h3>Tournament Season 2</h3>
                    <p>Join our competitive season with amazing prizes. Registration opens next week!</p>
                    <a href="#" class="btn btn-secondary btn-small">Learn More</a>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">⚡</span>
                    <h3>Balance Update</h3>
                    <p>Check out the latest balance changes and meta shifts affecting the competitive scene.</p>
                    <a href="#" class="btn btn-secondary btn-small">View Changes</a>
                </div>
            </div>
        </section>

        <!-- Community Decks Preview - 6 Decks (3 per row) -->
        <?php
        $stmt = $pdo->query("
            SELECT d.*, u.username, c.card_art_url as featured_card_image,
                   (SELECT SUM(quantity) FROM deck_cards WHERE deck_id = d.id) as total_cards
            FROM decks d
            JOIN users u ON d.user_id = u.id
            LEFT JOIN cards c ON d.featured_card_id = c.id
            WHERE d.is_published = TRUE
            ORDER BY d.published_at DESC
            LIMIT 6
        ");
        $community_decks = $stmt->fetchAll();
        ?>

        <?php if (!empty($community_decks)): ?>
        <section class="featured-section">
            <div class="section-header">
                <h2 class="section-title">Community Decks</h2>
                <a href="community_decks.php" class="btn btn-secondary btn-small">View All →</a>
            </div>
            <div class="community-decks-grid">
                <?php foreach ($community_decks as $deck): ?>
                    <div class="feature-card deck-card" onclick="window.location.href='view_deck.php?id=<?php echo $deck['id']; ?>'">
                        <?php if ($deck['featured_card_image']): ?>
                            <div style="margin: -1rem -1rem 1rem -1rem; border-radius: var(--radius-lg) var(--radius-lg) 0 0; overflow: hidden; background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%); padding: 1rem;">
                                <img src="<?php echo htmlspecialchars($deck['featured_card_image']); ?>"
                                     alt="Featured card"
                                     style="width: 100%; border-radius: var(--radius-md);">
                            </div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($deck['deck_name']); ?></h3>

                        <p class="deck-meta" style="color: var(--text-muted); margin-bottom: 0.5rem; font-size: 0.85rem;">
                            by <strong><?php echo htmlspecialchars($deck['username']); ?></strong>
                        </p>

                        <?php if ($deck['description']): ?>
                            <p style="font-size: 0.85rem; line-height: 1.5; color: var(--text-secondary);">
                                <?php
                                $desc = htmlspecialchars($deck['description']);
                                echo strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc;
                                ?>
                            </p>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; margin: 1rem 0 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--border-primary); font-size: 0.8rem; color: var(--text-muted);">
                            <span>📊 <?php echo $deck['total_cards']; ?> cards</span>
                            <span>❤️ <?php echo $deck['like_count']; ?> likes</span>
                        </div>

                        <a href="view_deck.php?id=<?php echo $deck['id']; ?>" class="btn btn-secondary btn-small" onclick="event.stopPropagation();">
                            View Deck
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Card Detail Modal -->
    <?php include 'includes/card_detail_modal.php'; ?>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/card_formatter.js"></script>
    <script src="js/cards.js"></script>
</body>
</html>
