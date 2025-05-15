/**
 * Inventory management utilities
 * Handles container and product stock interactions
 */

const InventoryManager = {
    /**
     * Updates the container stock display throughout the page
     * @param {number} containerId - The ID of the container
     * @param {number} newStockValue - The new stock value
     */
    updateContainerStockDisplay: function(containerId, newStockValue) {
        // Update any container cards
        const containerCards = document.querySelectorAll(`.container-card .editable-stock[data-id="container-${containerId}"]`);
        containerCards.forEach(stockDisplay => {
            stockDisplay.textContent = `${newStockValue} pcs`;
        });
        
        // Update container dropdowns in product forms
        const containerOptions = document.querySelectorAll(`#productContainer option[value="${containerId}"], #editProductContainer option[value="${containerId}"]`);
        containerOptions.forEach(option => {
            option.setAttribute('data-stocks', newStockValue);
        });
        
        // If the container is currently selected in any dropdown, update the stock info
        if (document.getElementById('productContainer') && 
            document.getElementById('productContainer').value == containerId) {
            const event = new Event('change');
            document.getElementById('productContainer').dispatchEvent(event);
        }
        
        if (document.getElementById('editProductContainer') && 
            document.getElementById('editProductContainer').value == containerId) {
            const event = new Event('change');
            document.getElementById('editProductContainer').dispatchEvent(event);
        }
    },
    
    /**
     * Validates if there are enough container stocks for a product
     * @param {number} requestedStocks - The number of stocks requested
     * @param {HTMLSelectElement} containerSelect - The container select element
     * @returns {boolean} - Whether there are enough stocks
     */
    validateContainerStocks: function(requestedStocks, containerSelect) {
        const selectedOption = containerSelect.options[containerSelect.selectedIndex];
        if (selectedOption.value) {
            const availableStocks = parseInt(selectedOption.getAttribute('data-stocks'));
            return requestedStocks <= availableStocks;
        }
        return true;
    },
    
    /**
     * Updates stock badges on product or container cards
     * @param {HTMLElement} card - The card element
     * @param {number} newStockValue - The new stock value
     * @param {number} threshold - The threshold for low stock
     */
    updateStockBadge: function(card, newStockValue, threshold = 10) {
        const stockBadge = card.querySelector('.stock-badge');
        if (stockBadge) {
            if (newStockValue < threshold) {
                stockBadge.className = 'stock-badge low-stock';
                stockBadge.textContent = 'Low Stock';
            } else {
                stockBadge.className = 'stock-badge in-stock';
                stockBadge.textContent = 'In Stock';
            }
        }
    }
};

// Export the module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = InventoryManager;
}
