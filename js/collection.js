// Collection Management JavaScript
// Handles view toggling, filtering, sorting, quantity updates, and wishlist

(function() {
    'use strict';

    let currentView = 'all'; // all, owned, unowned
    let currentFilters = {
        search: '',
        set: '',
        region: '',
        rarity: '',
        type: '',
        wishlist: false
    };
    let currentSort = 'id-asc';

    // Initialize
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        setupViewToggle();
        setupFilters();
        setupSort();
        applyFiltersAndSort();
    }

    /**
     * Setup view toggle buttons
     */
    function setupViewToggle() {
        const toggleBtns = document.querySelectorAll('.view-toggle-btn');

        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active state
                toggleBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update current view
                currentView = this.dataset.view;

                // Apply filters
                applyFiltersAndSort();
            });
        });
    }

    /**
     * Setup filter controls
     */
    function setupFilters() {
        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                currentFilters.search = this.value.toLowerCase();
                applyFiltersAndSort();
            });
        }

        // Set filter
        const setFilter = document.getElementById('setFilter');
        if (setFilter) {
            setFilter.addEventListener('change', function() {
                currentFilters.set = this.value.toLowerCase();
                applyFiltersAndSort();
            });
        }

        // Region filter
        const regionFilter = document.getElementById('regionFilter');
        if (regionFilter) {
            regionFilter.addEventListener('change', function() {
                currentFilters.region = this.value.toLowerCase();
                applyFiltersAndSort();
            });
        }

        // Rarity filter
        const rarityFilter = document.getElementById('rarityFilter');
        if (rarityFilter) {
            rarityFilter.addEventListener('change', function() {
                currentFilters.rarity = this.value.toLowerCase();
                applyFiltersAndSort();
            });
        }

        // Type filter
        const typeFilter = document.getElementById('typeFilter');
        if (typeFilter) {
            typeFilter.addEventListener('change', function() {
                currentFilters.type = this.value.toLowerCase();
                applyFiltersAndSort();
            });
        }

        // Wishlist filter
        const wishlistFilter = document.getElementById('wishlistFilter');
        if (wishlistFilter) {
            wishlistFilter.addEventListener('change', function() {
                currentFilters.wishlist = this.checked;
                applyFiltersAndSort();
            });
        }
    }

    /**
     * Reset all filters
     */
    window.resetFilters = function() {
        // Reset filter values
        currentFilters = {
            search: '',
            set: '',
            region: '',
            rarity: '',
            type: '',
            wishlist: false
        };

        // Reset UI
        document.getElementById('searchInput').value = '';
        if (document.getElementById('setFilter')) document.getElementById('setFilter').value = '';
        if (document.getElementById('regionFilter')) document.getElementById('regionFilter').value = '';
        if (document.getElementById('rarityFilter')) document.getElementById('rarityFilter').value = '';
        if (document.getElementById('typeFilter')) document.getElementById('typeFilter').value = '';
        if (document.getElementById('wishlistFilter')) document.getElementById('wishlistFilter').checked = false;

        // Reset view to All
        currentView = 'all';
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === 'all');
        });

        // Reset sort
        currentSort = 'id-asc';
        document.getElementById('sortSelect').value = 'id-asc';

        // Apply
        applyFiltersAndSort();
    };

    /**
     * Setup sort control
     */
    function setupSort() {
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                currentSort = this.value;
                applyFiltersAndSort();
            });
        }
    }

    /**
     * Apply filters and sorting
     */
    function applyFiltersAndSort() {
        const grid = document.getElementById('collectionGrid');
        const cards = Array.from(grid.querySelectorAll('.collection-card'));
        const emptyState = document.getElementById('emptyState');

        let visibleCount = 0;

        // Filter cards
        cards.forEach(card => {
            let visible = true;

            // View filter (owned/unowned/all)
            const isOwned = card.dataset.owned === '1';
            if (currentView === 'owned' && !isOwned) {
                visible = false;
            } else if (currentView === 'unowned' && isOwned) {
                visible = false;
            }

            // Search filter
            if (visible && currentFilters.search) {
                const name = card.dataset.name;
                const cardId = card.querySelector('.card-image-container img')?.alt || '';
                if (!name.includes(currentFilters.search) &&
                    !cardId.toLowerCase().includes(currentFilters.search)) {
                    visible = false;
                }
            }

            // Set filter
            if (visible && currentFilters.set) {
                const cardSet = card.dataset.set || '';
                if (cardSet !== currentFilters.set) {
                    visible = false;
                }
            }

            // Region filter
            if (visible && currentFilters.region) {
                if (card.dataset.region !== currentFilters.region) {
                    visible = false;
                }
            }

            // Rarity filter
            if (visible && currentFilters.rarity) {
                if (card.dataset.rarity !== currentFilters.rarity) {
                    visible = false;
                }
            }

            // Type filter
            if (visible && currentFilters.type) {
                if (card.dataset.type !== currentFilters.type) {
                    visible = false;
                }
            }

            // Wishlist filter
            if (visible && currentFilters.wishlist) {
                if (card.dataset.wishlisted !== '1') {
                    visible = false;
                }
            }

            // Apply visibility
            card.style.display = visible ? 'block' : 'none';
            if (visible) visibleCount++;
        });

        // Sort visible cards
        sortCards(cards.filter(c => c.style.display !== 'none'));

        // Show/hide empty state
        if (visibleCount === 0) {
            grid.style.display = 'none';
            emptyState.style.display = 'block';
        } else {
            grid.style.display = 'grid';
            emptyState.style.display = 'none';
        }
    }

    /**
     * Sort cards
     */
    function sortCards(cards) {
        const grid = document.getElementById('collectionGrid');

        cards.sort((a, b) => {
            const [field, direction] = currentSort.split('-');
            let aVal, bVal;

            switch (field) {
                case 'id':
                    aVal = a.dataset.cardId;
                    bVal = b.dataset.cardId;
                    break;
                case 'name':
                    aVal = a.dataset.name;
                    bVal = b.dataset.name;
                    break;
                case 'energy':
                    aVal = parseInt(a.dataset.energy) || 0;
                    bVal = parseInt(b.dataset.energy) || 0;
                    break;
                case 'rarity':
                    const rarityOrder = { 'common': 1, 'uncommon': 2, 'rare': 3, 'epic': 4, 'champion': 5, 'legend': 6 };
                    aVal = rarityOrder[a.dataset.rarity] || 0;
                    bVal = rarityOrder[b.dataset.rarity] || 0;
                    break;
                default:
                    return 0;
            }

            if (direction === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        // Re-append in sorted order
        cards.forEach(card => grid.appendChild(card));
    }

    /**
     * Update card quantity
     */
    window.updateQuantity = async function(cardId, change) {
        const card = document.querySelector(`[data-card-id="${cardId}"]`);
        if (!card) return;

        const quantityDisplay = card.querySelector('.quantity-display');
        const currentQuantity = parseInt(quantityDisplay.textContent) || 0;
        const newQuantity = Math.max(0, currentQuantity + change);

        try {
            const response = await fetch('api/collection.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_quantity',
                    card_id: cardId,
                    quantity: newQuantity
                })
            });

            const result = await response.json();

            if (result.success) {
                // Update UI
                quantityDisplay.textContent = newQuantity;

                // Update owned status
                const wasOwned = card.dataset.owned === '1';
                const isNowOwned = newQuantity > 0;
                card.dataset.owned = isNowOwned ? '1' : '0';

                // Update visual state
                if (isNowOwned) {
                    card.classList.remove('unowned');
                } else {
                    card.classList.add('unowned');
                }

                // Update minus button state
                const minusBtn = card.querySelector('.quantity-btn:first-child');
                if (minusBtn) {
                    minusBtn.disabled = newQuantity <= 0;
                }

                // Update stats
                updateCollectionStats();

                // Reapply filters (for owned/unowned view)
                applyFiltersAndSort();
            } else {
                alert('Error updating quantity: ' + result.message);
            }
        } catch (error) {
            console.error('Update quantity error:', error);
            alert('Failed to update card quantity');
        }
    };

    /**
     * Toggle wishlist
     */
    window.toggleWishlist = async function(cardId) {
        const card = document.querySelector(`[data-card-id="${cardId}"]`);
        if (!card) return;

        const wishlistBtn = card.querySelector('.wishlist-btn');
        const isWishlisted = card.dataset.wishlisted === '1';
        const newState = !isWishlisted;

        try {
            const response = await fetch('api/collection.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: newState ? 'add_to_wishlist' : 'remove_from_wishlist',
                    card_id: cardId
                })
            });

            const result = await response.json();

            if (result.success) {
                // Update UI
                card.dataset.wishlisted = newState ? '1' : '0';
                wishlistBtn.classList.toggle('active', newState);
                wishlistBtn.textContent = newState ? '❤️' : '🤍';

                // Reapply filters (if wishlist filter is active)
                if (currentFilters.wishlist) {
                    applyFiltersAndSort();
                }
            } else {
                alert('Error updating wishlist: ' + result.message);
            }
        } catch (error) {
            console.error('Wishlist error:', error);
            alert('Failed to update wishlist');
        }
    };

    /**
     * Update collection statistics
     */
    function updateCollectionStats() {
        const cards = document.querySelectorAll('.collection-card');
        let totalCards = 0;
        let ownedUnique = 0;

        cards.forEach(card => {
            const quantity = parseInt(card.querySelector('.quantity-display').textContent) || 0;
            if (quantity > 0) {
                totalCards += quantity;
                ownedUnique++;
            }
        });

        // Update stats display
        const totalCardsEl = document.querySelector('.stat-card:nth-child(2) .stat-value');
        const uniqueCardsEl = document.querySelector('.stat-card:nth-child(3) .stat-value');
        const completionEl = document.querySelector('.stat-card:nth-child(4) .stat-value');

        if (totalCardsEl) totalCardsEl.textContent = totalCards;

        if (uniqueCardsEl) {
            const totalUnique = cards.length;
            uniqueCardsEl.textContent = `${ownedUnique} / ${totalUnique}`;
        }

        if (completionEl) {
            const totalUnique = cards.length;
            const percentage = totalUnique > 0 ? Math.round((ownedUnique / totalUnique) * 100) : 0;
            completionEl.textContent = `${percentage}%`;
        }
    }

    // Export functions
    window.collectionManager = {
        applyFiltersAndSort,
        updateQuantity,
        toggleWishlist
    };

})();
