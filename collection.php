<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();

// Load user's collection
$stmt = $pdo->prepare("SELECT card_id, quantity FROM user_collections WHERE user_id = ?");
$stmt->execute([$user['id']]);
$user_collection = [];
while ($row = $stmt->fetch()) {
    $user_collection[$row['card_id']] = $row['quantity'];
}

// Load user's wishlist
$stmt = $pdo->prepare("SELECT card_id FROM user_wishlist WHERE user_id = ?");
$stmt->execute([$user['id']]);
$user_wishlist = [];
while ($row = $stmt->fetch()) {
    $user_wishlist[] = $row['card_id'];
}

// Load all cards
$all_cards = $pdo->query("
    SELECT * FROM cards
    ORDER BY card_code
")->fetchAll();

// Calculate collection statistics
$total_unique_cards = count($all_cards);
$owned_cards = count($user_collection);
$total_cards_count = array_sum($user_collection);

// Calculate collection value (if you have a 'price' field)
$collection_value = 0;
foreach ($user_collection as $card_id => $quantity) {
    $stmt = $pdo->prepare("SELECT price FROM cards WHERE id = ?");
    $stmt->execute([$card_id]);
    $card = $stmt->fetch();
    if ($card && isset($card['price'])) {
        $collection_value += floatval($card['price']) * $quantity;
    }
}

// Get filter options
$regions = $pdo->query("SELECT DISTINCT region FROM cards WHERE region IS NOT NULL ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$rarities = $pdo->query("SELECT DISTINCT rarity FROM cards WHERE rarity IS NOT NULL ORDER BY rarity")->fetchAll(PDO::FETCH_COLUMN);
$card_types = $pdo->query("SELECT DISTINCT card_type FROM cards WHERE card_type NOT IN ('Rune', 'Battlefield') ORDER BY card_type")->fetchAll(PDO::FETCH_COLUMN);
$sets = $pdo->query("SELECT DISTINCT set_name FROM cards WHERE set_name IS NOT NULL ORDER BY set_name")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collection - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .collection-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .collection-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .collection-controls {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .filter-group {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
            min-width: 140px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-input {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            min-width: 250px;
            font-size: 0.9rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Collection Status Bar (matches cards.php info bar) */
        .collection-status-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .status-text {
            font-size: 0.95rem;
            color: #555;
        }

        .status-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .view-toggle {
            display: flex;
            gap: 0;
            background: #f0f0f0;
            padding: 3px;
            border-radius: 6px;
        }

        .view-toggle-btn {
            padding: 0.4rem 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            color: #666;
        }

        .view-toggle-btn:hover {
            color: #667eea;
        }

        .view-toggle-btn.active {
            background: white;
            color: #667eea;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .wishlist-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            padding: 0.4rem 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .wishlist-toggle:hover {
            background: #e9ecef;
        }

        .wishlist-toggle input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .wishlist-toggle input[type="checkbox"]:checked + span {
            color: #e74c3c;
            font-weight: 600;
        }

        .collection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
            padding: 1rem 0;
        }

        .collection-card {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .collection-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .collection-card.unowned {
            filter: grayscale(100%);
            opacity: 0.4;
            transition: all 0.3s ease;
        }

        .collection-card.unowned:hover {
            filter: grayscale(0%);
            opacity: 1;
            transform: translateY(-4px);
        }

        .card-image-container {
            position: relative;
            cursor: pointer;
        }

        .card-image-container img {
            width: 100%;
            display: block;
        }

        .card-quantity-badge {
            /* Removed - quantity now only shown in controls */
            display: none;
        }

        .card-energy-badge {
            /* Hidden - already visible on card image */
            display: none;
        }

        .card-rarity-badge {
            position: absolute;
            bottom: 8px;
            left: 8px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .card-rarity-badge.common { background: #95a5a6; color: white; }
        .card-rarity-badge.uncommon { background: #3498db; color: white; }
        .card-rarity-badge.rare { background: #f39c12; color: white; }
        .card-rarity-badge.epic { background: #9b59b6; color: white; }
        .card-rarity-badge.champion { background: #e74c3c; color: white; }
        .card-rarity-badge.legend { background: #16a085; color: white; }
        .card-rarity-badge.showcase {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 200% 200%;
            animation: shimmer 3s ease infinite;
            color: white;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .card-value-badge {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(46, 204, 113, 0.95);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .card-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #667eea;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .quantity-btn:hover {
            background: #5568d3;
            transform: scale(1.1);
        }

        .quantity-btn:disabled {
            background: #ddd;
            cursor: not-allowed;
            transform: none;
        }

        .quantity-display {
            min-width: 40px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .collection-card[data-owned="1"] .quantity-display {
            background: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }

        .collection-card[data-owned="0"] .quantity-display {
            color: #999;
        }

        .wishlist-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1.3rem;
            transition: all 0.2s;
            border-radius: 50%;
        }

        .wishlist-btn:hover {
            background: rgba(0, 0, 0, 0.05);
            transform: scale(1.2);
        }

        .wishlist-btn.active {
            color: #e74c3c;
        }

        .info-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1rem;
            color: #667eea;
            transition: all 0.2s;
            border-radius: 50%;
        }

        .info-btn:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .collection-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 1rem;
            }

            .collection-controls,
            .collection-status-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex-direction: column;
                width: 100%;
            }

            .filter-select,
            .search-input {
                width: 100%;
            }

            .status-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .view-toggle {
                width: 100%;
            }

            .view-toggle-btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="collection-header">
            <h1>My Collection</h1>

            <div class="collection-stats">
                <div class="stat-card">
                    <span class="stat-value"><?php echo number_format($collection_value, 2); ?> €</span>
                    <span class="stat-label">Collection Value</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $total_cards_count; ?></span>
                    <span class="stat-label">Total Cards</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $owned_cards; ?> / <?php echo $total_unique_cards; ?></span>
                    <span class="stat-label">Unique Cards</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $owned_cards > 0 ? round(($owned_cards / $total_unique_cards) * 100) : 0; ?>%</span>
                    <span class="stat-label">Completion</span>
                </div>
            </div>
        </div>

        <!-- Unified Filter Bar (matching cards.php) -->
        <div class="collection-controls">
            <!-- Left side: Main filters -->
            <div class="filter-group">
                <input type="text" id="searchInput" class="search-input" placeholder="Search cards...">

                <select id="setFilter" class="filter-select">
                    <option value="">All Sets</option>
                    <?php foreach ($sets as $set): ?>
                        <option value="<?php echo htmlspecialchars($set); ?>"><?php echo htmlspecialchars($set); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="regionFilter" class="filter-select">
                    <option value="">All Regions</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?php echo htmlspecialchars($region); ?>"><?php echo htmlspecialchars($region); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="typeFilter" class="filter-select">
                    <option value="">All Types</option>
                    <?php foreach ($card_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="rarityFilter" class="filter-select">
                    <option value="">All Rarities</option>
                    <?php foreach ($rarities as $rarity): ?>
                        <option value="<?php echo htmlspecialchars($rarity); ?>"><?php echo htmlspecialchars($rarity); ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="btn btn-secondary btn-small" onclick="resetFilters()">Reset Filters</button>
            </div>
        </div>

        <!-- Collection Status Bar (matching cards.php info bar) -->
        <div class="collection-status-bar">
            <div class="status-info">
                <span class="status-text">
                    <strong>Cards Owned:</strong> <?php echo $owned_cards; ?> / <?php echo $total_unique_cards; ?>
                    (<?php echo $owned_cards > 0 ? round(($owned_cards / $total_unique_cards) * 100) : 0; ?>%)
                </span>
            </div>

            <div class="status-controls">
                <!-- View Toggle -->
                <div class="view-toggle">
                    <button class="view-toggle-btn" data-view="unowned">Unowned</button>
                    <button class="view-toggle-btn active" data-view="all">All</button>
                    <button class="view-toggle-btn" data-view="owned">Owned</button>
                </div>

                <!-- Wishlist Toggle -->
                <label class="wishlist-toggle">
                    <input type="checkbox" id="wishlistFilter">
                    <span>❤️ Wishlist</span>
                </label>

                <!-- Sort -->
                <select id="sortSelect" class="filter-select">
                    <option value="code-asc">Sort: Card Code (A-Z)</option>
                    <option value="code-desc">Sort: Card Code (Z-A)</option>
                    <option value="name-asc">Sort: Name (A-Z)</option>
                    <option value="name-desc">Sort: Name (Z-A)</option>
                    <option value="energy-asc">Sort: Energy (Low-High)</option>
                    <option value="energy-desc">Sort: Energy (High-Low)</option>
                    <option value="rarity-asc">Sort: Rarity</option>
                </select>
            </div>
        </div>

        <div class="collection-grid" id="collectionGrid">
            <?php foreach ($all_cards as $card): ?>
                <?php
                    $owned_quantity = $user_collection[$card['id']] ?? 0;
                    $is_owned = $owned_quantity > 0;
                    $is_wishlisted = in_array($card['id'], $user_wishlist);
                ?>
                <div class="collection-card <?php echo !$is_owned ? 'unowned' : ''; ?>"
                     data-card-id="<?php echo $card['id']; ?>"
                     data-card-code="<?php echo strtolower($card['card_code'] ?? ''); ?>"
                     data-owned="<?php echo $is_owned ? '1' : '0'; ?>"
                     data-wishlisted="<?php echo $is_wishlisted ? '1' : '0'; ?>"
                     data-set="<?php echo strtolower($card['set_name'] ?? ''); ?>"
                     data-region="<?php echo strtolower($card['region'] ?? ''); ?>"
                     data-rarity="<?php echo strtolower($card['rarity'] ?? ''); ?>"
                     data-type="<?php echo strtolower($card['card_type'] ?? ''); ?>"
                     data-name="<?php echo strtolower($card['name']); ?>"
                     data-energy="<?php echo $card['energy'] ?? 0; ?>">

                    <div class="card-image-container" onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card)); ?>)">
                        <?php if ($card['card_art_url']): ?>
                            <img src="<?php echo htmlspecialchars($card['card_art_url']); ?>"
                                 alt="<?php echo htmlspecialchars($card['name']); ?>">
                        <?php else: ?>
                            <div style="aspect-ratio: 2/3; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <span><?php echo htmlspecialchars($card['name']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($card['rarity']): ?>
                            <div class="card-rarity-badge <?php echo strtolower($card['rarity']); ?>">
                                <?php echo htmlspecialchars($card['rarity']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-controls">
                        <div class="quantity-controls">
                            <button class="quantity-btn"
                                    onclick="updateQuantity(<?php echo $card['id']; ?>, -1)"
                                    <?php echo $owned_quantity <= 0 ? 'disabled' : ''; ?>>
                                −
                            </button>
                            <span class="quantity-display"><?php echo $owned_quantity; ?></span>
                            <button class="quantity-btn"
                                    onclick="updateQuantity(<?php echo $card['id']; ?>, 1)">
                                +
                            </button>
                        </div>

                        <button class="wishlist-btn <?php echo $is_wishlisted ? 'active' : ''; ?>"
                                onclick="toggleWishlist(<?php echo $card['id']; ?>)">
                            <?php echo $is_wishlisted ? '❤️' : '🤍'; ?>
                        </button>

                        <button class="info-btn" onclick="showCardDetails(<?php echo htmlspecialchars(json_encode($card)); ?>)">
                            ℹ️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-state-icon">📦</div>
            <h3>No cards found</h3>
            <p>Try adjusting your filters or search query</p>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/card_detail_modal.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/card_formatter.js"></script>
    <script src="js/cards.js"></script>
    <script src="js/collection.js"></script>
</body>
</html>
