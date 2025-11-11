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
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <section class="hero">
            <h1>Welcome to <?php echo SITE_NAME; ?></h1>
            <p>Build your collection, craft powerful decks, and master the game.</p>
            <?php if (!$user): ?>
                <div class="hero-actions">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="featured-cards">
            <h2>Featured Cards</h2>
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

        <section class="features">
            <h2>Community Decks</h2>
            <?php
            $stmt = $pdo->query("
                SELECT d.*, u.username,
                       (SELECT SUM(quantity) FROM deck_cards WHERE deck_id = d.id) as total_cards
                FROM decks d
                JOIN users u ON d.user_id = u.id
                WHERE d.is_published = TRUE
                ORDER BY d.published_at DESC
                LIMIT 3
            ");
            $community_decks = $stmt->fetchAll();
            ?>

            <?php if (!empty($community_decks)): ?>
                <div class="feature-grid">
                    <?php foreach ($community_decks as $deck): ?>
                        <div class="feature-item"
                             onclick="window.location.href='view_deck.php?id=<?php echo $deck['id']; ?>'"
                             style="cursor: pointer; transition: transform 0.2s;">
                            <h3><?php echo htmlspecialchars($deck['deck_name']); ?></h3>
                            <p style="color: #666; margin: 0.5rem 0;">by <?php echo htmlspecialchars($deck['username']); ?></p>
                            <?php if ($deck['description']): ?>
                                <p style="color: #777; font-size: 0.9rem; line-height: 1.5; margin: 0.75rem 0;">
                                    <?php
                                    $desc = htmlspecialchars($deck['description']);
                                    echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                    ?>
                                </p>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: center; gap: 1rem; margin: 1rem 0; font-size: 0.85rem; color: #666;">
                                <span>📊 <?php echo $deck['total_cards']; ?> cards</span>
                                <span>❤️ <?php echo $deck['like_count']; ?> likes</span>
                                <span>📋 <?php echo $deck['copy_count']; ?> copies</span>
                            </div>
                            <a href="view_deck.php?id=<?php echo $deck['id']; ?>"
                               class="btn btn-small"
                               onclick="event.stopPropagation();"
                               style="margin-top: 0.5rem;">View Deck</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="community_decks.php" class="btn btn-primary">View All Community Decks</a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #999;">
                    <p>No published decks yet. Be the first to share your deck!</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="deck_builder.php" class="btn btn-primary" style="margin-top: 1rem;">Create a Deck</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="features">
            <h2>Features</h2>
            <div class="feature-grid">
                <div class="feature-item">
                    <h3>📚 Collection Manager</h3>
                    <p>Track and manage your card collection with ease</p>
                    <?php if ($user): ?>
                        <a href="collection.php" class="btn btn-small">View Collection</a>
                    <?php endif; ?>
                </div>
                <div class="feature-item">
                    <h3>🎴 Deck Builder</h3>
                    <p>Create and optimize powerful decks</p>
                    <?php if ($user): ?>
                        <a href="deck_builder.php" class="btn btn-small">Build Deck</a>
                    <?php endif; ?>
                </div>
                <div class="feature-item">
                    <h3>🔍 Card Database</h3>
                    <p>Browse and search all available cards</p>
                    <a href="cards.php" class="btn btn-small">Browse Cards</a>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Card Detail Modal -->
    <div id="cardDetailModal" class="modal">
        <div class="modal-content card-detail-modal">
            <span class="close">&times;</span>
            <div class="card-detail-layout">
                <div class="card-detail-image">
                    <img id="modalCardImage" src="" alt="">
                </div>
                <div class="card-detail-info">
                    <h2 id="modalCardName"></h2>

                    <!-- Badges (Type, Rarity, Region) -->
                    <div class="card-badges" id="modalBadges"></div>

                    <!-- Champion/Region Pills -->
                    <div class="card-pills" id="modalPills"></div>

                    <!-- Stats Grid -->
                    <div class="detail-stats-grid">
                        <div class="detail-stat-item">
                            <span class="detail-stat-label">Energy</span>
                            <div class="detail-stat-value" id="modalEnergy">0</div>
                        </div>
                        <div class="detail-stat-item">
                            <span class="detail-stat-label">Power</span>
                            <div class="detail-stat-value" id="modalPower">0</div>
                        </div>
                        <div class="detail-stat-item">
                            <span class="detail-stat-label">Might</span>
                            <div class="detail-stat-value" id="modalMight">0</div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="card-section">
                        <h3 class="card-section-title">Description</h3>
                        <div class="detail-description">
                            <p id="modalDescription"></p>
                        </div>
                    </div>

                    <!-- Flavor Text -->
                    <div class="card-section" id="modalFlavorSection" style="display: none;">
                        <h3 class="card-section-title">Flavor Text</h3>
                        <p class="card-flavor-text" id="modalFlavorText"></p>
                    </div>

                    <!-- Card Information -->
                    <div class="card-info-box">
                        <h3>Card Information</h3>
                        <div class="card-info-list">
                            <div class="card-info-item">
                                <span class="card-info-label">Card Number:</span>
                                <code id="modalCardCode"></code>
                            </div>
                            <div class="card-info-item" id="modalQuantityRow" style="display: none;">
                                <span class="card-info-label">You own:</span>
                                <span class="card-info-value" id="modalQuantity"></span>
                            </div>
                        </div>
                    </div>

                    <?php if (isLoggedIn()): ?>
                    <!-- Collection Actions -->
                    <div class="collection-actions" id="modalCollectionActions">
                        <button class="btn btn-primary btn-full" onclick="addToCollectionFromModal()">Add to Collection</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/card_formatter.js"></script>
    <script src="js/cards.js"></script>
    <script>
        // Define globally BEFORE other scripts
        window.currentModalCardId = null;

        // Add card to collection from modal
        async function addToCollectionFromModal() {
            console.log('Add to collection clicked, card ID:', window.currentModalCardId);

            if (!window.currentModalCardId) {
                alert('No card selected');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('card_id', window.currentModalCardId);

            try {
                const response = await fetch('api/collection.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showNotification(data.message, 'success');

                    // Update quantity display if it exists
                    const quantityRow = document.getElementById('modalQuantityRow');
                    const quantitySpan = document.getElementById('modalQuantity');
                    if (quantityRow && quantitySpan) {
                        quantityRow.style.display = 'flex';
                        const currentQty = parseInt(quantitySpan.textContent.replace('x', '')) || 0;
                        quantitySpan.textContent = 'x' + (currentQty + 1);
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            }
        }

        // Show notification helper
        function showNotification(message, type) {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = 'notification notification-' + type;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 10);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
