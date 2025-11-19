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
    <style>
        /* ============================================
           ANNOUNCEMENT/ALERT BANNER
           ============================================ */
        
        .announcement-banner {
            padding: var(--spacing-md) var(--spacing-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-md);
            position: relative;
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
        }

        .announcement-banner.success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: var(--success);
        }

        .announcement-banner.warning {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: var(--warning);
        }

        .announcement-banner.danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: var(--error);
        }

        .announcement-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .announcement-content {
            flex: 1;
            text-align: center;
        }

        .announcement-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 2px;
        }

        .announcement-message {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .announcement-dismiss {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            opacity: 0.7;
            transition: all var(--transition-fast);
            font-size: 1.25rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .announcement-dismiss:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Hide dismissed announcements */
        .announcement-banner.dismissed {
            display: none;
        }

        /* ============================================
           HERO CARD SHOWCASE
           ============================================ */
        
        .hero {
            position: relative;
            overflow: hidden;
        }

        /* Holographic Foil Effect for Hero Title */
        .hero-title-foil {
            position: relative;
            display: inline-block;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-title-foil::before {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                125deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0) 35%,
                rgba(255, 120, 200, 0.5) 45%,
                rgba(255, 255, 255, 0.6) 50%,
                rgba(120, 200, 255, 0.5) 55%,
                rgba(255, 255, 255, 0) 65%,
                rgba(255, 255, 255, 0) 100%
            );
            background-size: 200% 200%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: foil-shimmer 6s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes foil-shimmer {
            0% {
                background-position: 200% 50%;
            }
            100% {
                background-position: -200% 50%;
            }
        }

        .hero-cards-showcase {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 0;
        }

        .hero-card {
            position: absolute;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            opacity: 0.2;
            animation: float 6s ease-in-out infinite;
        }

        .hero-card img {
            width: 100%;
            height: auto;
            border-radius: var(--radius-md);
            display: block;
        }

        /* Position cards around the hero - Left side (7 cards) */
        .hero-card:nth-child(1) {
            width: 90px;
            top: 3%;
            left: 2%;
            transform: rotate(-12deg);
            animation-delay: 0s;
        }

        .hero-card:nth-child(2) {
            width: 110px;
            top: 22%;
            left: 7%;
            transform: rotate(8deg);
            animation-delay: -1s;
        }

        .hero-card:nth-child(3) {
            width: 85px;
            top: 45%;
            left: 1%;
            transform: rotate(-6deg);
            animation-delay: -2s;
        }

        .hero-card:nth-child(4) {
            width: 100px;
            top: 68%;
            left: 9%;
            transform: rotate(10deg);
            animation-delay: -3s;
        }

        .hero-card:nth-child(5) {
            width: 75px;
            top: 12%;
            left: 14%;
            transform: rotate(-3deg);
            animation-delay: -4s;
        }

        .hero-card:nth-child(6) {
            width: 95px;
            top: 38%;
            left: 12%;
            transform: rotate(5deg);
            animation-delay: -5s;
        }

        .hero-card:nth-child(7) {
            width: 80px;
            bottom: 5%;
            left: 3%;
            transform: rotate(-8deg);
            animation-delay: -6s;
        }

        /* Position cards around the hero - Right side (7 cards) */
        .hero-card:nth-child(8) {
            width: 100px;
            top: 5%;
            right: 4%;
            transform: rotate(10deg);
            animation-delay: -0.5s;
        }

        .hero-card:nth-child(9) {
            width: 85px;
            top: 28%;
            right: 1%;
            transform: rotate(-8deg);
            animation-delay: -1.5s;
        }

        .hero-card:nth-child(10) {
            width: 110px;
            top: 50%;
            right: 8%;
            transform: rotate(5deg);
            animation-delay: -2.5s;
        }

        .hero-card:nth-child(11) {
            width: 80px;
            top: 72%;
            right: 2%;
            transform: rotate(-12deg);
            animation-delay: -3.5s;
        }

        .hero-card:nth-child(12) {
            width: 90px;
            top: 10%;
            right: 12%;
            transform: rotate(6deg);
            animation-delay: -4.5s;
        }

        .hero-card:nth-child(13) {
            width: 75px;
            top: 40%;
            right: 14%;
            transform: rotate(-4deg);
            animation-delay: -5.5s;
        }

        .hero-card:nth-child(14) {
            width: 95px;
            bottom: 8%;
            right: 6%;
            transform: rotate(7deg);
            animation-delay: -6.5s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(var(--rotation, 0deg));
            }
            50% {
                transform: translateY(-12px) rotate(var(--rotation, 0deg));
            }
        }

        /* Preserve individual rotations during animation */
        .hero-card:nth-child(1) { --rotation: -12deg; }
        .hero-card:nth-child(2) { --rotation: 8deg; }
        .hero-card:nth-child(3) { --rotation: -6deg; }
        .hero-card:nth-child(4) { --rotation: 10deg; }
        .hero-card:nth-child(5) { --rotation: -3deg; }
        .hero-card:nth-child(6) { --rotation: 5deg; }
        .hero-card:nth-child(7) { --rotation: -8deg; }
        .hero-card:nth-child(8) { --rotation: 10deg; }
        .hero-card:nth-child(9) { --rotation: -8deg; }
        .hero-card:nth-child(10) { --rotation: 5deg; }
        .hero-card:nth-child(11) { --rotation: -12deg; }
        .hero-card:nth-child(12) { --rotation: 6deg; }
        .hero-card:nth-child(13) { --rotation: -4deg; }
        .hero-card:nth-child(14) { --rotation: 7deg; }

        /* Ensure hero content stays above cards */
        .hero-content {
            position: relative;
            z-index: 1;
        }

        /* Responsive - progressively hide cards */
        @media (max-width: 1400px) {
            .hero-card:nth-child(5),
            .hero-card:nth-child(6),
            .hero-card:nth-child(12),
            .hero-card:nth-child(13) {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .hero-card {
                opacity: 0.15;
            }
            
            .hero-card:nth-child(4),
            .hero-card:nth-child(7),
            .hero-card:nth-child(11),
            .hero-card:nth-child(14) {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .hero-card {
                opacity: 0.12;
            }
            
            .hero-card:nth-child(3),
            .hero-card:nth-child(10) {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .hero-card:nth-child(2),
            .hero-card:nth-child(9) {
                display: none;
            }
        }

        /* ============================================
           UNIFIED CARD GRID SYSTEM
           ============================================ */
        
        /* Unified grid for all homepage sections */
        .unified-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }

        /* News grid - always 4 columns for wider cards */
        .unified-grid.grid-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        /* Unified card styling */
        .unified-card {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all var(--transition-base);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .unified-card:hover {
            transform: translateY(-6px);
            border-color: var(--accent-primary);
            box-shadow: var(--shadow-glow), var(--shadow-lg);
        }

        /* Card image container - matches actual card ratio (515x719) */
        .unified-card-image {
            position: relative;
            width: 100%;
            aspect-ratio: 515/719;
            overflow: hidden;
            background: var(--bg-primary);
        }

        /* News cards use a landscape/square ratio for wider appearance */
        .unified-card.news-card .unified-card-image {
            aspect-ratio: 16/9;
        }

        .unified-card-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform var(--transition-slow);
        }

        /* News card images should cover since they're photos, not game cards */
        .unified-card.news-card .unified-card-image img {
            object-fit: cover;
        }

        .unified-card:hover .unified-card-image img {
            transform: scale(1.05);
        }

        /* Placeholder for cards without images */
        .unified-card-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: var(--spacing-sm);
            background: var(--accent-gradient);
            padding: var(--spacing-md);
            text-align: center;
        }

        .unified-card-placeholder .placeholder-icon {
            font-size: 2.5rem;
            opacity: 0.9;
        }

        .unified-card-placeholder .placeholder-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .unified-card-placeholder .placeholder-type {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Card content area */
        .unified-card-content {
            padding: var(--spacing-md);
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .unified-card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 var(--spacing-xs);
            line-height: 1.3;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .unified-card-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: var(--spacing-sm);
        }

        .unified-card-description {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.4;
            margin: 0;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            flex: 1;
        }

        .unified-card-stats {
            display: flex;
            justify-content: space-between;
            padding-top: var(--spacing-sm);
            margin-top: auto;
            border-top: 1px solid var(--border-primary);
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* Featured cards specific - no content area, just image */
        .unified-card.card-only {
            aspect-ratio: 515/719;
        }

        .unified-card.card-only .unified-card-image {
            aspect-ratio: unset;
            height: 100%;
        }

        /* Rarity-based hover effects for game cards */
        .unified-card[data-rarity="common"]:hover {
            box-shadow: 0 8px 20px rgba(149, 165, 166, 0.5);
        }

        .unified-card[data-rarity="rare"]:hover {
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.5);
        }

        .unified-card[data-rarity="epic"]:hover {
            box-shadow: 0 8px 20px rgba(155, 89, 182, 0.5);
        }

        .unified-card[data-rarity="champion"]:hover {
            box-shadow: 0 8px 20px rgba(243, 156, 18, 0.6);
        }

        /* Responsive breakpoints */
        @media (max-width: 1400px) {
            .unified-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .unified-grid.grid-4 {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .unified-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .unified-grid.grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .unified-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .unified-grid.grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .unified-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: var(--spacing-md);
            }
            
            .unified-grid.grid-4 {
                grid-template-columns: 1fr;
            }

            .unified-card-content {
                padding: var(--spacing-sm);
            }

            .unified-card-title {
                font-size: 0.8rem;
            }

            .unified-card-description {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <!-- Announcements/Alerts -->
        <?php
        $announcement_stmt = $pdo->query("
            SELECT * FROM announcements 
            WHERE is_active = TRUE 
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY 
                CASE alert_type 
                    WHEN 'danger' THEN 1 
                    WHEN 'warning' THEN 2 
                    WHEN 'success' THEN 3 
                END,
                created_at DESC
            LIMIT 3
        ");
        $announcements = $announcement_stmt->fetchAll();
        
        foreach ($announcements as $announcement):
            $icon = match($announcement['alert_type']) {
                'success' => '✓',
                'warning' => '⚠',
                'danger' => '⚠',
                default => 'ℹ'
            };
        ?>
            <div class="announcement-banner <?php echo htmlspecialchars($announcement['alert_type']); ?>" 
                 data-announcement-id="<?php echo $announcement['id']; ?>">
                <span class="announcement-icon"><?php echo $icon; ?></span>
                <div class="announcement-content">
                    <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                    <div class="announcement-message"><?php echo htmlspecialchars($announcement['message']); ?></div>
                </div>
                <?php if ($announcement['is_dismissible']): ?>
                    <button class="announcement-dismiss" onclick="dismissAnnouncement(<?php echo $announcement['id']; ?>)" aria-label="Dismiss">
                        ×
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Hero Section -->
        <section class="hero">
            <!-- Floating Cards Background -->
            <div class="hero-cards-showcase">
                <?php 
                // Get random cards for hero background (excluding Battlefield cards)
                $hero_stmt = $pdo->query("SELECT card_art_url, name FROM cards WHERE card_art_url IS NOT NULL AND card_type != 'Battlefield' ORDER BY RAND() LIMIT 14");
                $hero_cards = $hero_stmt->fetchAll();
                foreach ($hero_cards as $hero_card): 
                    if ($hero_card['card_art_url']):
                ?>
                    <div class="hero-card">
                        <img src="<?php echo htmlspecialchars($hero_card['card_art_url']); ?>" 
                             alt="<?php echo htmlspecialchars($hero_card['name']); ?>"
                             loading="lazy">
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>

            <div class="hero-content">
                <h1><span class="hero-title-foil" data-text="Welcome to <?php echo SITE_NAME; ?>">Welcome to <?php echo SITE_NAME; ?></span></h1>
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
            <div class="unified-grid">
                <?php foreach ($featured_cards as $card): ?>
                    <div class="unified-card card-only"
                         data-rarity="<?php echo strtolower($card['rarity']); ?>"
                         onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card), ENT_QUOTES, 'UTF-8'); ?>)">
                        <div class="unified-card-image">
                            <?php if ($card['card_art_url']): ?>
                                <img src="<?php echo htmlspecialchars($card['card_art_url']); ?>"
                                     alt="<?php echo htmlspecialchars($card['name']); ?>"
                                     draggable="false"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="unified-card-placeholder">
                                    <span class="placeholder-name"><?php echo htmlspecialchars($card['name']); ?></span>
                                    <span class="placeholder-type"><?php echo $card['card_type']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Latest News Section -->
        <?php
        $stmt = $pdo->query("
            SELECT n.*, u.username as author_name
            FROM news_posts n
            JOIN users u ON n.author_id = u.id
            WHERE n.is_published = TRUE
            ORDER BY n.published_at DESC
            LIMIT 4
        ");
        $latest_news = $stmt->fetchAll();
        ?>

        <?php if (!empty($latest_news)): ?>
        <section class="featured-section">
            <div class="section-header">
                <h2 class="section-title">Latest News</h2>
                <a href="news.php" class="btn btn-secondary btn-small">View All News →</a>
            </div>
            <div class="unified-grid grid-4">
                <?php foreach ($latest_news as $news): ?>
                    <div class="unified-card news-card" onclick="window.location.href='news.php?slug=<?php echo htmlspecialchars($news['slug']); ?>'">
                        <div class="unified-card-image">
                            <?php if ($news['featured_image']): ?>
                                <img src="<?php echo htmlspecialchars($news['featured_image']); ?>"
                                     alt="<?php echo htmlspecialchars($news['title']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="unified-card-placeholder">
                                    <span class="placeholder-icon">📰</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="unified-card-content">
                            <h3 class="unified-card-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                            <div class="unified-card-meta">
                                <?php echo date('M j, Y', strtotime($news['published_at'])); ?>
                            </div>
                            <?php if ($news['excerpt']): ?>
                                <p class="unified-card-description"><?php echo htmlspecialchars($news['excerpt']); ?></p>
                            <?php endif; ?>
                            <div class="unified-card-stats">
                                <span>👁 <?php echo number_format($news['view_count']); ?></span>
                                <span>by <?php echo htmlspecialchars($news['author_name']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <!-- Fallback: Show static placeholder if no news posts exist -->
        <section class="featured-section">
            <div class="section-header">
                <h2 class="section-title">Latest News</h2>
            </div>
            <div class="unified-grid grid-4">
                <div class="unified-card news-card">
                    <div class="unified-card-image">
                        <div class="unified-card-placeholder">
                            <span class="placeholder-icon">📢</span>
                        </div>
                    </div>
                    <div class="unified-card-content">
                        <h3 class="unified-card-title">Coming Soon</h3>
                        <p class="unified-card-description">Stay tuned for the latest updates and announcements!</p>
                    </div>
                </div>

                <div class="unified-card news-card">
                    <div class="unified-card-image">
                        <div class="unified-card-placeholder">
                            <span class="placeholder-icon">🏆</span>
                        </div>
                    </div>
                    <div class="unified-card-content">
                        <h3 class="unified-card-title">Community Updates</h3>
                        <p class="unified-card-description">Join our growing community and be the first to know!</p>
                    </div>
                </div>

                <div class="unified-card news-card">
                    <div class="unified-card-image">
                        <div class="unified-card-placeholder">
                            <span class="placeholder-icon">⚡</span>
                        </div>
                    </div>
                    <div class="unified-card-content">
                        <h3 class="unified-card-title">Game Updates</h3>
                        <p class="unified-card-description">Check back for balance changes and new cards.</p>
                    </div>
                </div>

                <div class="unified-card news-card">
                    <div class="unified-card-image">
                        <div class="unified-card-placeholder">
                            <span class="placeholder-icon">🎮</span>
                        </div>
                    </div>
                    <div class="unified-card-content">
                        <h3 class="unified-card-title">Events</h3>
                        <p class="unified-card-description">Upcoming tournaments and special events.</p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Community Decks Preview -->
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
            <div class="unified-grid">
                <?php foreach ($community_decks as $deck): ?>
                    <div class="unified-card" onclick="window.location.href='view_deck.php?id=<?php echo $deck['id']; ?>'">
                        <div class="unified-card-image">
                            <?php if ($deck['featured_card_image']): ?>
                                <img src="<?php echo htmlspecialchars($deck['featured_card_image']); ?>"
                                     alt="Featured card"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="unified-card-placeholder">
                                    <span class="placeholder-icon">🃏</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="unified-card-content">
                            <h3 class="unified-card-title"><?php echo htmlspecialchars($deck['deck_name']); ?></h3>
                            <div class="unified-card-meta">
                                by <?php echo htmlspecialchars($deck['username']); ?>
                            </div>
                            <?php if ($deck['description']): ?>
                                <p class="unified-card-description"><?php echo htmlspecialchars($deck['description']); ?></p>
                            <?php endif; ?>
                            <div class="unified-card-stats">
                                <span>📊 <?php echo $deck['total_cards'] ?: 0; ?> cards</span>
                                <span>❤️ <?php echo $deck['like_count']; ?></span>
                            </div>
                        </div>
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
    <script>
        // Announcement dismiss functionality
        function dismissAnnouncement(id) {
            const banner = document.querySelector(`[data-announcement-id="${id}"]`);
            if (banner) {
                banner.classList.add('dismissed');
                
                // Store dismissed state in localStorage
                let dismissed = JSON.parse(localStorage.getItem('dismissedAnnouncements') || '[]');
                if (!dismissed.includes(id)) {
                    dismissed.push(id);
                    localStorage.setItem('dismissedAnnouncements', JSON.stringify(dismissed));
                }
            }
        }

        // Check for previously dismissed announcements on page load
        document.addEventListener('DOMContentLoaded', function() {
            const dismissed = JSON.parse(localStorage.getItem('dismissedAnnouncements') || '[]');
            dismissed.forEach(id => {
                const banner = document.querySelector(`[data-announcement-id="${id}"]`);
                if (banner) {
                    banner.classList.add('dismissed');
                }
            });
        });
    </script>
</body>
</html>