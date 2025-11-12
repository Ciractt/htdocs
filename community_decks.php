<?php
require_once 'config.php';

$pdo = getDB();
$user = getCurrentUser();

// Get filter parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'recent'; // recent, popular, liked

// Build query
$query = "
    SELECT d.*, u.username, c.card_art_url as featured_card_image,
           (SELECT COUNT(*) FROM deck_cards WHERE deck_id = d.id) as unique_cards,
           (SELECT SUM(quantity) FROM deck_cards WHERE deck_id = d.id) as total_cards
    FROM decks d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN cards c ON d.featured_card_id = c.id
    WHERE d.is_published = TRUE
";
$params = [];

if ($search) {
    $query .= " AND (d.deck_name LIKE ? OR d.description LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Apply sorting
if ($sort === 'popular') {
    $query .= " ORDER BY d.copy_count DESC, d.like_count DESC";
} elseif ($sort === 'liked') {
    $query .= " ORDER BY d.like_count DESC, d.copy_count DESC";
} else {
    $query .= " ORDER BY d.published_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$decks = $stmt->fetchAll();

// Get user's likes if logged in
$user_likes = [];
if ($user) {
    $stmt = $pdo->prepare("SELECT deck_id FROM deck_likes WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    while ($row = $stmt->fetch()) {
        $user_likes[] = $row['deck_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Decks - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-fixes.css">
    <style>
        .deck-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .deck-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .deck-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .deck-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .deck-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin: 0 0 0.25rem 0;
        }

        .deck-author {
            font-size: 0.85rem;
            color: #666;
        }

        .deck-featured-badge {
            background: #f39c12;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .deck-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .deck-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .deck-stat {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .deck-stat strong {
            color: #667eea;
        }

        .deck-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-like {
            background: white;
            border: 2px solid #e74c3c;
            color: #e74c3c;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-like:hover {
            background: #e74c3c;
            color: white;
        }

        .btn-like.liked {
            background: #e74c3c;
            color: white;
        }

        .featured-section {
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            color: white;
        }

        .featured-deck {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .featured-deck h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>Community Decks</h1>
            <?php if ($user): ?>
                <a href="deck_builder.php" class="btn btn-primary btn-small">Create Deck</a>
            <?php endif; ?>
        </div>

        <!-- Featured Decks -->
        <?php
        $featured_stmt = $pdo->query("
            SELECT d.*, u.username, c.card_art_url as featured_card_image,
                   (SELECT COUNT(*) FROM deck_cards WHERE deck_id = d.id) as unique_cards,
                   (SELECT SUM(quantity) FROM deck_cards WHERE deck_id = d.id) as total_cards
            FROM decks d
            JOIN users u ON d.user_id = u.id
            LEFT JOIN cards c ON d.featured_card_id = c.id
            WHERE d.is_featured = TRUE AND d.is_published = TRUE
            ORDER BY d.published_at DESC
            LIMIT 1
        ");
        $featured_deck = $featured_stmt->fetch();

        if ($featured_deck):
        ?>
        <div class="featured-section">
            <h2>⭐ Featured Deck</h2>
            <div class="featured-deck">
                <?php if ($featured_deck['featured_card_image']): ?>
                    <div style="width: 208px; height: 312px; float: left; margin-right: 1.5rem; border-radius: 8px; overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($featured_deck['featured_card_image']); ?>"
                             alt="Featured card"
                             style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($featured_deck['deck_name']); ?></h3>
                <p style="margin: 0.5rem 0;">by <?php echo htmlspecialchars($featured_deck['username']); ?></p>
                <p style="margin: 1rem 0;"><?php echo htmlspecialchars($featured_deck['description']); ?></p>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <span>📊 <?php echo $featured_deck['total_cards']; ?> cards</span>
                    <span>❤️ <?php echo $featured_deck['like_count']; ?> likes</span>
                    <span>📋 <?php echo $featured_deck['copy_count']; ?> copies</span>
                </div>
                <div style="margin-top: 1rem; clear: both;">
                    <a href="view_deck.php?id=<?php echo $featured_deck['id']; ?>" class="btn btn-primary btn-small">View Deck</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <input type="text"
                           name="search"
                           placeholder="Search decks, players..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="filter-input">
                </div>

                <div class="filter-group">
                    <select name="sort" class="filter-select">
                        <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Recently Published</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Copied</option>
                        <option value="liked" <?php echo $sort === 'liked' ? 'selected' : ''; ?>>Most Liked</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="community_decks.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>

        <div class="results-count">
            <p>Showing <?php echo count($decks); ?> deck(s)</p>
        </div>

        <!-- Deck Grid -->
        <?php if (empty($decks)): ?>
            <div class="empty-state">
                <h3>No decks found</h3>
                <p>Be the first to publish a deck!</p>
                <?php if ($user): ?>
                    <a href="deck_builder.php" class="btn btn-primary">Create Deck</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="deck-grid">
                <?php foreach ($decks as $deck): ?>
                    <div class="deck-card" onclick="window.location.href='view_deck.php?id=<?php echo $deck['id']; ?>'">
                        <?php if ($deck['featured_card_image']): ?>
                            <div style="display: flex; justify-content: center; margin: -1.5rem -1.5rem 1rem -1.5rem; padding: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <img src="<?php echo htmlspecialchars($deck['featured_card_image']); ?>"
                                     alt="Featured card"
                                     style="width: 208px; height: 312px; object-fit: contain; border-radius: 8px;">
                            </div>
                        <?php endif; ?>

                        <div class="deck-card-header">
                            <div>
                                <h3 class="deck-name"><?php echo htmlspecialchars($deck['deck_name']); ?></h3>
                                <p class="deck-author">by <?php echo htmlspecialchars($deck['username']); ?></p>
                            </div>
                            <?php if ($deck['is_featured']): ?>
                                <span class="deck-featured-badge">FEATURED</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($deck['description']): ?>
                            <p class="deck-description"><?php echo htmlspecialchars($deck['description']); ?></p>
                        <?php endif; ?>

                        <div class="deck-stats">
                            <div class="deck-stat">
                                <span>📊</span>
                                <strong><?php echo $deck['total_cards']; ?></strong> cards
                            </div>
                            <div class="deck-stat">
                                <span>❤️</span>
                                <strong><?php echo $deck['like_count']; ?></strong> likes
                            </div>
                            <div class="deck-stat">
                                <span>📋</span>
                                <strong><?php echo $deck['copy_count']; ?></strong> copies
                            </div>
                        </div>

                        <div class="deck-actions" onclick="event.stopPropagation()">
                            <?php if ($user): ?>
                                <button class="btn-like <?php echo in_array($deck['id'], $user_likes) ? 'liked' : ''; ?>"
                                        onclick="toggleLike(<?php echo $deck['id']; ?>, this)">
                                    ❤️ <?php echo in_array($deck['id'], $user_likes) ? 'Liked' : 'Like'; ?>
                                </button>
                                <button class="btn btn-primary btn-small" onclick="copyDeck(<?php echo $deck['id']; ?>)">
                                    Copy Deck
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-small">Login to Copy</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        async function toggleLike(deckId, button) {
            const isLiked = button.classList.contains('liked');
            const action = isLiked ? 'unlike' : 'like';

            const formData = new FormData();
            formData.append('action', action);
            formData.append('deck_id', deckId);

            try {
                const response = await fetch('api/deck.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    button.classList.toggle('liked');
                    button.innerHTML = isLiked ? '❤️ Like' : '❤️ Liked';

                    // Update like count in the deck stat
                    const deckCard = button.closest('.deck-card');
                    const likesStat = deckCard.querySelector('.deck-stat:nth-child(2) strong');
                    const currentLikes = parseInt(likesStat.textContent);
                    likesStat.textContent = currentLikes + (isLiked ? -1 : 1);
                }
            } catch (error) {
                console.error('Like error:', error);
            }
        }

        async function copyDeck(deckId) {
            if (!confirm('Copy this deck to your collection?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'copy_deck');
            formData.append('deck_id', deckId);

            try {
                const response = await fetch('api/deck.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);

                    // Update copy count in the deck stat
                    const deckCard = event.target.closest('.deck-card');
                    const copiesStat = deckCard.querySelector('.deck-stat:nth-child(3) strong');
                    const currentCopies = parseInt(copiesStat.textContent);
                    copiesStat.textContent = currentCopies + 1;

                    // Redirect to edit the copied deck
                    window.location.href = `deck_builder.php?deck_id=${data.deck_id}`;
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Copy error:', error);
                alert('Failed to copy deck');
            }
        }
    </script>
</body>
</html>
