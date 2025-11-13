// Deck Import Functionality
// Handles importing decks from various formats (TTS, standard, etc.)

(function() {
    'use strict';

    // Import Modal Elements
    const importDeckBtn = document.getElementById('importDeckBtn');
    const importDeckModal = document.getElementById('importDeckModal');
    const importTextarea = document.getElementById('importTextarea');
    const importProcessBtn = document.getElementById('importProcessBtn');
    const importCancelBtn = document.getElementById('importCancelBtn');
    const importResult = document.getElementById('importResult');
    const importCloseBtn = importDeckModal?.querySelector('.close');

    // Setup import modal
    if (importDeckBtn && importDeckModal) {
        importDeckBtn.addEventListener('click', () => {
            importDeckModal.classList.add('active');
            importTextarea.value = '';
            importResult.style.display = 'none';
        });

        importCancelBtn?.addEventListener('click', () => {
            importDeckModal.classList.remove('active');
        });

        importCloseBtn?.addEventListener('click', () => {
            importDeckModal.classList.remove('active');
        });

        importProcessBtn?.addEventListener('click', processImport);
    }

    /**
     * Process the imported deck code
     */
    function processImport() {
        const deckCode = importTextarea.value.trim();

        if (!deckCode) {
            showImportResult('Please paste a deck code', 'error');
            return;
        }

        try {
            console.log('=== IMPORT DEBUG ===');
            console.log('Input deck code:', deckCode);
            console.log('cardCodeDatabase exists?', !!window.cardCodeDatabase);
            console.log('cardCodeDatabase sample keys:', window.cardCodeDatabase ? Object.keys(window.cardCodeDatabase).slice(0, 5) : 'N/A');

            const cards = parseDeckCode(deckCode);
            console.log('Parsed cards:', cards);

            if (cards.length === 0) {
                showImportResult('No valid cards found in the deck code', 'error');
                return;
            }

            // Clear current deck
            if (window.currentDeck && window.currentDeck.length > 0) {
                if (!confirm('This will replace your current deck. Continue?')) {
                    return;
                }
                window.currentDeck = [];
            }

            // Import cards into deck
            let imported = 0;
            let notFound = [];

            cards.forEach(({ cardCode, quantity }) => {
                console.log('Looking up card:', cardCode);
                const card = findCardByCode(cardCode);
                console.log('Found card:', card);

                if (card) {
                    // Add the card the specified number of times
                    for (let i = 0; i < quantity; i++) {
                        window.addCardToDeck(card.id, card.name, card.energy || 0);
                    }
                    imported++;
                } else {
                    notFound.push(cardCode);
                }
            });

            // Show results
            let message = `Successfully imported ${imported} card types`;

            if (notFound.length > 0) {
                message += `<br><strong>Not found:</strong> ${notFound.join(', ')}`;
                showImportResult(message, 'error');
            } else {
                showImportResult(message, 'success');

                // Close modal after success
                setTimeout(() => {
                    importDeckModal.classList.remove('active');
                }, 2000);
            }

        } catch (error) {
            console.error('Import error:', error);
            showImportResult('Error parsing deck code: ' + error.message, 'error');
        }
    }

    /**
     * Parse deck code into array of {cardCode, quantity}
     * Supports multiple formats:
     * - TTS: OGN-259-1 OGN-076-2 (code-number format)
     * - Standard: 1x OGN-259 or 1 OGN-259
     * - Line format: One card per line
     */
    function parseDeckCode(deckCode) {
        const cards = [];
        const cardMap = new Map(); // Track quantities

        // Normalize input
        deckCode = deckCode.trim();

        // Try to detect format
        if (isTTSFormat(deckCode)) {
            return parseTTSFormat(deckCode);
        } else {
            return parseStandardFormat(deckCode);
        }
    }

    /**
     * Check if deck code is in TTS format
     * TTS format: OGN-259-1 OGN-076-2 (ends with -number)
     */
    function isTTSFormat(deckCode) {
        // Check if it contains patterns like XXX-NNN-N
        return /[A-Z]+-\d+-\d+/.test(deckCode);
    }

    /**
     * Parse TTS format: OGN-259-1 OGN-076-2 OGN-045-1
     * Format is: CARDCODE-VARIANT where variant is usually 1 (normal) or 2 (showcase)
     * We extract the base card code (e.g., OGN-259) and look it up by card_code
     */
    function parseTTSFormat(deckCode) {
        const cards = [];
        const cardMap = new Map();

        // Split by spaces and parse each entry
        const entries = deckCode.split(/\s+/);

        entries.forEach(entry => {
            entry = entry.trim();
            if (!entry) return;

            // Match pattern: XXX-NNN-V where V is variant (1 or 2)
            const match = entry.match(/^([A-Z]+-\d+)-\d+$/);

            if (match) {
                const cardCode = match[1]; // e.g., OGN-259 (base card code)

                // Count each occurrence
                if (cardMap.has(cardCode)) {
                    cardMap.set(cardCode, cardMap.get(cardCode) + 1);
                } else {
                    cardMap.set(cardCode, 1);
                }
            }
        });

        // Convert map to array
        cardMap.forEach((quantity, cardCode) => {
            cards.push({ cardCode, quantity });
        });

        return cards;
    }

    /**
     * Parse standard format:
     * - 1x OGN-259
     * - 1 OGN-259
     * - OGN-259 (assumes 1)
     * Supports newlines and spaces
     */
    function parseStandardFormat(deckCode) {
        const cards = [];
        const cardMap = new Map();

        // Split by newlines or multiple spaces
        const lines = deckCode.split(/\r?\n|(?:\s{2,})/);

        lines.forEach(line => {
            line = line.trim();
            if (!line) return;

            let quantity = 1;
            let cardCode = '';

            // Try format: 1x OGN-259 or 1 OGN-259
            let match = line.match(/^(\d+)x?\s+([A-Z]+-\d+)/);
            if (match) {
                quantity = parseInt(match[1], 10);
                cardCode = match[2];
            } else {
                // Try format: OGN-259 (no quantity, assume 1)
                match = line.match(/^([A-Z]+-\d+)/);
                if (match) {
                    cardCode = match[1];
                    quantity = 1;
                }
            }

            if (cardCode) {
                if (cardMap.has(cardCode)) {
                    cardMap.set(cardCode, cardMap.get(cardCode) + quantity);
                } else {
                    cardMap.set(cardCode, quantity);
                }
            }
        });

        // Convert map to array
        cardMap.forEach((quantity, cardCode) => {
            cards.push({ cardCode, quantity });
        });

        return cards;
    }

    /**
     * Find card by card code (case-insensitive)
     * @param {string} cardCode - e.g., "OGN-259" (base card code without variant)
     * @returns {object|null} - Card object or null
     */
    function findCardByCode(cardCode) {
        // Normalize to lowercase for case-insensitive lookup
        const normalizedCode = cardCode.toLowerCase();
        console.log('findCardByCode - input:', cardCode, 'normalized:', normalizedCode);

        // Use the global cardCodeDatabase created in the HTML
        // This database maps by card_code (without variant suffix)
        if (window.cardCodeDatabase && window.cardCodeDatabase[normalizedCode]) {
            console.log('Found in cardCodeDatabase');
            return window.cardCodeDatabase[normalizedCode];
        }

        // Fallback: search through all cards by card_code (case-insensitive)
        if (window.allCardsData) {
            console.log('Searching in allCardsData...');
            const card = window.allCardsData.find(card =>
                card.card_code && card.card_code.toLowerCase() === normalizedCode
            );
            if (card) {
                console.log('Found in allCardsData');
                return card;
            }
        }

        console.log('Card not found');
        return null;
    }

    /**
     * Show import result message
     * @param {string} message - Message to display
     * @param {string} type - 'success' or 'error'
     */
    function showImportResult(message, type) {
        importResult.innerHTML = message;
        importResult.className = 'import-result ' + type;
        importResult.style.display = 'block';
    }

    // Export functions for use in other scripts if needed
    window.deckImport = {
        parseDeckCode,
        findCardByCode
    };

})();
