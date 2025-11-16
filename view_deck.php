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
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/style-fixes.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        .sidebar-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .sidebar-tab {
            flex: 1;
            padding: 0.75rem 1rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .sidebar-tab:hover {
            color: #667eea;
        }

        .sidebar-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .sidebar-tab-content {
            display: none;
        }

        .sidebar-tab-content.active {
            display: block;
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-section h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #333;
        }

        .chart-container-small {
            position: relative;
            height: 200px;
        }

        .chart-container-small canvas {
            max-height: 200px;
        }

        #sampleHandDisplay {
            min-height: 150px;
        }

        .sample-hand-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .sample-hand-card {
            text-align: center;
        }

        .sample-hand-card img {
            width: 100%;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sample-hand-card-name {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .sample-hand-card-cost {
            font-size: 0.7rem;
            color: #666;
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

            <!-- Sidebar with Statistics -->
            <div class="deck-sidebar">
                <!-- Tabs -->
                <div class="sidebar-tabs">
                    <button class="sidebar-tab active" data-tab="stats">Stats</button>
                    <button class="sidebar-tab" data-tab="sample-hand">Sample Hand</button>
                </div>

                <!-- Stats Tab -->
                <div id="stats-tab" class="sidebar-tab-content active">
                    <!-- Energy Curve -->
                    <div class="sidebar-section">
                        <h4>Energy Curve</h4>
                        <div class="chart-container-small">
                            <canvas id="energyCurveChart"></canvas>
                        </div>
                    </div>

                    <!-- Power Distribution -->
                    <div class="sidebar-section">
                        <h4>Power Distribution</h4>
                        <div class="chart-container-small">
                            <canvas id="powerDistChart"></canvas>
                        </div>
                    </div>

                    <!-- Card Type Distribution -->
                    <div class="sidebar-section">
                        <h4>Card Types</h4>
                        <div class="chart-container-small">
                            <canvas id="cardTypeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Sample Hand Tab -->
                <div id="sample-hand-tab" class="sidebar-tab-content">
                    <div class="sidebar-section">
                        <button id="drawHandBtn" class="btn btn-primary btn-small" style="width: 100%; margin-bottom: 1rem;">
                            Draw Hand
                        </button>
                        <div id="sampleHandDisplay">
                            <p style="text-align: center; color: #999; padding: 2rem 0;">
                                Click "Draw Hand" to see a sample starting hand
                            </p>
                        </div>
                    </div>
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
        // Deck data from PHP
        const deckCards = <?php echo json_encode($deck_cards); ?>;

        // Tab switching
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;

                // Update tab buttons
                document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Update tab content
                document.querySelectorAll('.sidebar-tab-content').forEach(c => c.classList.remove('active'));
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            setupSampleHand();
        });

        function initializeCharts() {
            createEnergyCurveChart();
            createPowerDistributionChart();
            createCardTypeChart();
        }

        function createEnergyCurveChart() {
            const ctx = document.getElementById('energyCurveChart');
            if (!ctx) return;

            // Count cards by energy
            const energyCounts = {};
            deckCards.forEach(card => {
                const energy = card.energy ?? 0;
                const qty = card.quantity || 1;
                energyCounts[energy] = (energyCounts[energy] || 0) + qty;
            });

            const maxEnergy = Math.max(...Object.keys(energyCounts).map(Number), 10);
            const labels = [];
            const data = [];

            for (let i = 0; i <= maxEnergy; i++) {
                labels.push(i.toString());
                data.push(energyCounts[i] || 0);
            }

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cards',
                        data: data,
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Energy Cost'
                            }
                        }
                    }
                }
            });
        }

        function createPowerDistributionChart() {
            const ctx = document.getElementById('powerDistChart');
            if (!ctx) return;

            // Count cards by power
            const powerCounts = {};
            deckCards.forEach(card => {
                const power = card.power || 'None';
                const qty = card.quantity || 1;
                powerCounts[power] = (powerCounts[power] || 0) + qty;
            });

            const labels = Object.keys(powerCounts);
            const data = Object.values(powerCounts);

            const powerColors = {
                'Fury': 'rgba(231, 76, 60, 0.8)',
                'Calm': 'rgba(52, 152, 219, 0.8)',
                'Chaos': 'rgba(155, 89, 182, 0.8)',
                'Calm Chaos': 'rgba(102, 126, 234, 0.8)',
                'Fury Fury': 'rgba(192, 57, 43, 0.8)',
                'Fury Calm': 'rgba(142, 68, 173, 0.8)',
                'None': 'rgba(149, 165, 166, 0.8)'
            };

            const colors = labels.map(label => powerColors[label] || 'rgba(149, 165, 166, 0.8)');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 11 },
                                padding: 8
                            }
                        }
                    }
                }
            });
        }

        function createCardTypeChart() {
            const ctx = document.getElementById('cardTypeChart');
            if (!ctx) return;

            // Count cards by type
            const typeCounts = {};
            deckCards.forEach(card => {
                const type = card.card_type || 'Unknown';
                const qty = card.quantity || 1;
                typeCounts[type] = (typeCounts[type] || 0) + qty;
            });

            const labels = Object.keys(typeCounts);
            const data = Object.values(typeCounts);

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.8)',
                            'rgba(231, 76, 60, 0.8)',
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(241, 196, 15, 0.8)',
                            'rgba(155, 89, 182, 0.8)',
                            'rgba(230, 126, 34, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 11 },
                                padding: 8
                            }
                        }
                    }
                }
            });
        }

        function setupSampleHand() {
            const drawBtn = document.getElementById('drawHandBtn');
            if (drawBtn) {
                drawBtn.addEventListener('click', drawSampleHand);
            }
        }

        function drawSampleHand() {
            const handSize = 7;
            const display = document.getElementById('sampleHandDisplay');

            // Create deck array with duplicates
            const deck = [];
            deckCards.forEach(card => {
                const qty = card.quantity || 1;
                for (let i = 0; i < qty; i++) {
                    deck.push(card);
                }
            });

            // Shuffle
            for (let i = deck.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [deck[i], deck[j]] = [deck[j], deck[i]];
            }

            // Draw hand
            const hand = deck.slice(0, Math.min(handSize, deck.length));

            // Display
            let html = '<div class="sample-hand-grid">';
            hand.forEach(card => {
                html += `
                    <div class="sample-hand-card">
                        ${card.card_art_url ? `<img src="${card.card_art_url}" alt="${card.name}">` : ''}
                        <div class="sample-hand-card-name">${card.name}</div>
                        ${card.energy !== null && card.energy !== undefined ?
                            `<div class="sample-hand-card-cost">${card.energy} Energy</div>` : ''}
                    </div>
                `;
            });
            html += '</div>';

            display.innerHTML = html;
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
