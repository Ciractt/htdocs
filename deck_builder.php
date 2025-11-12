<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();

// Load deck if editing
$current_deck = null;
$deck_cards = [];
if (!empty($_GET['deck_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['deck_id'], $user['id']]);
    $current_deck = $stmt->fetch();

    if ($current_deck) {
        $stmt = $pdo->prepare("
            SELECT c.*, dc.quantity
            FROM deck_cards dc
            JOIN cards c ON dc.card_id = c.id
            WHERE dc.deck_id = ?
        ");
        $stmt->execute([$current_deck['id']]);
        $deck_cards = $stmt->fetchAll();
    }
}

// Load user's collection for badge display
$stmt = $pdo->prepare("SELECT card_id, quantity FROM user_collections WHERE user_id = ?");
$stmt->execute([$user['id']]);
$user_collection = [];
while ($row = $stmt->fetch()) {
    $user_collection[$row['card_id']] = $row['quantity'];
}

// Load all cards for the library
$all_cards = $pdo->query("
    SELECT * FROM cards
    ORDER BY card_code
")->fetchAll();

// Get unique values for filters
$champions = $pdo->query("SELECT DISTINCT champion FROM cards WHERE champion IS NOT NULL ORDER BY champion")->fetchAll(PDO::FETCH_COLUMN);
$regions = $pdo->query("SELECT DISTINCT region FROM cards ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$sets = $pdo->query("SELECT DISTINCT set_name FROM cards WHERE set_name IS NOT NULL ORDER BY set_name")->fetchAll(PDO::FETCH_COLUMN);

// Load user's saved decks
$stmt = $pdo->prepare("SELECT * FROM decks WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$user['id']]);
$user_decks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deck Builder - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style-dark.css">
    <style>
        .deck-builder-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-top: 2rem;
        }

        .card-library {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .deck-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .deck-header {
            margin-bottom: 1.5rem;
        }

        .deck-name-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #667eea;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .deck-description {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 60px;
            resize: vertical;
            font-size: 0.9rem;
        }

        .deck-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .deck-stat {
            text-align: center;
        }

        .deck-stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .deck-stat-label {
            font-size: 0.85rem;
            color: #666;
        }

        .deck-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .deck-list {
            margin-top: 1rem;
        }

        .deck-section {
            margin-bottom: 1.5rem;
        }

        .deck-section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .deck-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .deck-card:hover {
            background: #f8f9fa;
        }

        .deck-card-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .deck-card-cost {
            background: #3498db;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-weight: bold;
            font-size: 0.9rem;
            min-width: 30px;
            text-align: center;
        }

        .deck-card-name {
            font-size: 0.9rem;
            flex: 1;
        }

        .deck-card-quantity {
            font-weight: bold;
            color: #666;
            margin-right: 0.5rem;
        }

        .deck-card-controls {
            display: flex;
            gap: 0.25rem;
        }

        .library-filters {
            margin-bottom: 1.5rem;
        }

        .library-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
        }

        .library-card {
            position: relative;
            cursor: pointer;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 2/3;
            transition: transform 0.2s;
        }

        .library-card:hover {
            transform: translateY(-4px);
        }

        .library-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .library-card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 0.5rem;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .library-card:hover .library-card-overlay {
            opacity: 1;
        }

        .library-card-name {
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .collection-badge-lib {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .insufficient-copies {
            color: #e74c3c;
        }

        .empty-deck {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .warning-box h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
        }

        .warning-box ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        @media (max-width: 1024px) {
            .deck-builder-container {
                grid-template-columns: 1fr;
            }

            .deck-panel {
                position: static;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>Deck Builder</h1>
            <div>
                <button id="loadDeckBtn" class="btn btn-secondary btn-small">Load Deck</button>
                <a href="deck_builder.php" class="btn btn-secondary btn-small">New Deck</a>
            </div>
        </div>

        <div class="deck-builder-container">
            <!-- LEFT: Card Library -->
            <div class="card-library">
                <h2>Card Library</h2>

                <div class="library-filters">
                    <div class="filters">
                        <form class="filter-form" id="libraryFilters">
                            <div class="filter-group">
                                <input type="text" id="cardSearch" placeholder="Search cards..." class="filter-input">
                            </div>

                            <div class="filter-group">
                                <select id="energyFilter" class="filter-select">
                                    <option value="">All Energy Costs</option>
                                    <?php for ($i = 0; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select id="typeFilter" class="filter-select">
                                    <option value="">All Types</option>
                                    <option value="Champion">Champion</option>
                                    <option value="Unit">Unit</option>
                                    <option value="Spell">Spell</option>
                                    <option value="Landmark">Landmark</option>
                                    <option value="Equipment">Equipment</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select id="rarityFilter" class="filter-select">
                                    <option value="">All Rarities</option>
                                    <option value="Common">Common</option>
                                    <option value="Rare">Rare</option>
                                    <option value="Epic">Epic</option>
                                    <option value="Champion">Champion</option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <select id="regionFilter" class="filter-select">
                                    <option value="">All Regions</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo htmlspecialchars($region); ?>">
                                            <?php echo htmlspecialchars($region); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="library-grid" id="libraryGrid">
                    <?php foreach ($all_cards as $card): ?>
                        <div class="library-card"
                             data-card-id="<?php echo $card['id']; ?>"
                             data-name="<?php echo strtolower($card['name']); ?>"
                             data-energy="<?php echo $card['energy']; ?>"
                             data-type="<?php echo strtolower($card['card_type']); ?>"
                             data-rarity="<?php echo strtolower($card['rarity']); ?>"
                             data-region="<?php echo strtolower($card['region'] ?? ''); ?>"
                             onclick="addCardToDeck(<?php echo $card['id']; ?>, '<?php echo htmlspecialchars($card['name'], ENT_QUOTES); ?>', <?php echo $card['energy'] ?? 0; ?>)">

                            <?php if ($card['card_art_url']): ?>
                                <img src="<?php echo htmlspecialchars($card['card_art_url']); ?>"
                                     alt="<?php echo htmlspecialchars($card['name']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="card-placeholder-full">
                                    <span><?php echo htmlspecialchars($card['name']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($user_collection[$card['id']])): ?>
                                <div class="collection-badge-lib">
                                    <?php echo $user_collection[$card['id']]; ?>
                                </div>
                            <?php endif; ?>

                            <div class="library-card-overlay">
                                <div class="library-card-name"><?php echo htmlspecialchars($card['name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RIGHT: Deck Panel -->
            <div class="deck-panel">
                <div class="deck-header">
                    <input type="text"
                           id="deckName"
                           class="deck-name-input"
                           placeholder="Deck Name"
                           value="<?php echo $current_deck ? htmlspecialchars($current_deck['deck_name']) : 'Untitled Deck'; ?>">

                    <textarea id="deckDescription"
                              class="deck-description"
                              placeholder="Deck description..."><?php echo $current_deck ? htmlspecialchars($current_deck['description']) : ''; ?></textarea>

                    <input type="hidden" id="deckId" value="<?php echo $current_deck['id'] ?? ''; ?>">
                </div>

                <div class="deck-stats">
                    <div class="deck-stat">
                        <span class="deck-stat-value" id="cardCount">0</span>
                        <span class="deck-stat-label">Cards</span>
                    </div>
                    <div class="deck-stat">
                        <span class="deck-stat-value" id="avgCost">0</span>
                        <span class="deck-stat-label">Avg Cost</span>
                    </div>
                    <div class="deck-stat">
                        <span class="deck-stat-value" id="uniqueCards">0</span>
                        <span class="deck-stat-label">Unique</span>
                    </div>
                </div>

                <div id="deckWarnings"></div>

                <div class="deck-actions">
                    <button id="saveDeckBtn" class="btn btn-primary btn-small">Save Deck</button>
                    <button id="publishToggleBtn" class="btn btn-secondary btn-small" style="display: none;">
                        <span id="publishBtnText">Publish Deck</span>
                    </button>
                    <button id="clearDeckBtn" class="btn btn-secondary btn-small">Clear</button>
                    <button id="exportDeckBtn" class="btn btn-secondary btn-small">Export Code</button>
                </div>

                <?php if ($current_deck && $current_deck['is_published']): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; font-size: 0.9rem;">
                    <strong>✓ This deck is published</strong><br>
                    <small>Other players can view and copy this deck</small>
                    <?php if ($current_deck['featured_card_id']): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT name, card_art_url FROM cards WHERE id = ?");
                        $stmt->execute([$current_deck['featured_card_id']]);
                        $featured_card = $stmt->fetch();
                        if ($featured_card):
                        ?>
                        <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <img src="<?php echo htmlspecialchars($featured_card['card_art_url']); ?>"
                                 style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;"
                                 alt="Featured card">
                            <small>Featured card: <?php echo htmlspecialchars($featured_card['name']); ?></small>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="deck-list" id="deckList">
                    <div class="empty-deck">
                        <p>Click cards from the library to add them to your deck</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Load Deck Modal -->
        <div id="loadDeckModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Load Deck</h2>

                <?php if (empty($user_decks)): ?>
                    <p>You don't have any saved decks yet.</p>
                <?php else: ?>
                    <div class="modal-card-list">
                        <?php foreach ($user_decks as $deck): ?>
                            <div class="modal-card-item">
                                <div class="modal-card-info">
                                    <div>
                                        <strong><?php echo htmlspecialchars($deck['deck_name']); ?></strong>
                                        <br>
                                        <small>Updated: <?php echo date('M j, Y', strtotime($deck['updated_at'])); ?></small>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="deck_builder.php?deck_id=<?php echo $deck['id']; ?>" class="btn btn-small">Load</a>
                                    <button class="btn btn-danger btn-small" onclick="deleteDeck(<?php echo $deck['id']; ?>)">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Featured Card Selection Modal -->
        <div id="featuredCardModal" class="modal">
            <div class="modal-content" style="max-width: 900px;">
                <span class="close" onclick="closeFeaturedCardModal()">&times;</span>
                <h2>Select Featured Card</h2>
                <p style="color: #666; margin-bottom: 1rem;">Choose a card from your deck to represent it in the community</p>

                <div id="featuredCardGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; max-height: 60vh; overflow-y: auto; padding: 1rem; border: 1px solid #ddd; border-radius: 8px;">
                    <!-- Cards will be populated by JavaScript -->
                </div>

                <div style="margin-top: 1rem; text-align: center;">
                    <button id="confirmFeaturedCard" class="btn btn-primary" style="display: none;">Confirm & Publish</button>
                    <button onclick="closeFeaturedCardModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Pass PHP data to JavaScript
        const currentDeckCards = <?php echo json_encode($deck_cards); ?>;
        const userCollection = <?php echo json_encode($user_collection); ?>;
        const allCardsData = <?php echo json_encode($all_cards); ?>;
        const currentDeckPublished = <?php echo $current_deck && $current_deck['is_published'] ? 'true' : 'false'; ?>;
        const currentFeaturedCardId = <?php echo $current_deck && $current_deck['featured_card_id'] ? $current_deck['featured_card_id'] : 'null'; ?>;

        // Create card database for quick lookups
        const cardDatabase = {};
        allCardsData.forEach(card => {
            cardDatabase[card.id] = card;
        });
    </script>
    <script src="js/main.js"></script>
    <script src="js/deck_builder.js"></script>
</body>
</html>
