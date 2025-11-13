// Riftbound Rule-Enforced Deck Builder
// Implements all official Riftbound deck construction rules

(function() {
    'use strict';

    // Deck state
    let selectedChampionLegend = null;
    let chosenChampion = null;
    let mainDeck = []; // Array of {cardId, quantity}
    let runeDeck = []; // Array of {cardId, quantity}
    let battlefields = []; // Array of {cardId, quantity}

    // Rule constants
    const RULES = {
        MAIN_DECK_MIN: 40,
        MAIN_DECK_MAX: null, // No maximum
        RUNE_DECK_SIZE: 12,
        MAX_COPIES_PER_CARD: 3,
        MAX_SIGNATURE_CARDS: 3
    };

    // Initialize
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        setupTabs();
        loadExistingDeck();
        setupEventListeners();
    }

    /**
     * Setup deck building tabs
     */
    function setupTabs() {
        const tabs = document.querySelectorAll('.deck-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.dataset.tab;
                switchTab(tabName);
            });
        });
    }

    function switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.deck-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`${tabName}-content`).classList.add('active');

        // Render appropriate card grid
        renderCardGrid(tabName);
    }

    /**
     * Load existing deck if editing
     */
    function loadExistingDeck() {
        if (!currentDeckData) return;

        // Load champion legend
        if (currentDeckData.champion_legend_id) {
            const legend = window.cardDatabase[currentDeckData.champion_legend_id];
            if (legend) {
                selectChampionLegend(legend);
            }
        }

        // Load chosen champion
        if (currentDeckData.chosen_champion_id) {
            const champion = window.cardDatabase[currentDeckData.chosen_champion_id];
            if (champion) {
                setChosenChampion(champion);
            }
        }

        // Load main deck
        if (currentDeckData.main_deck) {
            currentDeckData.main_deck.forEach(card => {
                for (let i = 0; i < card.quantity; i++) {
                    addToMainDeck(card.id, true);
                }
            });
        }

        // Load rune deck
        if (currentDeckData.rune_deck) {
            currentDeckData.rune_deck.forEach(card => {
                for (let i = 0; i < card.quantity; i++) {
                    addToRuneDeck(card.id, true);
                }
            });
        }

        // Load battlefields
        if (currentDeckData.battlefields) {
            currentDeckData.battlefields.forEach(card => {
                for (let i = 0; i < card.quantity; i++) {
                    addToBattlefields(card.id, true);
                }
            });
        }

        updateAllCounts();
        validateDeck();
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Save deck
        document.getElementById('saveDeckBtn')?.addEventListener('click', saveDeck);

        // Clear deck
        document.getElementById('clearDeckBtn')?.addEventListener('click', clearDeck);

        // Export deck
        document.getElementById('exportDeckBtn')?.addEventListener('click', exportDeck);

        // Search inputs
        document.getElementById('mainSearchInput')?.addEventListener('input', (e) => {
            filterCards('main-deck', e.target.value);
        });

        document.getElementById('runeSearchInput')?.addEventListener('input', (e) => {
            filterCards('rune-deck', e.target.value);
        });

        document.getElementById('battlefieldSearchInput')?.addEventListener('input', (e) => {
            filterCards('battlefields', e.target.value);
        });

        // Type filter
        document.getElementById('mainTypeFilter')?.addEventListener('change', () => {
            renderCardGrid('main-deck');
        });

        // Rarity filter
        document.getElementById('mainRarityFilter')?.addEventListener('change', () => {
            renderCardGrid('main-deck');
        });
    }

    /**
     * Select Champion Legend (Step 1)
     */
    window.selectChampionLegend = function(legend) {
        selectedChampionLegend = legend;

        // Update UI
        document.querySelectorAll('.legend-card').forEach(card => {
            card.classList.toggle('selected',
                parseInt(card.dataset.legendId) === legend.id);
        });

        // Show main builder
        document.getElementById('main-builder').style.display = 'block';

        // Scroll to main builder
        document.getElementById('main-builder').scrollIntoView({ behavior: 'smooth' });

        // Display selected legend
        displaySelectedLegend();

        // Render card grids with domain filtering
        renderCardGrid('main-deck');
        renderCardGrid('rune-deck');
        renderCardGrid('battlefields');

        validateDeck();
    };

    /**
     * Display selected legend in deck panel
     */
    function displaySelectedLegend() {
        const container = document.getElementById('selectedLegendDisplay');
        if (!selectedChampionLegend) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = `
            <div class="legend-display-header">Champion Legend</div>
            <div class="legend-display-content">
                ${selectedChampionLegend.card_art_url ?
                    `<img src="${selectedChampionLegend.card_art_url}" alt="${selectedChampionLegend.name}">`
                    : ''}
                <div class="legend-display-info">
                    <div class="legend-display-name">${selectedChampionLegend.name}</div>
                    <div class="legend-display-domain">
                        Domain: ${selectedChampionLegend.region || 'None'}
                        ${selectedChampionLegend.champion ? `<br>Tag: ${selectedChampionLegend.champion}` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Set Chosen Champion
     */
    function setChosenChampion(champion) {
        chosenChampion = champion;

        const slot = document.getElementById('chosenChampionSlot');
        slot.classList.add('filled');
        slot.innerHTML = `
            <div class="champion-display">
                ${champion.card_art_url ?
                    `<img src="${champion.card_art_url}" alt="${champion.name}">`
                    : ''}
                <div class="champion-display-info">
                    <div class="champion-display-name">${champion.name}</div>
                    <div class="champion-display-tag">Tag: ${champion.champion || 'None'}</div>
                </div>
                <button class="remove-champion-btn" onclick="removeChosenChampion()">Remove</button>
            </div>
        `;

        // Hide the filter hint
        const hint = document.getElementById('championFilterHint');
        if (hint) {
            hint.style.display = 'none';
        }

        // Re-render the grid to remove the badges from cards
        renderCardGrid('main-deck');

        validateDeck();
    }

    /**
     * Remove Chosen Champion
     */
    window.removeChosenChampion = function() {
        chosenChampion = null;

        const slot = document.getElementById('chosenChampionSlot');
        slot.classList.remove('filled');
        slot.innerHTML = '<p class="empty-slot">No Chosen Champion selected yet.<br><small>Click a matching champion card below to set.</small></p>';

        // Show the filter hint again
        const hint = document.getElementById('championFilterHint');
        if (hint) {
            hint.style.display = 'block';
        }

        // Re-render to show badges again
        renderCardGrid('main-deck');

        validateDeck();
    };

    /**
     * Render card grid based on active tab
     */
    function renderCardGrid(tabName) {
        let cards, gridId;

        switch(tabName) {
            case 'main-deck':
                cards = getValidMainDeckCards();
                gridId = 'mainDeckCardGrid';
                break;
            case 'rune-deck':
                cards = getValidRuneCards();
                gridId = 'runeDeckCardGrid';
                break;
            case 'battlefields':
                cards = getValidBattlefieldCards();
                gridId = 'battlefieldCardGrid';
                break;
            default:
                return;
        }

        const grid = document.getElementById(gridId);
        if (!grid) return;

        grid.innerHTML = '';

        if (cards.length === 0) {
            grid.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">No cards available</p>';
            return;
        }

        cards.forEach(card => {
            const cardDiv = createCardElement(card, tabName);
            grid.appendChild(cardDiv);
        });
    }

    /**
     * Get valid main deck cards (filtered by domain identity and rules)
     */
    function getValidMainDeckCards() {
        if (!selectedChampionLegend) return [];

        let cards = window.mainDeckCards.filter(card => {
            // Exclude legends, runes, battlefields
            if (['Legend', 'Rune', 'Battlefield'].includes(card.card_type)) {
                return false;
            }

            // Check domain identity
            if (!isValidDomainIdentity(card)) {
                return false;
            }

            return true;
        });

        // Apply type filter
        const typeFilter = document.getElementById('mainTypeFilter')?.value;
        if (typeFilter) {
            cards = cards.filter(c => c.card_type === typeFilter ||
                (typeFilter === 'Champion' && c.rarity === 'Champion'));
        }

        // Apply rarity filter
        const rarityFilter = document.getElementById('mainRarityFilter')?.value;
        if (rarityFilter) {
            cards = cards.filter(c => c.rarity === rarityFilter);
        }

        return cards;
    }

    /**
     * Get valid rune cards
     */
    function getValidRuneCards() {
        if (!selectedChampionLegend) return [];

        return window.runeCards.filter(card => {
            return isValidDomainIdentity(card);
        });
    }

    /**
     * Get valid battlefield cards
     */
    function getValidBattlefieldCards() {
        if (!selectedChampionLegend) return [];

        return window.battlefieldCards.filter(card => {
            return isValidDomainIdentity(card);
        });
    }

    /**
     * Check if card matches domain identity (Rule 103.1.b)
     */
    function isValidDomainIdentity(card) {
        if (!selectedChampionLegend) return false;

        const legendDomain = selectedChampionLegend.region;
        const cardDomain = card.region;

        // If card has no domain, it's valid
        if (!cardDomain || cardDomain === 'None') {
            return true;
        }

        // If legend has no domain, only neutral cards allowed
        if (!legendDomain) {
            return !cardDomain || cardDomain === 'None';
        }

        // Simple domain matching (you may need to expand for multi-domain cards)
        return cardDomain === legendDomain;
    }

    /**
     * Create card element
     */
    function createCardElement(card, deckType) {
        const div = document.createElement('div');
        div.className = 'library-card';
        div.dataset.cardId = card.id;

        // Check if max copies reached
        const currentCount = getCardCount(card.id, deckType);
        const isMaxCopies = currentCount >= RULES.MAX_COPIES_PER_CARD;

        if (isMaxCopies) {
            div.classList.add('max-copies');
        }

        // Check if it's a valid chosen champion
        const isChampion = card.card_type === 'Champion' || card.rarity === 'Champion';
        const matchesLegendTag = selectedChampionLegend && card.champion === selectedChampionLegend.champion;
        const canBeChosenChampion = isChampion && matchesLegendTag && !chosenChampion && card.card_type !== 'Signature';

        // Add visual indicator for valid chosen champions
        if (canBeChosenChampion) {
            div.classList.add('valid-chosen-champion');
        }

        div.innerHTML = `
            ${card.card_art_url ?
                `<img src="${card.card_art_url}" alt="${card.name}">`
                : `<div style="padding: 2rem; background: #f0f0f0;">${card.name}</div>`}
            ${currentCount > 0 ? `<div class="card-count-badge">×${currentCount}</div>` : ''}
            ${userCollection[card.id] ? `<div class="collection-badge-lib">${userCollection[card.id]}</div>` : ''}
            ${canBeChosenChampion ? `<div class="chosen-champion-badge">Set as Champion</div>` : ''}
        `;

        // Add click handler
        div.addEventListener('click', () => {
            if (isMaxCopies) return;

            if (deckType === 'main-deck') {
                // Check if clicking on a valid chosen champion
                if (canBeChosenChampion) {
                    if (confirm(`Set ${card.name} as your Chosen Champion?\n\nYou can still add up to 3 copies to your Main Deck.`)) {
                        setChosenChampion(card);
                        addToMainDeck(card.id);
                        return;
                    }
                }
                addToMainDeck(card.id);
            } else if (deckType === 'rune-deck') {
                addToRuneDeck(card.id);
            } else if (deckType === 'battlefields') {
                addToBattlefields(card.id);
            }
        });

        return div;
    }

    /**
     * Get current count of a card in a deck
     */
    function getCardCount(cardId, deckType) {
        let deck;
        if (deckType === 'main-deck') deck = mainDeck;
        else if (deckType === 'rune-deck') deck = runeDeck;
        else if (deckType === 'battlefields') deck = battlefields;
        else return 0;

        return deck.filter(c => c === cardId).length;
    }

    /**
     * Add card to main deck
     */
    function addToMainDeck(cardId, silent = false) {
        const card = window.cardDatabase[cardId];
        if (!card) return;

        // Check 3-copy limit (Rule 103.2.b)
        const currentCount = getCardCount(cardId, 'main-deck');
        if (currentCount >= RULES.MAX_COPIES_PER_CARD) {
            if (!silent) alert(`Maximum ${RULES.MAX_COPIES_PER_CARD} copies allowed per card`);
            return;
        }

        // Check signature card limit (Rule 103.2.d)
        if (card.card_type === 'Signature') {
            const signatureCount = mainDeck.filter(id => {
                const c = window.cardDatabase[id];
                return c && c.card_type === 'Signature';
            }).length;

            if (signatureCount >= RULES.MAX_SIGNATURE_CARDS) {
                if (!silent) alert(`Maximum ${RULES.MAX_SIGNATURE_CARDS} Signature cards allowed`);
                return;
            }

            // Must match legend's champion tag
            if (selectedChampionLegend && card.champion !== selectedChampionLegend.champion) {
                if (!silent) alert('Signature cards must match your Champion Legend\'s tag');
                return;
            }
        }

        mainDeck.push(cardId);
        updateDeckDisplay();
        updateAllCounts();
        validateDeck();
        renderCardGrid('main-deck');
    }

    /**
     * Add card to rune deck
     */
    function addToRuneDeck(cardId, silent = false) {
        // Check rune deck size (Rule 103.3)
        if (runeDeck.length >= RULES.RUNE_DECK_SIZE) {
            if (!silent) alert(`Rune deck must be exactly ${RULES.RUNE_DECK_SIZE} cards`);
            return;
        }

        runeDeck.push(cardId);
        updateDeckDisplay();
        updateAllCounts();
        validateDeck();
        renderCardGrid('rune-deck');
    }

    /**
     * Add card to battlefields
     */
    function addToBattlefields(cardId, silent = false) {
        // Check for duplicate names (Rule 103.4.c)
        const card = window.cardDatabase[cardId];
        if (!card) return;

        const hasDuplicate = battlefields.some(id => {
            const existingCard = window.cardDatabase[id];
            return existingCard && existingCard.name === card.name;
        });

        if (hasDuplicate) {
            if (!silent) alert('Cannot include more than one Battlefield of the same name');
            return;
        }

        battlefields.push(cardId);
        updateDeckDisplay();
        updateAllCounts();
        validateDeck();
        renderCardGrid('battlefields');
    }

    /**
     * Remove card from deck
     */
    window.removeFromDeck = function(cardId, deckType) {
        let deck;
        if (deckType === 'main') deck = mainDeck;
        else if (deckType === 'rune') deck = runeDeck;
        else if (deckType === 'battlefield') deck = battlefields;
        else return;

        const index = deck.indexOf(cardId);
        if (index > -1) {
            deck.splice(index, 1);
        }

        updateDeckDisplay();
        updateAllCounts();
        validateDeck();
        renderCardGrid(deckType === 'main' ? 'main-deck' : deckType === 'rune' ? 'rune-deck' : 'battlefields');
    };

    /**
     * Update deck display
     */
    function updateDeckDisplay() {
        const container = document.getElementById('deckList');
        if (!container) return;

        let html = '';

        // Main Deck
        if (mainDeck.length > 0) {
            html += '<div class="deck-section">';
            html += '<div class="deck-section-title">Main Deck <span>' + mainDeck.length + ' cards</span></div>';

            const cardCounts = {};
            mainDeck.forEach(id => {
                cardCounts[id] = (cardCounts[id] || 0) + 1;
            });

            Object.entries(cardCounts).forEach(([cardId, count]) => {
                const card = window.cardDatabase[cardId];
                if (!card) return;

                html += `
                    <div class="deck-card">
                        <div class="deck-card-info">
                            <span class="deck-card-cost">${card.energy ?? '-'}</span>
                            <span class="deck-card-name">${card.name}</span>
                        </div>
                        <div class="deck-card-controls">
                            <span class="deck-card-quantity">×${count}</span>
                            <button class="card-control-btn remove" onclick="removeFromDeck(${cardId}, 'main')">−</button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        // Rune Deck
        if (runeDeck.length > 0) {
            html += '<div class="deck-section">';
            html += '<div class="deck-section-title">Rune Deck <span>' + runeDeck.length + '/12</span></div>';

            const cardCounts = {};
            runeDeck.forEach(id => {
                cardCounts[id] = (cardCounts[id] || 0) + 1;
            });

            Object.entries(cardCounts).forEach(([cardId, count]) => {
                const card = window.cardDatabase[cardId];
                if (!card) return;

                html += `
                    <div class="deck-card">
                        <div class="deck-card-info">
                            <span class="deck-card-name">${card.name}</span>
                        </div>
                        <div class="deck-card-controls">
                            <span class="deck-card-quantity">×${count}</span>
                            <button class="card-control-btn remove" onclick="removeFromDeck(${cardId}, 'rune')">−</button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        // Battlefields
        if (battlefields.length > 0) {
            html += '<div class="deck-section">';
            html += '<div class="deck-section-title">Battlefields <span>' + battlefields.length + '</span></div>';

            battlefields.forEach(cardId => {
                const card = window.cardDatabase[cardId];
                if (!card) return;

                html += `
                    <div class="deck-card">
                        <div class="deck-card-info">
                            <span class="deck-card-name">${card.name}</span>
                        </div>
                        <div class="deck-card-controls">
                            <button class="card-control-btn remove" onclick="removeFromDeck(${cardId}, 'battlefield')">−</button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        if (html === '') {
            html = '<p style="text-align: center; color: #999; padding: 2rem;">Your deck is empty</p>';
        }

        container.innerHTML = html;
    }

    /**
     * Update all counters
     */
    function updateAllCounts() {
        document.getElementById('mainDeckCount').textContent =
            `${mainDeck.length}/${RULES.MAIN_DECK_MIN}+`;

        document.getElementById('runeDeckCount').textContent =
            `${runeDeck.length}/${RULES.RUNE_DECK_SIZE}`;

        document.getElementById('battlefieldCount').textContent = battlefields.length;
    }

    /**
     * Validate deck against all rules
     */
    function validateDeck() {
        const warnings = [];

        // Rule 103.1: Must have Champion Legend
        if (!selectedChampionLegend) {
            warnings.push({ type: 'error', message: 'Must select a Champion Legend' });
        }

        // Rule 103.2.a: Must have Chosen Champion
        if (!chosenChampion) {
            warnings.push({ type: 'warning', message: 'Must select a Chosen Champion' });
        } else if (selectedChampionLegend && chosenChampion.champion !== selectedChampionLegend.champion) {
            warnings.push({ type: 'error', message: 'Chosen Champion must match Legend\'s champion tag' });
        }

        // Rule 103.2: Main deck minimum
        if (mainDeck.length < RULES.MAIN_DECK_MIN) {
            warnings.push({
                type: 'error',
                message: `Main deck must have at least ${RULES.MAIN_DECK_MIN} cards (currently ${mainDeck.length})`
            });
        } else {
            warnings.push({
                type: 'success',
                message: `Main deck: ${mainDeck.length} cards`
            });
        }

        // Rule 103.3: Rune deck exact size
        if (runeDeck.length !== RULES.RUNE_DECK_SIZE) {
            warnings.push({
                type: runeDeck.length === 0 ? 'warning' : 'error',
                message: `Rune deck must be exactly ${RULES.RUNE_DECK_SIZE} cards (currently ${runeDeck.length})`
            });
        } else {
            warnings.push({
                type: 'success',
                message: `Rune deck complete: ${RULES.RUNE_DECK_SIZE} cards`
            });
        }

        // Rule 103.2.d: Signature cards
        const signatureCount = mainDeck.filter(id => {
            const card = window.cardDatabase[id];
            return card && card.card_type === 'Signature';
        }).length;

        if (signatureCount > RULES.MAX_SIGNATURE_CARDS) {
            warnings.push({
                type: 'error',
                message: `Too many Signature cards (${signatureCount}/${RULES.MAX_SIGNATURE_CARDS})`
            });
        }

        displayWarnings(warnings);
        return warnings.filter(w => w.type === 'error').length === 0;
    }

    /**
     * Display validation warnings
     */
    function displayWarnings(warnings) {
        const container = document.getElementById('deckWarnings');
        if (!container) return;

        if (warnings.length === 0) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        warnings.forEach(warning => {
            html += `<div class="warning-item ${warning.type}">${warning.message}</div>`;
        });

        container.innerHTML = html;
    }

    /**
     * Filter cards by search
     */
    function filterCards(tabName, searchTerm) {
        renderCardGrid(tabName);

        if (!searchTerm) return;

        const gridId = tabName === 'main-deck' ? 'mainDeckCardGrid' :
                       tabName === 'rune-deck' ? 'runeDeckCardGrid' :
                       'battlefieldCardGrid';

        const grid = document.getElementById(gridId);
        const cards = grid.querySelectorAll('.library-card');

        searchTerm = searchTerm.toLowerCase();

        cards.forEach(cardDiv => {
            const cardId = parseInt(cardDiv.dataset.cardId);
            const card = window.cardDatabase[cardId];

            const matches = card.name.toLowerCase().includes(searchTerm) ||
                          (card.card_code && card.card_code.toLowerCase().includes(searchTerm));

            cardDiv.style.display = matches ? 'block' : 'none';
        });
    }

    /**
     * Save deck
     */
    async function saveDeck() {
        if (!validateDeck()) {
            alert('Please fix validation errors before saving');
            return;
        }

        const deckName = document.getElementById('deckName').value.trim();
        if (!deckName) {
            alert('Please enter a deck name');
            return;
        }

        const deckData = {
            action: 'save_deck_v2',
            deck_id: currentDeckId,
            deck_name: deckName,
            description: document.getElementById('deckDescription').value.trim(),
            champion_legend_id: selectedChampionLegend?.id,
            chosen_champion_id: chosenChampion?.id,
            main_deck: mainDeck,
            rune_deck: runeDeck,
            battlefields: battlefields
        };

        try {
            const response = await fetch('api/deck_v2.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(deckData)
            });

            const result = await response.json();

            if (result.success) {
                alert('Deck saved successfully!');
                if (result.deck_id && !currentDeckId) {
                    window.location.href = `deck_builder_v2.php?deck_id=${result.deck_id}`;
                }
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Failed to save deck');
        }
    }

    /**
     * Clear deck
     */
    function clearDeck() {
        if (!confirm('Clear all cards from this deck?')) return;

        mainDeck = [];
        runeDeck = [];
        battlefields = [];
        chosenChampion = null;

        updateDeckDisplay();
        updateAllCounts();
        validateDeck();
        removeChosenChampion();
        renderCardGrid('main-deck');
        renderCardGrid('rune-deck');
        renderCardGrid('battlefields');
    }

    /**
     * Export deck
     */
    function exportDeck() {
        let code = '# Riftbound Deck Export\n\n';

        if (selectedChampionLegend) {
            code += `Champion Legend: ${selectedChampionLegend.name}\n`;
        }

        if (chosenChampion) {
            code += `Chosen Champion: ${chosenChampion.name}\n\n`;
        }

        code += 'Main Deck:\n';
        const mainCounts = {};
        mainDeck.forEach(id => {
            const card = window.cardDatabase[id];
            if (card) {
                mainCounts[card.card_code] = (mainCounts[card.card_code] || 0) + 1;
            }
        });
        Object.entries(mainCounts).forEach(([code, count]) => {
            code += `${count}x ${code}\n`;
        });

        navigator.clipboard.writeText(code).then(() => {
            alert('Deck code copied to clipboard!');
        }).catch(() => {
            prompt('Copy this deck code:', code);
        });
    }

    // Export functions for global access
    window.riftboundDeckBuilder = {
        selectChampionLegend,
        removeChosenChampion,
        removeFromDeck
    };

})();
