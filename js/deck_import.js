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
            const cards = parseDeckCode(deckCode);

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
                const card = findCardByCode(cardCode);

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
     * Format: CARDCODE-QUANTITY
     */
    function parseTTSFormat(deckCode) {
        const cards = [];
        const cardMap = new Map();

        // Split by spaces and parse each entry
        const entries = deckCode.split(/\s+/);

        entries.forEach(entry => {
            // Match pattern: XXX-NNN-Q where Q is quantity
            const match = entry.match(/^([A-Z]+-\d+)-(\d+)$/);

            if (match) {
                const cardCode = match[1]; // e.g., OGN-259
                const quantity = parseInt(match[2], 10); // e.g., 1

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
     * Find card by card code
     * @param {string} cardCode - e.g., "OGN-259"
     * @returns {object|null} - Card object or null
     */
    function findCardByCode(cardCode) {
        // Use the global cardCodeDatabase created in the HTML
        if (window.cardCodeDatabase && window.cardCodeDatabase[cardCode]) {
            return window.cardCodeDatabase[cardCode];
        }

        // Fallback: search through all cards
        if (window.allCardsData) {
            return window.allCardsData.find(card => card.card_code === cardCode);
        }

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
