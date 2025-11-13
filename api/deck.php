<?php
// API for Rule-Enforced Deck Builder (v2)
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();
$user = getCurrentUser();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'save_deck_v2':
            saveDeckV2($pdo, $user, $input);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Save deck with rule enforcement
 */
function saveDeckV2($pdo, $user, $input) {
    // Validate required fields
    if (empty($input['deck_name'])) {
        throw new Exception('Deck name is required');
    }

    if (empty($input['champion_legend_id'])) {
        throw new Exception('Champion Legend is required');
    }

    // Validate deck rules
    $mainDeck = $input['main_deck'] ?? [];
    $runeDeck = $input['rune_deck'] ?? [];
    $battlefields = $input['battlefields'] ?? [];

    // Rule: Main deck minimum 40 cards
    if (count($mainDeck) < 40) {
        throw new Exception('Main deck must have at least 40 cards');
    }

    // Rule: Rune deck exactly 12 cards
    if (count($runeDeck) !== 12) {
        throw new Exception('Rune deck must be exactly 12 cards');
    }

    // Rule: Must have Chosen Champion
    if (empty($input['chosen_champion_id'])) {
        throw new Exception('Chosen Champion is required');
    }

    // Validate Champion Legend exists and is a Legend
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = ? AND card_type = 'Legend'");
    $stmt->execute([$input['champion_legend_id']]);
    $championLegend = $stmt->fetch();

    if (!$championLegend) {
        throw new Exception('Invalid Champion Legend');
    }

    // Validate Chosen Champion
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = ? AND (card_type = 'Champion' OR rarity = 'Champion')");
    $stmt->execute([$input['chosen_champion_id']]);
    $chosenChampion = $stmt->fetch();

    if (!$chosenChampion) {
        throw new Exception('Invalid Chosen Champion');
    }

    // Rule: Chosen Champion must match Legend's tag
    if ($chosenChampion['champion'] !== $championLegend['champion']) {
        throw new Exception('Chosen Champion must match Champion Legend\'s tag');
    }

    // Validate card quantities (max 3 per card name)
    $cardCounts = array_count_values($mainDeck);
    foreach ($cardCounts as $cardId => $count) {
        if ($count > 3) {
            $stmt = $pdo->prepare("SELECT name FROM cards WHERE id = ?");
            $stmt->execute([$cardId]);
            $card = $stmt->fetch();
            throw new Exception("Maximum 3 copies allowed per card ({$card['name']})");
        }
    }

    // Validate signature cards (max 3 total)
    $signatureCount = 0;
    foreach ($mainDeck as $cardId) {
        $stmt = $pdo->prepare("SELECT card_type, champion FROM cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch();

        if ($card['card_type'] === 'Signature') {
            $signatureCount++;

            // Must match legend's champion tag
            if ($card['champion'] !== $championLegend['champion']) {
                throw new Exception('Signature cards must match Champion Legend\'s tag');
            }
        }
    }

    if ($signatureCount > 3) {
        throw new Exception('Maximum 3 Signature cards allowed');
    }

    // Validate domain identity for all cards
    $legendDomain = $championLegend['region'];
    $allCards = array_merge($mainDeck, $runeDeck, $battlefields);

    foreach ($allCards as $cardId) {
        $stmt = $pdo->prepare("SELECT region, name FROM cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch();

        if ($card && $card['region'] && $card['region'] !== 'None') {
            if ($card['region'] !== $legendDomain) {
                throw new Exception("Card '{$card['name']}' does not match Domain Identity");
            }
        }
    }

    // Validate battlefield uniqueness (no duplicate names)
    $battlefieldNames = [];
    foreach ($battlefields as $cardId) {
        $stmt = $pdo->prepare("SELECT name FROM cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch();

        if (in_array($card['name'], $battlefieldNames)) {
            throw new Exception("Cannot include more than one Battlefield of the same name ({$card['name']})");
        }
        $battlefieldNames[] = $card['name'];
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        $deckId = $input['deck_id'] ?? null;

        if ($deckId) {
            // Update existing deck
            $stmt = $pdo->prepare("
                UPDATE decks
                SET deck_name = ?,
                    description = ?,
                    champion_legend_id = ?,
                    chosen_champion_id = ?,
                    updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $input['deck_name'],
                $input['description'] ?? '',
                $input['champion_legend_id'],
                $input['chosen_champion_id'],
                $deckId,
                $user['id']
            ]);

            // Delete existing deck cards
            $stmt = $pdo->prepare("DELETE FROM deck_cards WHERE deck_id = ?");
            $stmt->execute([$deckId]);
        } else {
            // Create new deck
            $stmt = $pdo->prepare("
                INSERT INTO decks (user_id, deck_name, description, champion_legend_id, chosen_champion_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $user['id'],
                $input['deck_name'],
                $input['description'] ?? '',
                $input['champion_legend_id'],
                $input['chosen_champion_id']
            ]);
            $deckId = $pdo->lastInsertId();
        }

        // Insert main deck cards
        foreach ($mainDeck as $cardId) {
            insertDeckCard($pdo, $deckId, $cardId, 'main_deck');
        }

        // Insert rune deck cards
        foreach ($runeDeck as $cardId) {
            insertDeckCard($pdo, $deckId, $cardId, 'rune_deck');
        }

        // Insert battlefield cards
        foreach ($battlefields as $cardId) {
            insertDeckCard($pdo, $deckId, $cardId, 'battlefields');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Deck saved successfully',
            'deck_id' => $deckId
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Helper: Insert deck card with proper handling
 */
function insertDeckCard($pdo, $deckId, $cardId, $deckSlot) {
    // Check if card already exists in this slot
    $stmt = $pdo->prepare("
        SELECT id, quantity
        FROM deck_cards
        WHERE deck_id = ? AND card_id = ? AND deck_slot = ?
    ");
    $stmt->execute([$deckId, $cardId, $deckSlot]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Increment quantity
        $stmt = $pdo->prepare("
            UPDATE deck_cards
            SET quantity = quantity + 1
            WHERE id = ?
        ");
        $stmt->execute([$existing['id']]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("
            INSERT INTO deck_cards (deck_id, card_id, quantity, deck_slot)
            VALUES (?, ?, 1, ?)
        ");
        $stmt->execute([$deckId, $cardId, $deckSlot]);
    }
}
