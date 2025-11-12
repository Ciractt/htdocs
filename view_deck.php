<?php
require_once 'config.php';

$pdo = getDB();
$user = getCurrentUser();

$deck_id = intval($_GET['id'] ?? 0);

if ($deck_id <= 0) {
    header('Location: community_decks.php');
    exit;
}

// Get deck details
$stmt = $pdo->prepare("
    SELECT d.*, u.username,
           (SELECT COUNT(*) FROM deck_cards WHERE deck_id = d.id) as unique_cards,
           (SELECT SUM(quantity) FROM deck_cards WHERE deck_id = d.id) as total_cards
    FROM decks d
    JOIN users u ON d.user_id = u.id
    WHERE d.id = ? AND (d.is_published = TRUE OR d.user_id = ?)
");
$stmt->execute([$deck_id, $user['id'] ?? 0]);
$deck = $stmt->fetch();

if (!$deck) {
    header('Location: community_decks.php');
    exit;
}

// Increment view count
$stmt = $pdo->prepare("UPDATE decks SET view_count = view_count + 1 WHERE id = ?");
$stmt->execute([$deck_id]);

// Get deck cards
$stmt = $pdo->prepare("
    SELECT c.*, dc.quantity
    FROM deck_cards dc
    JOIN cards c ON dc.card_id = c.id
    WHERE dc.deck_id = ?
    ORDER BY c.card_code
");
$stmt->execute([$deck_id]);
$deck_cards = $stmt->fetchAll();

// Check if user liked this deck
$user_liked = false;
if ($user) {
    $stmt = $pdo->prepare("SELECT id FROM deck_likes WHERE user_id = ? AND deck_id = ?");
    $stmt->execute([$user['id'], $deck_id]);
    $user_liked = $stmt->fetch() ? true : false;
}

// Calculate deck stats
$total_cost = 0;
$card_types = [];
foreach ($deck_cards as $card) {
    $total_cost += ($card['energy'] ?? 0) * $card['quantity'];
    $type = $card['card_type'] ?? 'Other';
    if (!isset($card_types[$type])) {
        $card_types[$type] = 0;
    }
    $card_types[$type] += $card['quantity'];
}
$avg_cost = $deck['total_cards'] > 0 ? round($total_cost / $deck['total_cards'], 1) : 0;

// Group cards by type
$champions = [];
$units = [];
$spells = [];
$other = [];

foreach ($deck_cards as $card) {
    $cardType = $card['card_type'] ?? '';
    if ($card['rarity'] === 'Champion') {
        $champions[] = $card;
    } elseif ($cardType === 'Unit') {
        $units[] = $card;
    } elseif ($cardType === 'Spell') {
        $spells[] = $card;
    } else {
        $other[] = $card;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($deck['deck_name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-fixes.css">
    <style>
        .deck-view-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .deck-title {
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
        }

        .deck-meta {
            color: #666;
            margin-bottom: 1rem;
        }

        .deck-description {
            font-size: 1rem;
            line-height: 1.6;
            color: #555;
            margin: 1rem 0;
        }

        .deck-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .deck-actions-bar {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .deck-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .deck-list-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .deck-sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .card-type-section {
            margin-bottom: 1.5rem;
        }

        .card-type-title {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .deck-card-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .deck-card-row:hover {
            background: #f8f9fa;
            cursor: pointer;
        }

        .deck-card-qty {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        .deck-card-cost {
            background: #3498db;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        .deck-card-name {
            flex: 1;
            font-size: 0.95rem;
        }

        .type-distribution {
            margin-bottom: 1rem;
        }

        .type-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .type-label {
            width: 80px;
            font-size: 0.85rem;
            color: #666;
        }

        .type-progress {
            flex: 1;
            height: 24px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .type-progress-fill {
            height: 100%;
            background: #667eea;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
        }

        @media (max-width: 1024px) {
            .deck-content {
                grid-template-columns: 1fr;
            }

            .deck-sidebar {
                position: static;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="deck-view-header">
            <h1 class="deck-title"><?php echo htmlspecialchars($deck['deck_name']); ?></h1>
            <div class="deck-meta">
                Created by <strong><?php echo htmlspecialchars($deck['username']); ?></strong>
                <?php if ($deck['published_at']): ?>
                    • Published <?php echo date('M j, Y', strtotime($deck['published_at'])); ?>
                <?php endif; ?>
                <?php if ($deck['is_featured']): ?>
                    • <span style="color: #f39c12; font-weight: bold;">⭐ FEATURED</span>
                <?php endif; ?>
            </div>

            <?php if ($deck['description']): ?>
                <p class="deck-description"><?php echo nl2br(htmlspecialchars($deck['description'])); ?></p>
            <?php endif; ?>

            <div class="deck-stats-grid">
                <div class="stat-box">
                    <span class="stat-value"><?php echo $deck['total_cards']; ?></span>
                    <span class="stat-label">Total Cards</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value"><?php echo $deck['unique_cards']; ?></span>
                    <span class="stat-label">Unique Cards</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value"><?php echo $avg_cost; ?></span>
                    <span class="stat-label">Avg Cost</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value"><?php echo $deck['like_count']; ?></span>
                    <span class="stat-label">Likes</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value"><?php echo $deck['copy_count']; ?></span>
                    <span class="stat-label">Copies</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value"><?php echo $deck['view_count']; ?></span>
                    <span class="stat-label">Views</span>
                </div>
            </div>

            <div class="deck-actions-bar">
                <?php if ($user): ?>
                    <?php if ($deck['user_id'] == $user['id']): ?>
                        <a href="deck_builder.php?deck_id=<?php echo $deck['id']; ?>" class="btn btn-primary">
                            Edit Deck
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="copyDeck(<?php echo $deck['id']; ?>)">
                            📋 Copy to My Decks
                        </button>
                        <button class="btn <?php echo $user_liked ? 'btn-danger' : 'btn-secondary'; ?>"
                                id="likeBtn"
                                onclick="toggleLike(<?php echo $deck['id']; ?>)">
                            ❤️ <?php echo $user_liked ? 'Liked' : 'Like'; ?>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Login to Copy</a>
                <?php endif; ?>
                <button class="btn btn-secondary" onclick="exportDeckCode()">
                    Export Code
                </button>
                <a href="community_decks.php" class="btn btn-secondary">
                    ← Back to Decks
                </a>
            </div>
        </div>

        <div class="deck-content">
            <!-- Deck List -->
            <div class="deck-list-section">
                <h2>Deck List (<?php echo $deck['total_cards']; ?> cards)</h2>

                <?php if (!empty($champions)): ?>
                    <div class="card-type-section">
                        <div class="card-type-title">Champions (<?php echo array_sum(array_column($champions, 'quantity')); ?>)</div>
                        <?php foreach ($champions as $card): ?>
                            <div class="deck-card-row" onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card), ENT_QUOTES); ?>)">
                                <span class="deck-card-qty">×<?php echo $card['quantity']; ?></span>
                                <span class="deck-card-cost"><?php echo $card['energy'] ?? '-'; ?></span>
                                <span class="deck-card-name"><?php echo htmlspecialchars($card['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($units)): ?>
                    <div class="card-type-section">
                        <div class="card-type-title">Units (<?php echo array_sum(array_column($units, 'quantity')); ?>)</div>
                        <?php foreach ($units as $card): ?>
                            <div class="deck-card-row" onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card), ENT_QUOTES); ?>)">
                                <span class="deck-card-qty">×<?php echo $card['quantity']; ?></span>
                                <span class="deck-card-cost"><?php echo $card['energy'] ?? '-'; ?></span>
                                <span class="deck-card-name"><?php echo htmlspecialchars($card['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($spells)): ?>
                    <div class="card-type-section">
                        <div class="card-type-title">Spells (<?php echo array_sum(array_column($spells, 'quantity')); ?>)</div>
                        <?php foreach ($spells as $card): ?>
                            <div class="deck-card-row" onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card), ENT_QUOTES); ?>)">
                                <span class="deck-card-qty">×<?php echo $card['quantity']; ?></span>
                                <span class="deck-card-cost"><?php echo $card['energy'] ?? '-'; ?></span>
                                <span class="deck-card-name"><?php echo htmlspecialchars($card['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($other)): ?>
                    <div class="card-type-section">
                        <div class="card-type-title">Other (<?php echo array_sum(array_column($other, 'quantity')); ?>)</div>
                        <?php foreach ($other as $card): ?>
                            <div class="deck-card-row" onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card), ENT_QUOTES); ?>)">
                                <span class="deck-card-qty">×<?php echo $card['quantity']; ?></span>
                                <span class="deck-card-cost"><?php echo $card['energy'] ?? '-'; ?></span>
                                <span class="deck-card-name"><?php echo htmlspecialchars($card['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="deck-sidebar">
                <h3>Card Type Distribution</h3>
                <div class="type-distribution">
                    <?php foreach ($card_types as $type => $count): ?>
                        <?php $percentage = ($count / $deck['total_cards']) * 100; ?>
                        <div class="type-bar">
                            <span class="type-label"><?php echo $type; ?></span>
                            <div class="type-progress">
                                <div class="type-progress-fill" style="width: <?php echo $percentage; ?>%">
                                    <?php echo $count; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Card Detail Modal (reuse from other pages) -->
        <?php include 'includes/card_detail_modal.php'; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/card_formatter.js"></script>
    <script src="js/cards.js"></script>
    <script>
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
                    window.location.href = `deck_builder.php?deck_id=${data.deck_id}`;
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Copy error:', error);
                alert('Failed to copy deck');
            }
        }

        async function toggleLike(deckId) {
            const likeBtn = document.getElementById('likeBtn');
            const isLiked = likeBtn.classList.contains('btn-danger');
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
                    likeBtn.classList.toggle('btn-danger');
                    likeBtn.classList.toggle('btn-secondary');
                    likeBtn.innerHTML = isLiked ? '❤️ Like' : '❤️ Liked';

                    // Update like count
                    const likesStat = document.querySelector('.stat-box:nth-child(4) .stat-value');
                    const currentLikes = parseInt(likesStat.textContent);
                    likesStat.textContent = currentLikes + (isLiked ? -1 : 1);
                }
            } catch (error) {
                console.error('Like error:', error);
            }
        }

        function exportDeckCode() {
            const deckCode = `<?php
                $code = '';
                foreach ($deck_cards as $card) {
                    $code .= $card['quantity'] . 'x ' . $card['card_code'] . "\\n";
                }
                echo trim($code);
            ?>`;

            navigator.clipboard.writeText(deckCode).then(() => {
                alert('Deck code copied to clipboard!');
            }).catch(() => {
                prompt('Copy this deck code:', deckCode);
            });
        }
    </script>
</body>
</html>
