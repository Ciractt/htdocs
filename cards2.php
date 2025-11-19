<?php
require_once 'config.php';

$pdo = getDB();

// Get filter parameters
$search    = $_GET['search']    ?? '';
$champion  = $_GET['champion']  ?? '';
$rarity    = $_GET['rarity']    ?? '';
$card_type = $_GET['type']      ?? '';
$region    = $_GET['region']    ?? '';
$sort      = $_GET['sort']      ?? 'card_number';

// Build query
$query = "SELECT * FROM cards WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR card_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($champion) {
    $query .= " AND champion = ?";
    $params[] = $champion;
}
if ($rarity) {
    $query .= " AND rarity = ?";
    $params[] = $rarity;
}
if ($card_type) {
    $query .= " AND card_type = ?";
    $params[] = $card_type;
}
if ($region) {
    $query .= " AND region = ?";
    $params[] = $region;
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
$champions = $pdo->query("SELECT DISTINCT champion FROM cards WHERE champion IS NOT NULL ORDER BY champion")->fetchAll(PDO::FETCH_COLUMN);
$regions   = $pdo->query("SELECT DISTINCT region FROM cards ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Database - <?php echo SITE_NAME; ?></title>

    <!-- SINGLE CSS FILE -->
    <link rel="stylesheet" href="css/theme.css">
    <style>
        /* ============================================
           UNIFIED CARD GRID - Matches index.php
           ============================================ */
        
        .unified-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }

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

        .unified-card-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform var(--transition-slow);
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

        /* Card-only style (no content area) */
        .unified-card.card-only {
            aspect-ratio: 515/719;
        }

        .unified-card.card-only .unified-card-image {
            aspect-ratio: unset;
            height: 100%;
        }

        /* Rarity-based hover effects */
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
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .unified-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .unified-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .unified-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .unified-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: var(--spacing-md);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1>Card Database</h1>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search by name or card code..."
                           value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                </div>

                <div class="filter-group">
                    <select name="champion" class="filter-select">
                        <option value="">All Champions</option>
                        <?php foreach ($champions as $champ): ?>
                            <option value="<?php echo htmlspecialchars($champ); ?>" <?php echo $champion === $champ ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($champ); ?>
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

                <div class="filter-group">
                    <select name="region" class="filter-select">
                        <option value="">All Regions</option>
                        <?php foreach ($regions as $reg): ?>
                            <option value="<?php echo htmlspecialchars($reg); ?>" <?php echo $region === $reg ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($reg); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="sort" class="filter-select">
                        <option value="card_number" <?php echo $sort === 'card_number' ? 'selected' : ''; ?>>Sort by Card Number</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="cards2.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>

        <div class="results-count">
            <p>Showing <?php echo count($cards); ?> card(s)</p>
        </div>

        <!-- Card Gallery - Unified Design -->
        <div class="unified-grid">
            <?php foreach ($cards as $card): ?>
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
    </main>

    <!-- Card Detail Modal -->
    <div id="cardDetailModal" class="modal">
        <div class="modal-content card-detail-modal">
            <span class="close">×</span>
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

    <script src="js/main.js"></script>
    <script src="js/card_formatter.js"></script>
    <script src="js/cards.js"></script>
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
                console.error('Error:', error);
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
</body>
</html>