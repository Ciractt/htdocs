<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();
$user = getCurrentUser();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $deck_id = intval($_POST['deck_id'] ?? 0);
        $deck_name = trim($_POST['deck_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cards = json_decode($_POST['cards'] ?? '[]', true);

        if (empty($deck_name)) {
            $response['message'] = 'Deck name is required';
        } elseif (!is_array($cards)) {
            $response['message'] = 'Invalid card data';
        } else {
            $pdo->beginTransaction();

            try {
                if ($deck_id > 0) {
                    // Update existing deck
                    $stmt = $pdo->prepare("UPDATE decks SET deck_name = ?, description = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                    $stmt->execute([$deck_name, $description, $deck_id, $user['id']]);

                    // Delete old cards
                    $stmt = $pdo->prepare("DELETE FROM deck_cards WHERE deck_id = ?");
                    $stmt->execute([$deck_id]);
                } else {
                    // Create new deck
                    $stmt = $pdo->prepare("INSERT INTO decks (user_id, deck_name, description) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $deck_name, $description]);
                    $deck_id = $pdo->lastInsertId();
                }

                // Add cards to deck
                $stmt = $pdo->prepare("INSERT INTO deck_cards (deck_id, card_id, quantity) VALUES (?, ?, ?)");
                foreach ($cards as $card) {
                    if (isset($card['id']) && isset($card['quantity'])) {
                        $stmt->execute([$deck_id, $card['id'], $card['quantity']]);
                    }
                }

                $pdo->commit();

                $response['success'] = true;
                $response['message'] = 'Deck saved successfully';
                $response['deck_id'] = $deck_id;
            } catch (Exception $e) {
                $pdo->rollBack();
                $response['message'] = 'Failed to save deck';
            }
        }
    } elseif ($action === 'delete') {
        $deck_id = intval($_POST['deck_id'] ?? 0);

        if ($deck_id <= 0) {
            $response['message'] = 'Invalid deck ID';
        } else {
            $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ? AND user_id = ?");
            $stmt->execute([$deck_id, $user['id']]);

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Deck deleted successfully';
            } else {
                $response['message'] = 'Deck not found';
            }
        }
    } elseif ($action === 'export') {
        $deck_id = intval($_POST['deck_id'] ?? 0);

        if ($deck_id <= 0) {
            $response['message'] = 'Invalid deck ID';
        } else {
            $stmt = $pdo->prepare("
                SELECT c.card_code, dc.quantity
                FROM deck_cards dc
                JOIN cards c ON dc.card_id = c.id
                WHERE dc.deck_id = ?
            ");
            $stmt->execute([$deck_id]);
            $cards = $stmt->fetchAll();

            // Generate simple deck code
            $deck_code = '';
            foreach ($cards as $card) {
                $deck_code .= $card['quantity'] . 'x ' . $card['card_code'] . "\n";
            }

            $response['success'] = true;
            $response['deck_code'] = trim($deck_code);
        }
    } elseif ($action === 'publish') {
        $deck_id = intval($_POST['deck_id'] ?? 0);

        if ($deck_id <= 0) {
            $response['message'] = 'Invalid deck ID';
        } else {
            // Verify deck belongs to user
            $stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
            $stmt->execute([$deck_id, $user['id']]);

            if (!$stmt->fetch()) {
                $response['message'] = 'Deck not found';
            } else {
                $stmt = $pdo->prepare("UPDATE decks SET is_published = TRUE, published_at = NOW() WHERE id = ?");
                $stmt->execute([$deck_id]);

                $response['success'] = true;
                $response['message'] = 'Deck published successfully!';
            }
        }
    } elseif ($action === 'unpublish') {
        $deck_id = intval($_POST['deck_id'] ?? 0);

        if ($deck_id <= 0) {
            $response['message'] = 'Invalid deck ID';
        } else {
            // Verify deck belongs to user
            $stmt = $pdo->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ?");
            $stmt->execute([$deck_id, $user['id']]);

            if (!$stmt->fetch()) {
                $response['message'] = 'Deck not found';
            } else {
                $stmt = $pdo->prepare("UPDATE decks SET is_published = FALSE WHERE id = ?");
                $stmt->execute([$deck_id]);

                $response['success'] = true;
                $response['message'] = 'Deck unpublished';
            }
        }
    } elseif ($action === 'copy_deck') {
        $original_deck_id = intval($_POST['deck_id'] ?? 0);

        if ($original_deck_id <= 0) {
            $response['message'] = 'Invalid deck ID';
        } else {
            // Get original deck
            $stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ? AND is_published = TRUE");
            $stmt->execute([$original_deck_id]);
            $original_deck = $stmt->fetch();

            if (!$original_deck) {
                $response['message'] = 'Deck not found or not published';
            } else {
                // Get deck cards
                $stmt = $pdo->prepare("SELECT card_id, quantity FROM deck_cards WHERE deck_id = ?");
                $stmt->execute([$original_deck_id]);
                $deck_cards = $stmt->fetchAll();

                $pdo->beginTransaction();

                try {
                    // Create new deck for user
                    $new_name = $original_deck['deck_name'] . ' (Copy)';
                    $stmt = $pdo->prepare("INSERT INTO decks (user_id, deck_name, description) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $new_name, $original_deck['description']]);
                    $new_deck_id = $pdo->lastInsertId();

                    // Copy cards
                    $stmt = $pdo->prepare("INSERT INTO deck_cards (deck_id, card_id, quantity) VALUES (?, ?, ?)");
                    foreach ($deck_cards as $card) {
                        $stmt->execute([$new_deck_id, $card['card_id'], $card['quantity']]);
                    }

                    // Track copy
                    $stmt = $pdo->prepare("INSERT INTO deck_copies (user_id, original_deck_id, copied_deck_id) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $original_deck_id, $new_deck_id]);

                    // Increment copy count
                    $stmt = $pdo->prepare("UPDATE decks SET copy_count = copy_count + 1 WHERE id = ?");
                    $stmt->execute([$original_deck_id]);

                    $pdo->commit();

                    $response['success'] = true;
                    $response['message'] = 'Deck copied successfully!';
                    $response['deck_id'] = $new_deck_id;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $response['message'] = 'Failed to copy deck';
                }
            }
        }
    } elseif ($action === 'like') {
        $deck_id = intval($_POST['deck_id'] ?? 0);

        if ($deck_id <= 0) {
            $response['message'] = 'Invalid deck ID';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO deck_likes (user_id, deck_id) VALUES (?, ?)");
                $stmt->execute([$user['id'], $deck_id]);

                // Update like count
                $stmt = $pdo->prepare("UPDATE decks SET like_count = like_count + 1 WHERE id = ?");
                $stmt->execute([$deck_id]);

                $response['success'] = true;
                $response['message'] = 'Deck liked!';
            } catch (Exception $e) {
                $response['message'] = 'Already liked';
            }
        }
    } elseif ($action === 'unlike') {
        $deck_id = intval($_POST['deck_id'] ?? 0);

        if ($deck_id <= 0) {
            $response['message'] = 'Invalid deck ID';
        } else {
            $stmt = $pdo->prepare("DELETE FROM deck_likes WHERE user_id = ? AND deck_id = ?");
            $stmt->execute([$user['id'], $deck_id]);

            if ($stmt->rowCount() > 0) {
                // Update like count
                $stmt = $pdo->prepare("UPDATE decks SET like_count = like_count - 1 WHERE id = ?");
                $stmt->execute([$deck_id]);

                $response['success'] = true;
                $response['message'] = 'Deck unliked';
            }
        }
    }
}

echo json_encode($response);
