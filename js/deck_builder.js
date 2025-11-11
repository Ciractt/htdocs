// Deck Builder JavaScript - Fully Functional
let currentDeck = [];
let deckChangeTimeout = null;
let isPublished = false;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load existing deck if editing
    if (currentDeckCards && currentDeckCards.length > 0) {
        currentDeckCards.forEach(card => {
            for (let i = 0; i < card.quantity; i++) {
                addCardToDeck(card.id, card.name, card.energy);
            }
        });
    }

    // Set published status
    isPublished = currentDeckPublished;
    updatePublishButton();

    // Wire up event listeners
    setupFilters();
    setupDeckActions();
    setupModal();

    // Initial update
    updateDeckDisplay();
});

// =============================================================================
// DECK MANAGEMENT
// =============================================================================

function addCardToDeck(cardId, cardName, cardCost) {
    const existingCard = currentDeck.find(c => c.id === cardId);

    if (existingCard) {
        // Check copy limit (3 per card)
        if (existingCard.quantity >= 3) {
            showNotification('Maximum 3 copies per card', 'error');
            return;
        }
        existingCard.quantity++;
    } else {
        currentDeck.push({
            id: cardId,
            name: cardName,
            cost: cardCost,
            quantity: 1
        });
    }

    updateDeckDisplay();
    showNotification('Card added to deck', 'success');
}

function removeCardFromDeck(cardId) {
    const cardIndex = currentDeck.findIndex(c => c.id === cardId);

    if (cardIndex === -1) return;

    if (currentDeck[cardIndex].quantity > 1) {
        currentDeck[cardIndex].quantity--;
    } else {
        currentDeck.splice(cardIndex, 1);
    }

    updateDeckDisplay();
}

function removeAllCopies(cardId) {
    currentDeck = currentDeck.filter(c => c.id !== cardId);
    updateDeckDisplay();
}

function clearDeck() {
    if (confirm('Clear all cards from this deck?')) {
        currentDeck = [];
        document.getElementById('deckName').value = 'Untitled Deck';
        document.getElementById('deckDescription').value = '';
        document.getElementById('deckId').value = '';
        updateDeckDisplay();
        showNotification('Deck cleared', 'success');
    }
}

// =============================================================================
// DECK DISPLAY
// =============================================================================

function updateDeckDisplay() {
    const deckList = document.getElementById('deckList');
    const totalCards = currentDeck.reduce((sum, card) => sum + card.quantity, 0);
    const uniqueCards = currentDeck.length;

    // Update stats
    document.getElementById('cardCount').textContent = totalCards;
    document.getElementById('uniqueCards').textContent = uniqueCards;

    // Calculate average cost
    let totalCost = 0;
    currentDeck.forEach(card => {
        totalCost += (card.cost || 0) * card.quantity;
    });
    const avgCost = totalCards > 0 ? (totalCost / totalCards).toFixed(1) : 0;
    document.getElementById('avgCost').textContent = avgCost;

    // Check for warnings
    updateDeckWarnings(totalCards);

    // Render deck list
    if (currentDeck.length === 0) {
        deckList.innerHTML = '<div class="empty-deck"><p>Click cards from the library to add them to your deck</p></div>';
        return;
    }

    // Sort cards by cost, then name
    const sortedDeck = [...currentDeck].sort((a, b) => {
        if (a.cost !== b.cost) return a.cost - b.cost;
        return a.name.localeCompare(b.name);
    });

    // Group by card type
    const champions = [];
    const units = [];
    const spells = [];
    const other = [];

    sortedDeck.forEach(deckCard => {
        const card = cardDatabase[deckCard.id];
        if (!card) return;

        const cardType = card.card_type?.toLowerCase();
        if (card.rarity?.toLowerCase() === 'champion') {
            champions.push({ ...deckCard, card });
        } else if (cardType === 'unit') {
            units.push({ ...deckCard, card });
        } else if (cardType === 'spell') {
            spells.push({ ...deckCard, card });
        } else {
            other.push({ ...deckCard, card });
        }
    });

    let html = '';

    if (champions.length > 0) {
        html += renderDeckSection('Champions', champions);
    }
    if (units.length > 0) {
        html += renderDeckSection('Units', units);
    }
    if (spells.length > 0) {
        html += renderDeckSection('Spells', spells);
    }
    if (other.length > 0) {
        html += renderDeckSection('Other', other);
    }

    deckList.innerHTML = html;
}

function renderDeckSection(title, cards) {
    let html = `<div class="deck-section">`;
    html += `<div class="deck-section-title">${title} (${cards.reduce((sum, c) => sum + c.quantity, 0)})</div>`;

    cards.forEach(deckCard => {
        const card = deckCard.card;
        const owned = userCollection[deckCard.id] || 0;
        const insufficient = deckCard.quantity > owned;

        html += `
            <div class="deck-card ${insufficient ? 'insufficient-copies' : ''}">
                <div class="deck-card-info">
                    <div class="deck-card-cost">${card.energy ?? '-'}</div>
                    <div class="deck-card-name">${card.name}</div>
                    <div class="deck-card-quantity">x${deckCard.quantity}</div>
                </div>
                <div class="deck-card-controls">
                    <button class="btn-icon" onclick="addCardToDeck(${deckCard.id}, '${card.name.replace(/'/g, "\\'")}', ${card.energy ?? 0})" title="Add one">+</button>
                    <button class="btn-icon" onclick="removeCardFromDeck(${deckCard.id})" title="Remove one">-</button>
                    <button class="btn-icon btn-danger" onclick="removeAllCopies(${deckCard.id})" title="Remove all">×</button>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    return html;
}

function updateDeckWarnings(totalCards) {
    const warningsDiv = document.getElementById('deckWarnings');
    const warnings = [];

    // Check deck size
    if (totalCards < 40) {
        warnings.push(`Deck needs at least 40 cards (currently ${totalCards})`);
    } else if (totalCards > 60) {
        warnings.push(`Deck should not exceed 60 cards (currently ${totalCards})`);
    }

    // Check for cards not in collection
    const missingCards = [];
    currentDeck.forEach(deckCard => {
        const owned = userCollection[deckCard.id] || 0;
        if (deckCard.quantity > owned) {
            const card = cardDatabase[deckCard.id];
            const shortage = deckCard.quantity - owned;
            missingCards.push(`${card.name} (need ${shortage} more)`);
        }
    });

    if (missingCards.length > 0) {
        warnings.push(`Missing cards: ${missingCards.join(', ')}`);
    }

    // Display warnings
    if (warnings.length > 0) {
        warningsDiv.innerHTML = `
            <div class="warning-box">
                <h4>⚠️ Deck Warnings</h4>
                <ul>
                    ${warnings.map(w => `<li>${w}</li>`).join('')}
                </ul>
            </div>
        `;
    } else {
        warningsDiv.innerHTML = '';
    }
}

// =============================================================================
// FILTERING
// =============================================================================

function setupFilters() {
    const searchInput = document.getElementById('cardSearch');
    const energyFilter = document.getElementById('energyFilter');
    const typeFilter = document.getElementById('typeFilter');
    const rarityFilter = document.getElementById('rarityFilter');
    const regionFilter = document.getElementById('regionFilter');

    searchInput.addEventListener('input', filterLibrary);
    energyFilter.addEventListener('change', filterLibrary);
    typeFilter.addEventListener('change', filterLibrary);
    rarityFilter.addEventListener('change', filterLibrary);
    regionFilter.addEventListener('change', filterLibrary);
}

function filterLibrary() {
    const searchTerm = document.getElementById('cardSearch').value.toLowerCase();
    const energyValue = document.getElementById('energyFilter').value;
    const typeValue = document.getElementById('typeFilter').value.toLowerCase();
    const rarityValue = document.getElementById('rarityFilter').value.toLowerCase();
    const regionValue = document.getElementById('regionFilter').value.toLowerCase();

    const cards = document.querySelectorAll('.library-card');

    cards.forEach(card => {
        const name = card.dataset.name;
        const energy = card.dataset.energy;
        const type = card.dataset.type;
        const rarity = card.dataset.rarity;
        const region = card.dataset.region;

        let show = true;

        // Search filter
        if (searchTerm && !name.includes(searchTerm)) {
            show = false;
        }

        // Energy filter
        if (energyValue && energy !== energyValue) {
            show = false;
        }

        // Type filter
        if (typeValue && type !== typeValue) {
            show = false;
        }

        // Rarity filter
        if (rarityValue && rarity !== rarityValue) {
            show = false;
        }

        // Region filter
        if (regionValue && region !== regionValue) {
            show = false;
        }

        card.style.display = show ? '' : 'none';
    });
}

// =============================================================================
// DECK ACTIONS (Save, Export, etc.)
// =============================================================================

function setupDeckActions() {
    document.getElementById('saveDeckBtn').addEventListener('click', saveDeck);
    document.getElementById('clearDeckBtn').addEventListener('click', clearDeck);
    document.getElementById('exportDeckBtn').addEventListener('click', exportDeck);
    document.getElementById('publishToggleBtn').addEventListener('click', togglePublish);
}

async function saveDeck() {
    const deckName = document.getElementById('deckName').value.trim();
    const deckDescription = document.getElementById('deckDescription').value.trim();
    const deckId = document.getElementById('deckId').value;

    if (!deckName) {
        showNotification('Please enter a deck name', 'error');
        return;
    }

    if (currentDeck.length === 0) {
        showNotification('Cannot save an empty deck', 'error');
        return;
    }

    const totalCards = currentDeck.reduce((sum, card) => sum + card.quantity, 0);
    if (totalCards < 40) {
        if (!confirm(`Your deck only has ${totalCards} cards. Decks should have at least 40 cards. Save anyway?`)) {
            return;
        }
    }

    const formData = new FormData();
    formData.append('action', 'save');
    formData.append('deck_id', deckId);
    formData.append('deck_name', deckName);
    formData.append('description', deckDescription);
    formData.append('cards', JSON.stringify(currentDeck));

    try {
        const response = await fetch('api/deck.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');

            // Update deck ID if this was a new deck
            if (data.deck_id) {
                document.getElementById('deckId').value = data.deck_id;

                // Update URL without reloading
                const newUrl = `deck_builder_new.php?deck_id=${data.deck_id}`;
                window.history.replaceState({}, '', newUrl);

                // Show publish button now that deck is saved
                updatePublishButton();
            }
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Save error:', error);
        showNotification('Failed to save deck', 'error');
    }
}

function updatePublishButton() {
    const deckId = document.getElementById('deckId').value;
    const publishBtn = document.getElementById('publishToggleBtn');
    const publishBtnText = document.getElementById('publishBtnText');

    if (deckId) {
        publishBtn.style.display = 'inline-block';
        publishBtnText.textContent = isPublished ? 'Unpublish Deck' : 'Publish Deck';
        publishBtn.className = isPublished ? 'btn btn-secondary btn-small' : 'btn btn-primary btn-small';
    } else {
        publishBtn.style.display = 'none';
    }
}

async function togglePublish() {
    const deckId = document.getElementById('deckId').value;

    if (!deckId) {
        showNotification('Please save the deck first', 'error');
        return;
    }

    const action = isPublished ? 'unpublish' : 'publish';
    const confirmMsg = isPublished
        ? 'Unpublish this deck? Other players will no longer be able to view it.'
        : 'Publish this deck? Other players will be able to view and copy it.';

    if (!confirm(confirmMsg)) {
        return;
    }

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
            isPublished = !isPublished;
            updatePublishButton();
            showNotification(data.message, 'success');

            // Reload page to show published status
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Publish error:', error);
        showNotification('Failed to update deck status', 'error');
    }
}

async function exportDeck() {
    const deckId = document.getElementById('deckId').value;

    if (!deckId) {
        showNotification('Please save the deck first', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'export');
    formData.append('deck_id', deckId);

    try {
        const response = await fetch('api/deck.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Copy to clipboard
            navigator.clipboard.writeText(data.deck_code).then(() => {
                showNotification('Deck code copied to clipboard!', 'success');

                // Also show it in an alert for manual copy if needed
                prompt('Deck code (already copied to clipboard):', data.deck_code);
            }).catch(() => {
                // Fallback: show in prompt
                prompt('Copy this deck code:', data.deck_code);
            });
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Export error:', error);
        showNotification('Failed to export deck', 'error');
    }
}

async function deleteDeck(deckId) {
    if (!confirm('Delete this deck permanently?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('deck_id', deckId);

    try {
        const response = await fetch('api/deck.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');

            // Reload after a short delay
            setTimeout(() => {
                window.location.href = 'deck_builder_new.php';
            }, 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Delete error:', error);
        showNotification('Failed to delete deck', 'error');
    }
}

// =============================================================================
// MODAL
// =============================================================================

function setupModal() {
    const modal = document.getElementById('loadDeckModal');
    const loadBtn = document.getElementById('loadDeckBtn');
    const closeBtn = modal.querySelector('.close');

    loadBtn.addEventListener('click', () => {
        modal.classList.add('active');
    });

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
}

// =============================================================================
// NOTIFICATIONS
// =============================================================================

function showNotification(message, type) {
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }

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

// =============================================================================
// EXPOSE FUNCTIONS GLOBALLY
// =============================================================================

window.addCardToDeck = addCardToDeck;
window.removeCardFromDeck = removeCardFromDeck;
window.removeAllCopies = removeAllCopies;
window.deleteDeck = deleteDeck;
