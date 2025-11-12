<?php
require_once 'config.php';

$pdo = getDB();

// Get filter parameters
$search = $_GET['search'] ?? '';
$color = $_GET['color'] ?? '';
$set = $_GET['set'] ?? '';
$card_type = $_GET['type'] ?? '';
$rarity = $_GET['rarity'] ?? '';
$champion = $_GET['champion'] ?? '';
$region = $_GET['region'] ?? '';
$sort = $_GET['sort'] ?? 'card_number';

// Range filters
$energy_min = isset($_GET['energy_min']) ? intval($_GET['energy_min']) : 0;
$energy_max = isset($_GET['energy_max']) ? intval($_GET['energy_max']) : 12;
$might_min = isset($_GET['might_min']) ? intval($_GET['might_min']) : 0;
$might_max = isset($_GET['might_max']) ? intval($_GET['might_max']) : 10;
$power_min = isset($_GET['power_min']) ? intval($_GET['power_min']) : 0;
$power_max = isset($_GET['power_max']) ? intval($_GET['power_max']) : 10;

// Build query
$query = "SELECT * FROM cards WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR description LIKE ? OR card_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($color) {
    $query .= " AND color = ?";
    $params[] = $color;
}
if ($set) {
    $query .= " AND set_name = ?";
    $params[] = $set;
}
if ($card_type) {
    $query .= " AND card_type = ?";
    $params[] = $card_type;
}
if ($rarity) {
    $query .= " AND rarity = ?";
    $params[] = $rarity;
}
if ($champion) {
    $query .= " AND champion = ?";
    $params[] = $champion;
}
if ($region) {
    $query .= " AND region = ?";
    $params[] = $region;
}

/// Only apply stat filters if user changed from default
if ($energy_min > 0 || $energy_max < 12) {
    $query .= " AND energy BETWEEN ? AND ?";
    $params[] = $energy_min;
    $params[] = $energy_max;
}

if ($might_min > 0 || $might_max < 10) {
    $query .= " AND might BETWEEN ? AND ?";
    $params[] = $might_min;
    $params[] = $might_max;
}

if ($power_min > 0 || $power_max < 10) {
    $query .= " AND power BETWEEN ? AND ?";
    $params[] = $power_min;
    $params[] = $power_max;
}

// Apply sorting
if ($sort === 'name') {
    $query .= " ORDER BY name";
} else {
    $query .= " ORDER BY card_code";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$cards = $stmt->fetchAll();

// Get unique values for filters
$colors = $pdo->query("SELECT DISTINCT color FROM cards WHERE color IS NOT NULL ORDER BY color")->fetchAll(PDO::FETCH_COLUMN);
$sets = $pdo->query("SELECT DISTINCT set_name FROM cards WHERE set_name IS NOT NULL ORDER BY set_name")->fetchAll(PDO::FETCH_COLUMN);
$types = $pdo->query("SELECT DISTINCT card_type FROM cards ORDER BY card_type")->fetchAll(PDO::FETCH_COLUMN);
$rarities = $pdo->query("SELECT DISTINCT rarity FROM cards ORDER BY rarity")->fetchAll(PDO::FETCH_COLUMN);
$champions = $pdo->query("SELECT DISTINCT champion FROM cards WHERE champion IS NOT NULL ORDER BY champion")->fetchAll(PDO::FETCH_COLUMN);
$regions = $pdo->query("SELECT DISTINCT region FROM cards WHERE region IS NOT NULL ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Database - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style-dark.css">
    <link rel="stylesheet" href="css/advanced_search.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1>Card Database</h1>

        <div class="search-container">
            <form method="GET" id="searchForm">
                <!-- Main Search Bar -->
                <div class="search-bar-wrapper">
                    <input type="text"
                           name="search"
                           placeholder="Search cards... (name, description, card code)"
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="main-search-input">
                </div>

                <!-- Filters Panel (Always Visible) -->
                <div class="filters-panel">
                    <div class="filters-grid">
                        <!-- Color Filters -->
                        <div class="filter-section">
                            <h3 class="filter-title">Colors</h3>
                            <div class="color-grid">
                                <button type="button"
                                        class="color-btn <?php echo $color === 'Fury' ? 'active' : ''; ?>"
                                        data-color="Fury"
                                        title="Fury">
                                    <img src="https://cdn.piltoverarchive.com/colors/Fury.webp"
                                         alt="Fury"
                                         onerror="this.style.display='none'">
                                </button>
                                <button type="button"
                                        class="color-btn <?php echo $color === 'Calm' ? 'active' : ''; ?>"
                                        data-color="Calm"
                                        title="Calm">
                                    <img src="https://cdn.piltoverarchive.com/colors/Calm.webp"
                                         alt="Calm"
                                         onerror="this.style.display='none'">
                                </button>
                                <button type="button"
                                        class="color-btn <?php echo $color === 'Mind' ? 'active' : ''; ?>"
                                        data-color="Mind"
                                        title="Mind">
                                    <img src="https://cdn.piltoverarchive.com/colors/Mind.webp"
                                         alt="Mind"
                                         onerror="this.style.display='none'">
                                </button>
                                <button type="button"
                                        class="color-btn <?php echo $color === 'Body' ? 'active' : ''; ?>"
                                        data-color="Body"
                                        title="Body">
                                    <img src="https://cdn.piltoverarchive.com/colors/Body.webp"
                                         alt="Body"
                                         onerror="this.style.display='none'">
                                </button>
                                <button type="button"
                                        class="color-btn <?php echo $color === 'Chaos' ? 'active' : ''; ?>"
                                        data-color="Chaos"
                                        title="Chaos">
                                    <img src="https://cdn.piltoverarchive.com/colors/Chaos.webp"
                                         alt="Chaos"
                                         onerror="this.style.display='none'">
                                </button>
                                <button type="button"
                                        class="color-btn <?php echo $color === 'Order' ? 'active' : ''; ?>"
                                        data-color="Order"
                                        title="Order">
                                    <img src="https://cdn.piltoverarchive.com/colors/Order.webp"
                                         alt="Order"
                                         onerror="this.style.display='none'">
                                </button>
                            </div>
                            <input type="hidden" name="color" id="colorInput" value="<?php echo htmlspecialchars($color); ?>">
                        </div>

                        <!-- Dropdowns -->
                        <div class="filter-section">
                            <div class="filter-group">
                                <label>Set</label>
                                <select name="set" class="filter-select">
                                    <option value="">All Sets</option>
                                    <?php foreach ($sets as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $set === $s ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Card Type</label>
                                <select name="type" class="filter-select">
                                    <option value="">All Card Types</option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $card_type === $t ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Rarity</label>
                                <select name="rarity" class="filter-select">
                                    <option value="">All Rarities</option>
                                    <?php foreach ($rarities as $r): ?>
                                        <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $rarity === $r ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Range Sliders -->
                        <div class="filter-section">
                            <div class="range-filter">
                                <div class="range-header">
                                    <label>Energy (0-12)</label>
                                    <span class="range-value" id="energyValue">
                                        <?php echo $energy_min === 0 && $energy_max === 12 ? 'Any' : "$energy_min - $energy_max"; ?>
                                    </span>
                                </div>
                                <div class="dual-range-slider">
                                    <input type="range" name="energy_min" min="0" max="12" value="<?php echo $energy_min; ?>" class="range-slider range-min" id="energyMin">
                                    <input type="range" name="energy_max" min="0" max="12" value="<?php echo $energy_max; ?>" class="range-slider range-max" id="energyMax">
                                    <div class="slider-track"></div>
                                </div>
                                <div class="range-labels">
                                    <span>0</span>
                                    <span>6</span>
                                    <span>12</span>
                                </div>
                            </div>

                            <div class="range-filter">
                                <div class="range-header">
                                    <label>Might (0-10)</label>
                                    <span class="range-value" id="mightValue">
                                        <?php echo $might_min === 0 && $might_max === 10 ? 'Any' : "$might_min - $might_max"; ?>
                                    </span>
                                </div>
                                <div class="dual-range-slider">
                                    <input type="range" name="might_min" min="0" max="10" value="<?php echo $might_min; ?>" class="range-slider range-min" id="mightMin">
                                    <input type="range" name="might_max" min="0" max="10" value="<?php echo $might_max; ?>" class="range-slider range-max" id="mightMax">
                                    <div class="slider-track"></div>
                                </div>
                                <div class="range-labels">
                                    <span>0</span>
                                    <span>5</span>
                                    <span>10</span>
                                </div>
                            </div>

                            <div class="range-filter">
                                <div class="range-header">
                                    <label>Power (0-10)</label>
                                    <span class="range-value" id="powerValue">
                                        <?php echo $power_min === 0 && $power_max === 10 ? 'Any' : "$power_min - $power_max"; ?>
                                    </span>
                                </div>
                                <div class="dual-range-slider">
                                    <input type="range" name="power_min" min="0" max="10" value="<?php echo $power_min; ?>" class="range-slider range-min" id="powerMin">
                                    <input type="range" name="power_max" min="0" max="10" value="<?php echo $power_max; ?>" class="range-slider range-max" id="powerMax">
                                    <div class="slider-track"></div>
                                </div>
                                <div class="range-labels">
                                    <span>0</span>
                                    <span>2</span>
                                    <span>4</span>
                                    <span>6</span>
                                    <span>8</span>
                                    <span>10</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="cards_advanced.php" class="btn btn-secondary">Clear All</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="results-header">
            <p>Showing <?php echo count($cards); ?> card(s)</p>
            <select name="sort" class="sort-select" onchange="document.getElementById('searchForm').submit()">
                <option value="card_number" <?php echo $sort === 'card_number' ? 'selected' : ''; ?>>Sort by Card Number</option>
                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
            </select>
        </div>

        <div class="card-gallery">
            <?php foreach ($cards as $card): ?>
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
    </main>

    <!-- Card Detail Modal (same as cards.php) -->
    <div id="cardDetailModal" class="modal">
        <div class="modal-content card-detail-modal">
            <span class="close">&times;</span>
            <div class="card-detail-layout">
                <div class="card-detail-image">
                    <img id="modalCardImage" src="" alt="">
                </div>
                <div class="card-detail-info">
                    <h2 id="modalCardName"></h2>
                    <div class="card-badges" id="modalBadges"></div>
                    <div class="card-pills" id="modalPills"></div>
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
                    <div class="card-section">
                        <h3 class="card-section-title">Description</h3>
                        <div class="detail-description">
                            <p id="modalDescription"></p>
                        </div>
                    </div>
                    <div class="card-section" id="modalFlavorSection" style="display: none;">
                        <h3 class="card-section-title">Flavor Text</h3>
                        <p class="card-flavor-text" id="modalFlavorText"></p>
                    </div>
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
                    <div class="collection-actions" id="modalCollectionActions">
                        <button class="btn btn-primary btn-full" onclick="addToCollectionFromModal()">Add to Collection</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        window.currentModalCardId = null;

        async function addToCollectionFromModal() {
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
                showNotification('An error occurred', 'error');
            }
        }

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
    <script src="js/main.js"></script>
    <script src="js/card_formatter.js"></script>
    <script src="js/cards.js"></script>
    <script src="js/advanced_search.js"></script>
</body>
</html>
