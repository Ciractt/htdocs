<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();

// Load deck if editing
$current_deck = null;
$deck_data = [
    'champion_legend_id' => null,
    'chosen_champion_id' => null,
    'main_deck' => [],
    'rune_deck' => [],
    'battlefields' => []
];

if (!empty($_GET['deck_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['deck_id'], $user['id']]);
    $current_deck = $stmt->fetch();

    if ($current_deck) {
        // Load champion legend
        if ($current_deck['champion_legend_id']) {
            $deck_data['champion_legend_id'] = $current_deck['champion_legend_id'];
        }

        // Load chosen champion
        if ($current_deck['chosen_champion_id']) {
            $deck_data['chosen_champion_id'] = $current_deck['chosen_champion_id'];
        }

        // Load deck cards
        $stmt = $pdo->prepare("
            SELECT c.*, dc.quantity, dc.deck_slot
            FROM deck_cards dc
            JOIN cards c ON dc.card_id = c.id
            WHERE dc.deck_id = ?
        ");
        $stmt->execute([$current_deck['id']]);
        $all_deck_cards = $stmt->fetchAll();

        // Organize by slot
        foreach ($all_deck_cards as $card) {
            $slot = $card['deck_slot'] ?? 'main_deck';
            if (!isset($deck_data[$slot])) {
                $deck_data[$slot] = [];
            }
            $deck_data[$slot][] = $card;
        }
    }
}

// Load user's collection
$stmt = $pdo->prepare("SELECT card_id, quantity FROM user_collections WHERE user_id = ?");
$stmt->execute([$user['id']]);
$user_collection = [];
while ($row = $stmt->fetch()) {
    $user_collection[$row['card_id']] = $row['quantity'];
}

// Load all cards organized by type
$all_cards = $pdo->query("SELECT * FROM cards ORDER BY card_code")->fetchAll();

// Organize cards by type for easy access
$champion_legends = array_filter($all_cards, fn($c) => $c['card_type'] === 'Legend');
$champion_units = array_filter($all_cards, fn($c) => $c['card_type'] === 'Champion' || $c['rarity'] === 'Champion');
$rune_cards = array_filter($all_cards, fn($c) => $c['card_type'] === 'Rune');
$battlefield_cards = array_filter($all_cards, fn($c) => $c['card_type'] === 'Battlefield');
$main_deck_cards = array_filter($all_cards, fn($c) =>
    !in_array($c['card_type'], ['Legend', 'Rune', 'Battlefield'])
);

// Get unique filter values
$regions = $pdo->query("SELECT DISTINCT region FROM cards WHERE region IS NOT NULL ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$champion_tags = $pdo->query("SELECT DISTINCT champion FROM cards WHERE champion IS NOT NULL ORDER BY champion")->fetchAll(PDO::FETCH_COLUMN);

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
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/deck_builder_v2.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1>Riftbound Deck Builder</h1>

        <!-- Rule-Enforced Deck Building Steps -->
        <div class="deck-builder-wizard">

            <!-- Step 1: Champion Legend Selection -->
            <div class="builder-step" id="step-legend">
                <div class="step-header">
                    <h2>Step 1: Choose Your Champion Legend</h2>
                    <p class="step-description">Your Champion Legend determines your deck's Domain Identity</p>
                </div>

                <div class="champion-legend-selector">
                    <div class="legend-grid">
                        <?php foreach ($champion_legends as $legend): ?>
                            <div class="legend-card <?php echo $deck_data['champion_legend_id'] == $legend['id'] ? 'selected' : ''; ?>"
                                 data-legend-id="<?php echo $legend['id']; ?>"
                                 data-champion-tag="<?php echo htmlspecialchars($legend['champion'] ?? ''); ?>"
                                 data-region="<?php echo htmlspecialchars($legend['region'] ?? ''); ?>"
                                 onclick="selectChampionLegend(<?php echo htmlspecialchars(json_encode($legend)); ?>)">

                                <?php if ($legend['card_art_url']): ?>
                                    <img src="<?php echo htmlspecialchars($legend['card_art_url']); ?>"
                                         alt="<?php echo htmlspecialchars($legend['name']); ?>">
                                <?php endif; ?>

                                <div class="legend-info">
                                    <div class="legend-name"><?php echo htmlspecialchars($legend['name']); ?></div>
                                    <?php if ($legend['region']): ?>
                                        <div class="legend-domain"><?php echo htmlspecialchars($legend['region']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($deck_data['champion_legend_id'] == $legend['id']): ?>
                                    <div class="selected-badge">✓ Selected</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Deck Builder Interface -->
            <div class="builder-main" id="main-builder" style="display: none;">
                <div class="deck-builder-container">

                    <!-- LEFT: Card Library -->
                    <div class="card-library">
                        <!-- Deck Building Tabs -->
                        <div class="deck-tabs">
                            <button class="deck-tab active" data-tab="main-deck">
                                Main Deck
                                <span class="tab-count" id="mainDeckCount">0/40+</span>
                            </button>
                            <button class="deck-tab" data-tab="rune-deck">
                                Rune Deck
                                <span class="tab-count" id="runeDeckCount">0/12</span>
                            </button>
                            <button class="deck-tab" data-tab="battlefields">
                                Battlefields
                                <span class="tab-count" id="battlefieldCount">0</span>
                            </button>
                        </div>

                        <!-- Main Deck Tab -->
                        <div class="tab-content active" id="main-deck-content">
                            <!-- Chosen Champion Selection -->
                            <div class="chosen-champion-section">
                                <h3>Chosen Champion <span style="font-size: 0.85rem; font-weight: normal; color: #999;">(Required)</span></h3>
                                <p style="font-size: 0.85rem; color: #666; margin: 0 0 1rem 0;">
                                    Click on a Champion Unit below that matches your Legend's tag.
                                    <strong>Look for cards with a green border and "Set as Champion" badge.</strong>
                                </p>
                                <div id="chosenChampionSlot" class="champion-slot">
                                    <p class="empty-slot">No Chosen Champion selected yet.<br><small>Click a matching champion card below to set.</small></p>
                                </div>
                            </div>

                            <!-- Card Filters -->
                            <div class="filters">
                                <input type="text" id="mainSearchInput" placeholder="Search cards..." class="search-input">

                                <select id="mainTypeFilter" class="filter-select">
                                    <option value="">All Types</option>
                                    <option value="Champion">Champions</option>
                                    <option value="Unit">Units</option>
                                    <option value="Spell">Spells</option>
                                    <option value="Gear">Gear</option>
                                </select>

                                <select id="mainRarityFilter" class="filter-select">
                                    <option value="">All Rarities</option>
                                    <option value="Common">Common</option>
                                    <option value="Uncommon">Uncommon</option>
                                    <option value="Rare">Rare</option>
                                    <option value="Epic">Epic</option>
                                </select>
                            </div>

                            <div id="championFilterHint" style="background: #e7f3ff; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; font-size: 0.85rem; border-left: 3px solid #667eea;">
                                💡 <strong>Tip:</strong> Filter by "Champions" to see only cards eligible for Chosen Champion
                            </div>

                            <!-- Card Grid -->
                            <div class="card-grid" id="mainDeckCardGrid">
                                <!-- Cards populated by JavaScript -->
                            </div>
                        </div>

                        <!-- Rune Deck Tab -->
                        <div class="tab-content" id="rune-deck-content">
                            <div class="filters">
                                <input type="text" id="runeSearchInput" placeholder="Search runes..." class="search-input">
                            </div>
                            <div class="card-grid" id="runeDeckCardGrid">
                                <!-- Rune cards populated by JavaScript -->
                            </div>
                        </div>

                        <!-- Battlefields Tab -->
                        <div class="tab-content" id="battlefields-content">
                            <div class="filters">
                                <input type="text" id="battlefieldSearchInput" placeholder="Search battlefields..." class="search-input">
                            </div>
                            <div class="card-grid" id="battlefieldCardGrid">
                                <!-- Battlefield cards populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Deck Panel -->
                    <div class="deck-panel">
                        <!-- Deck Header -->
                        <div class="deck-header">
                            <input type="text" id="deckName" class="deck-name-input"
                                   placeholder="Deck Name"
                                   value="<?php echo $current_deck ? htmlspecialchars($current_deck['deck_name']) : 'Untitled Deck'; ?>">

                            <textarea id="deckDescription" class="deck-description"
                                      placeholder="Deck description..."><?php echo $current_deck ? htmlspecialchars($current_deck['description']) : ''; ?></textarea>

                            <input type="hidden" id="deckId" value="<?php echo $current_deck['id'] ?? ''; ?>">
                        </div>

                        <!-- Selected Legend Display -->
                        <div class="selected-legend-display" id="selectedLegendDisplay">
                            <!-- Populated by JavaScript -->
                        </div>

                        <!-- Deck Warnings/Validation -->
                        <div id="deckWarnings" class="deck-warnings">
                            <!-- Validation messages populated by JavaScript -->
                        </div>

                        <!-- Deck Actions -->
                        <div class="deck-actions">
                            <button id="saveDeckBtn" class="btn btn-primary btn-small">Save Deck</button>
                            <button id="clearDeckBtn" class="btn btn-secondary btn-small">Clear</button>
                            <button id="exportDeckBtn" class="btn btn-secondary btn-small">Export</button>
                            <button id="importDeckBtn" class="btn btn-secondary btn-small">Import</button>
                        </div>

                        <!-- Deck List -->
                        <div class="deck-list" id="deckList">
                            <!-- Deck cards populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal (reuse existing) -->
        <?php include 'includes/deck_import_modal.php'; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Pass PHP data to JavaScript
        const currentUser = <?php echo json_encode($user); ?>;
        const userCollection = <?php echo json_encode($user_collection); ?>;
        const currentDeckData = <?php echo json_encode($deck_data); ?>;
        const currentDeckId = <?php echo json_encode($current_deck['id'] ?? null); ?>;

        // All cards organized by type
        window.allCardsData = <?php echo json_encode($all_cards); ?>;
        window.championLegends = <?php echo json_encode(array_values($champion_legends)); ?>;
        window.championUnits = <?php echo json_encode(array_values($champion_units)); ?>;
        window.runeCards = <?php echo json_encode(array_values($rune_cards)); ?>;
        window.battlefieldCards = <?php echo json_encode(array_values($battlefield_cards)); ?>;
        window.mainDeckCards = <?php echo json_encode(array_values($main_deck_cards)); ?>;

        // Create card database for quick lookups
        window.cardDatabase = {};
        window.allCardsData.forEach(card => {
            window.cardDatabase[card.id] = card;
        });

        // Create lookup by card_code for imports (case-insensitive)
        window.cardCodeDatabase = {};
        window.allCardsData.forEach(card => {
            if (card.card_code) {
                window.cardCodeDatabase[card.card_code.toLowerCase()] = card;
            }
        });
    </script>

    <script src="js/main.js"></script>
    <script src="js/deck_builder.js"></script>
    <script src="js/deck_import.js"></script>
</body>
</html>
