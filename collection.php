<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();

// Get filter and sort parameters
$search = $_GET['search'] ?? '';
$region = $_GET['region'] ?? '';
$rarity = $_GET['rarity'] ?? '';
$card_type = $_GET['type'] ?? '';
$champion = $_GET['champion'] ?? '';
$sort = $_GET['sort'] ?? 'card_number'; // card_number, date_added, or quantity

// Build query
$query = "
    SELECT c.*, uc.quantity, uc.acquired_at
    FROM user_collections uc
    JOIN cards c ON uc.card_id = c.id
    WHERE uc.user_id = ?
";
$params = [$user['id']];

if ($search) {
    $query .= " AND c.name LIKE ?";
    $params[] = "%$search%";
}
if ($region) {
    $query .= " AND c.region = ?";
    $params[] = $region;
}
if ($rarity) {
    $query .= " AND c.rarity = ?";
    $params[] = $rarity;
}
if ($card_type) {
    $query .= " AND c.card_type = ?";
    $params[] = $card_type;
}
if ($champion) {
    $query .= " AND c.champion = ?";
    $params[] = $champion;
}

// Apply sorting
if ($sort === 'date_added') {
    $query .= " ORDER BY uc.acquired_at DESC";
} elseif ($sort === 'quantity') {
    $query .= " ORDER BY uc.quantity DESC, c.card_code";
} else {
    $query .= " ORDER BY c.card_code";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$collection = $stmt->fetchAll();

// Get all cards for adding to collection
$all_cards = $pdo->query("SELECT * FROM cards ORDER BY card_code")->fetchAll();

// Get unique values for filters from user's collection
$stmt = $pdo->prepare("
    SELECT DISTINCT c.region
    FROM user_collections uc
    JOIN cards c ON uc.card_id = c.id
    WHERE uc.user_id = ?
    ORDER BY c.region
");
$stmt->execute([$user['id']]);
$user_regions = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT DISTINCT c.champion
    FROM user_collections uc
    JOIN cards c ON uc.card_id = c.id
    WHERE uc.user_id = ? AND c.champion IS NOT NULL
    ORDER BY c.champion
");
$stmt->execute([$user['id']]);
$user_champions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collection - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-fixes.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>My Collection</h1>
            <button id="addCardBtn" class="btn btn-primary">+ Add Cards</button>
        </div>

        <div class="collection-stats">
            <div class="stat-item">
                <span class="stat-value"><?php echo count($collection); ?></span>
                <span class="stat-label">Unique Cards</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo array_sum(array_column($collection, 'quantity')); ?></span>
                <span class="stat-label">Total Cards</span>
            </div>
        </div>

        <?php if (!empty($collection)): ?>
            <div class="filters">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search cards..." value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                    </div>

                    <div class="filter-group">
                        <select name="region" class="filter-select">
                            <option value="">All Regions</option>
                            <?php foreach ($user_regions as $reg): ?>
                                <option value="<?php echo htmlspecialchars($reg); ?>" <?php echo $region === $reg ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($reg); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <select name="rarity" class="filter-select">
                            <option value="">All Rarities</option>
                            <option value="Common" <?php echo $rarity === 'Common' ? 'selected' : ''; ?>>Common</option>
                            <option value="Rare" <?php echo $rarity === 'Rare' ? 'selected' : ''; ?>>Rare</option>
                            <option value="Epic" <?php echo $rarity === 'Epic' ? 'selected' : ''; ?>>Epic</option>
                            <option value="Champion" <?php echo $rarity === 'Champion' ? 'selected' : ''; ?>>Champion</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <select name="type" class="filter-select">
                            <option value="">All Types</option>
                            <option value="Unit" <?php echo $card_type === 'Unit' ? 'selected' : ''; ?>>Unit</option>
                            <option value="Spell" <?php echo $card_type === 'Spell' ? 'selected' : ''; ?>>Spell</option>
                            <option value="Landmark" <?php echo $card_type === 'Landmark' ? 'selected' : ''; ?>>Landmark</option>
                            <option value="Equipment" <?php echo $card_type === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                        </select>
                    </div>

                    <?php if (!empty($user_champions)): ?>
                        <div class="filter-group">
                            <select name="champion" class="filter-select">
                                <option value="">All Champions</option>
                                <?php foreach ($user_champions as $champ): ?>
                                    <option value="<?php echo htmlspecialchars($champ); ?>" <?php echo $champion === $champ ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($champ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="filter-group">
                        <select name="sort" class="filter-select">
                            <option value="card_number" <?php echo $sort === 'card_number' ? 'selected' : ''; ?>>Sort by Card Number</option>
                            <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Sort by Quantity (Most First)</option>
                            <option value="date_added" <?php echo $sort === 'date_added' ? 'selected' : ''; ?>>Sort by Date Added</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="collection.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <div class="results-count">
                <p>Showing <?php echo count($collection); ?> card(s)</p>
            </div>
        <?php endif; ?>

        <?php if (empty($collection)): ?>
            <div class="empty-state">
                <p>Your collection is empty. Start adding cards!</p>
                <button class="btn btn-primary" onclick="document.getElementById('addCardBtn').click()">Add Your First Card</button>
            </div>
        <?php else: ?>
            <div class="card-gallery">
                <?php foreach ($collection as $card): ?>
                    <div class="gallery-card-item collection-card"
                         data-card-id="<?php echo $card['id']; ?>"
                         data-rarity="<?php echo strtolower($card['rarity']); ?>"
                         onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card), ENT_QUOTES, 'UTF-8'); ?>)">
                        <div class="card-quantity">x<?php echo $card['quantity']; ?></div>
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
                        <div class="card-actions" onclick="event.stopPropagation()">
                            <button class="btn-icon" onclick="updateQuantity(<?php echo $card['id']; ?>, 1)" title="Add one">+</button>
                            <button class="btn-icon" onclick="updateQuantity(<?php echo $card['id']; ?>, -1)" title="Remove one">-</button>
                            <button class="btn-icon btn-danger" onclick="removeCard(<?php echo $card['id']; ?>)" title="Remove from collection">×</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Add Card Modal -->
    <div id="addCardModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Cards to Collection</h2>

            <input type="text" id="cardSearch" placeholder="Search cards..." class="search-input">

            <div id="cardList" class="modal-card-list">
                <?php foreach ($all_cards as $card): ?>
                    <div class="modal-card-item" data-name="<?php echo strtolower($card['name']); ?>">
                        <div class="modal-card-info">
                            <span class="card-name"><?php echo htmlspecialchars($card['name']); ?></span>
                            <span class="card-meta"><?php echo htmlspecialchars($card['card_code']); ?> - <?php echo $card['rarity']; ?></span>
                        </div>
                        <button class="btn btn-small" onclick="addToCollection(<?php echo $card['id']; ?>)">Add</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

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
                            <div class="card-info-item" id="modalQuantityRow">
                                <span class="card-info-label">You own:</span>
                                <span class="card-info-value" id="modalQuantity"></span>
                            </div>
                        </div>
                    </div>

                    <div class="collection-actions" id="modalCollectionActions">
                        <button class="btn btn-primary btn-full" onclick="addOneFromModal()">Add to Collection</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        let currentModalCardId = null;

        function addOneFromModal() {
            if (currentModalCardId) {
                // Add to collection then redirect back to cards page
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('card_id', currentModalCardId);

                fetch('api/collection.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'cards.php';
                        }, 800);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred', 'error');
                });
            }
        }
    </script>
    <script src="js/card_formatter.js"></script>
    <script src="js/collection.js"></script>
    <script src="js/cards.js"></script>
</body>
</html>
