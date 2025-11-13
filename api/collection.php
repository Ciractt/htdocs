<?php
// Collection API
// Handles quantity updates and wishlist operations

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
        case 'update_quantity':
            updateQuantity($pdo, $user, $input);
            break;

        case 'add_to_wishlist':
            addToWishlist($pdo, $user, $input);
            break;

        case 'remove_from_wishlist':
            removeFromWishlist($pdo, $user, $input);
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
 * Update card quantity in collection
 */
function updateQuantity($pdo, $user, $input) {
    if (!isset($input['card_id']) || !isset($input['quantity'])) {
        throw new Exception('Missing required fields');
    }

    $cardId = intval($input['card_id']);
    $quantity = intval($input['quantity']);

    // Validate card exists
    $stmt = $pdo->prepare("SELECT id FROM cards WHERE id = ?");
    $stmt->execute([$cardId]);
    if (!$stmt->fetch()) {
        throw new Exception('Card not found');
    }

    if ($quantity < 0) {
        throw new Exception('Quantity cannot be negative');
    }

    // Check if user already has this card
    $stmt = $pdo->prepare("SELECT id FROM user_collections WHERE user_id = ? AND card_id = ?");
    $stmt->execute([$user['id'], $cardId]);
    $existing = $stmt->fetch();

    if ($quantity === 0) {
        // Remove from collection
        if ($existing) {
            $stmt = $pdo->prepare("DELETE FROM user_collections WHERE user_id = ? AND card_id = ?");
            $stmt->execute([$user['id'], $cardId]);
        }
    } else {
        // Update or insert
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE user_collections SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND card_id = ?");
            $stmt->execute([$quantity, $user['id'], $cardId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_collections (user_id, card_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$user['id'], $cardId, $quantity]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Collection updated',
        'quantity' => $quantity
    ]);
}

/**
 * Add card to wishlist
 */
function addToWishlist($pdo, $user, $input) {
    if (!isset($input['card_id'])) {
        throw new Exception('Card ID required');
    }

    $cardId = intval($input['card_id']);

    // Validate card exists
    $stmt = $pdo->prepare("SELECT id FROM cards WHERE id = ?");
    $stmt->execute([$cardId]);
    if (!$stmt->fetch()) {
        throw new Exception('Card not found');
    }

    // Check if already in wishlist
    $stmt = $pdo->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND card_id = ?");
    $stmt->execute([$user['id'], $cardId]);

    if (!$stmt->fetch()) {
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO user_wishlist (user_id, card_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user['id'], $cardId]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Added to wishlist'
    ]);
}

/**
 * Remove card from wishlist
 */
function removeFromWishlist($pdo, $user, $input) {
    if (!isset($input['card_id'])) {
        throw new Exception('Card ID required');
    }

    $cardId = intval($input['card_id']);

    $stmt = $pdo->prepare("DELETE FROM user_wishlist WHERE user_id = ? AND card_id = ?");
    $stmt->execute([$user['id'], $cardId]);

    echo json_encode([
        'success' => true,
        'message' => 'Removed from wishlist'
    ]);
}
