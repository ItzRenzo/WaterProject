// Path: c:\xampp\htdocs\WaterProject\js\order.js
document.addEventListener('DOMContentLoaded', function() {
    // Set cashier name from PHP variable if available
    const cashierNameDisplay = document.getElementById('cashier-name-display');
    const headerCashierName = document.getElementById('header-cashier-name');
    
    if (typeof PHP_CASHIER_NAME !== 'undefined' && PHP_CASHIER_NAME) {
        console.log("Setting cashier name to: " + PHP_CASHIER_NAME);
        
        // Set cashier name in the review page
        if (cashierNameDisplay) {
            cashierNameDisplay.textContent = PHP_CASHIER_NAME;
        } else {
            console.log("Cashier name display element not found in review section");
        }
        
        // Set cashier name in the header
        if (headerCashierName) {
            headerCashierName.textContent = PHP_CASHIER_NAME;
        } else {
            console.log("Header cashier name element not found");
        }
    } else {
        console.log("PHP_CASHIER_NAME not defined");
        if (cashierNameDisplay) cashierNameDisplay.textContent = "Not logged in";
        if (headerCashierName) headerCashierName.textContent = "Not logged in";
    }
    
    // Display stock information for each product
    if (typeof PHP_PRODUCT_STOCKS !== 'undefined') {
        document.querySelectorAll('.product-card').forEach(card => {
            const productName = card.querySelector('h3').textContent;
            
            // Check if this product has stock information
            if (PHP_PRODUCT_STOCKS[productName] !== undefined) {
                const availableStock = PHP_PRODUCT_STOCKS[productName];
                
                // Create or update stock information element
                let stockInfo = card.querySelector('.stock-info');
                if (!stockInfo) {
                    stockInfo = document.createElement('div');
                    stockInfo.className = 'stock-info';
                    card.querySelector('.product-info').appendChild(stockInfo);
                }
                
                stockInfo.textContent = `Stock: ${availableStock}`;
                
                // Disable add button if stock is 0
                if (availableStock <= 0) {
                    const addButton = card.querySelector('.add-to-cart');
                    if (addButton) {
                        addButton.disabled = true;
                        addButton.textContent = 'Out of Stock';
                    }
                }
            }
        });
    }
    
    // Get all Add to Cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartTotalElement = document.getElementById('cart-total');
    const cartCountElement = document.querySelector('.cart-count');

    // Cart data
    let cartItems = [];
    let cartTotal = 0;

    // Make cartItems accessible globally
    window.cartItems = cartItems;

    // Make updateNavButtons globally available
    function updateNavButtons() {
        // Hide Back button on first tab (products)
        if (typeof currentTabIndex === 'undefined') return;
        if (currentTabIndex === 0) {
            prevBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'block';
        }
        // Handle success tab (index 4, not in .order-tabs)
        if (currentTabIndex === 4) {
            nextBtn.style.display = 'none';
            return;
        }
        // Change Continue button text to Place Order on review tab (index 3)
        if (currentTabIndex === 3) {
            nextBtn.textContent = 'Place Order';
            nextBtn.style.display = 'block';
        } else {
            nextBtn.textContent = 'Continue';
            nextBtn.style.display = 'block';
        }
    }
    
    // Make it accessible outside the DOM content loaded event
    window.updateNavButtons = updateNavButtons;

    // Add event listeners to quantity buttons
    const decreaseButtons = document.querySelectorAll('.decrease-inline');
    const increaseButtons = document.querySelectorAll('.increase-inline');

    decreaseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const qtyElement = this.nextElementSibling;
            let currentQty = parseInt(qtyElement.textContent);
            if (currentQty > 1) {
                qtyElement.textContent = currentQty - 1;
            }
        });
    });

    increaseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const qtyElement = this.previousElementSibling;
            let currentQty = parseInt(qtyElement.textContent);
            qtyElement.textContent = currentQty + 1;
        });
    });    // Add to cart functionality
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get product information
            const productCard = this.closest('.product-card');
            const productInfo = this.closest('.product-info');
            const productName = productInfo.querySelector('h3').textContent;
            const priceText = productInfo.querySelector('.price').textContent;
            const price = parseFloat(priceText.replace('₱', ''));
            const quantityElement = productInfo.querySelector('.qty-inline-value');
            const quantity = parseInt(quantityElement.textContent);

            // Add to cart with stock check
            addToCart(productName, price, quantity);

            // Reset quantity to 1 after adding to cart
            quantityElement.textContent = '1';
        });
    });

    function addToCart(name, price, quantity) {
        // Check available stock (if we have stock information from PHP)
        if (typeof PHP_PRODUCT_STOCKS !== 'undefined') {
            // Check if this product has stock information
            if (PHP_PRODUCT_STOCKS[name] !== undefined) {
                const availableStock = PHP_PRODUCT_STOCKS[name];
                
                // Calculate current quantity in cart of this product
                const existingItemIndex = cartItems.findIndex(item => item.name === name);
                const currentQuantityInCart = existingItemIndex !== -1 ? cartItems[existingItemIndex].quantity : 0;
                
                // Check if adding this quantity would exceed available stock
                if (currentQuantityInCart + quantity > availableStock) {
                    alert(`Sorry, only ${availableStock} unit(s) of ${name} are available.`);
                    return;
                }
            }
        }
        
        // Check if the product already exists in the cart
        const existingItemIndex = cartItems.findIndex(item => item.name === name);

        if (existingItemIndex !== -1) {
            // Update quantity if the product already exists
            cartItems[existingItemIndex].quantity += quantity;
        } else {
            // Add new item to cart
            cartItems.push({
                name: name,
                price: price,
                quantity: quantity
            });
        }

        // Update the cart UI
        updateCartUI();
    }

    // Replace the updateCartUI function with this updated version
    function updateCartUI() {
        // Clear current cart display
        cartItemsContainer.innerHTML = '';        // If cart is empty
        if (cartItems.length === 0) {
            cartItemsContainer.innerHTML = '<div class="empty-cart">Cart is empty. Please add products.</div>';
            cartTotalElement.textContent = '₱0.00';
            cartCountElement.textContent = '0';
            
            // Update payment and review tabs if they exist
            const paymentOrderTotal = document.getElementById('payment-order-total');
            if (paymentOrderTotal) {
                paymentOrderTotal.textContent = '₱0.00';
            }
            
            // Also update change calculation
            updateChange();
            return;
        }

        // Calculate total
        cartTotal = 0;
        let totalItems = 0;

        // Add each item to the cart
        cartItems.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            cartTotal += itemTotal;
            totalItems += item.quantity;

            const cartItemElement = document.createElement('div');
            cartItemElement.classList.add('cart-item');
            cartItemElement.innerHTML = `
                <div class="cart-item-info">
                    <div class="cart-item-details">
                        <span class="cart-item-name">${item.name}</span>
                        <span class="cart-item-quantity">Quantity: ${item.quantity}</span>
                        <span class="cart-item-price">₱${item.price.toFixed(2)}</span>
                    </div>
                </div>
                <div class="cart-item-actions">
                    <button class="remove-item" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            cartItemsContainer.appendChild(cartItemElement);
        });

        // Add event listeners to remove buttons
        const removeButtons = document.querySelectorAll('.remove-item');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                removeFromCart(index);
            });
        });

        // Update cart count and total
        cartCountElement.textContent = totalItems.toString();
        cartTotalElement.textContent = `₱${cartTotal.toFixed(2)}`;
    }
    
    // Make it globally accessible
    window.updateCartUI = updateCartUI;

    function removeFromCart(index) {
        // Remove the item from the cart
        cartItems.splice(index, 1);
        // Update the cart UI
        updateCartUI();
    }

    // Tab navigation functionality
    const tabButtons = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');

    // Initialize with the products tab
    let currentTabIndex = 0;
    updateNavButtons();

    // Remove both existing nextBtn event listeners and replace with this one
    nextBtn.addEventListener('click', function() {
        // When on products tab (first tab)
        if (currentTabIndex === 0) {
            if (cartItems.length === 0) {
                alert('Please add at least one product to your cart before continuing.');
                return;
            }
            currentTabIndex++;
            updateTabs();
            updateNavButtons();
            return;
        }

        // When on customer details tab (second tab)
        if (currentTabIndex === 1) {
            const name = document.getElementById('name').value;
            const phone = document.getElementById('phone').value;
            const delivery = document.getElementById('delivery').value;
            const address = document.getElementById('address').value;

            // Validate required fields on customer tab before proceeding
            if (!name.trim()) {
                alert('Please enter your name');
                return;
            }

            if (!phone.match(/^09\d{9}$/)) {
                alert('Phone number must start with 09 and be exactly 11 digits');
                return;
            }

            if (delivery === 'delivery' && !address.trim()) {
                alert('Please provide a delivery address');
                return;
            }
        }

        // When on payment tab (third tab)
        if (currentTabIndex === 2) {
            // Payment validation logic - simplified
            const paymentAmount = document.getElementById('payment-amount').value;
            const finalAmount = getFinalAmount();

            if (!paymentAmount || paymentAmount === '0') {
                alert('Please enter a payment amount.');
                return;
            }

            if (parseFloat(paymentAmount) < finalAmount) {
                alert('Payment amount must be equal to or greater than the total amount due.');
                return;
            }
        }    // When on review tab (fourth tab) - Place Order button
    if (currentTabIndex === 3) {
        submitOrder();
        return;
    }

        // Default behavior - proceed to next tab if not returned earlier
        if (currentTabIndex < tabContents.length - 1) {
            currentTabIndex++;
            updateTabs();
            updateNavButtons();

            // If moving to payment tab, update payment content
            if (currentTabIndex === 2) {
                updatePaymentTab();
            }
            // If moving to review tab, update review content
            if (currentTabIndex === 3) {
                updateReviewTab();
            }
        }
    });

    // Back button functionality
    prevBtn.addEventListener('click', function() {
        if (currentTabIndex > 0) {
            currentTabIndex--;
            updateTabs();
            updateNavButtons();
        }    });

    // Tab functionality is now controlled only by the Next/Back buttons
    // Click events for tabs are now disabled

    function updateTabs() {
        // Update the active tab
        tabButtons.forEach((tab, index) => {
            if (index === currentTabIndex) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        // Update the visible content
        tabContents.forEach((content, index) => {
            if (index === currentTabIndex) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });
    }    function updateReviewTab() {
        console.log("Updating review tab");
        
        // Get the review tab content and clear existing order items
        const reviewTab = document.getElementById('tab-review');
        const orderSummaryEl = reviewTab.querySelector('.order-summary');
        
        // Clear existing items except for the header
        const headerEl = orderSummaryEl.querySelector('.summary-header');
        orderSummaryEl.innerHTML = '';
        orderSummaryEl.appendChild(headerEl);
        
        // If there are no items in the cart, show a message
        if (cartItems.length === 0) {
            const emptyEl = document.createElement('div');
            emptyEl.className = 'summary-item';
            emptyEl.textContent = 'No items in cart';
            orderSummaryEl.appendChild(emptyEl);
            return;
        }
        
        // Add each item from the cart to the order summary
        let totalAmount = 0;
        
        cartItems.forEach(item => {
            const itemTotal = item.price * item.quantity;
            totalAmount += itemTotal;
            
            const itemEl = document.createElement('div');
            itemEl.className = 'summary-item';
            itemEl.innerHTML = `
                <span>${item.name} x ${item.quantity}</span>
                <span>₱${itemTotal.toFixed(2)}</span>
            `;
            orderSummaryEl.appendChild(itemEl);
        });
        
        // Add the total
        const totalEl = document.createElement('div');
        totalEl.className = 'summary-item total';
        totalEl.innerHTML = `
            <span>Total:</span>
            <span>₱${totalAmount.toFixed(2)}</span>
        `;
        orderSummaryEl.appendChild(totalEl);
        
        // Update customer details section
        const customerDetails = reviewTab.querySelector('.customer-details');
        if (customerDetails) {
            const name = document.getElementById('name').value;
            const phone = document.getElementById('phone').value;
            const payment = document.getElementById('payment').value;
            const delivery = document.getElementById('delivery').value;
            const address = document.getElementById('address').value;
            const paymentAmount = document.getElementById('payment-amount').value || '0';

            // Calculate change amount
            const cartTotalText = document.getElementById('cart-total').textContent.replace(/[₱,]/g, '');
            const due = parseFloat(cartTotalText) || 0;
            const paid = parseFloat(paymentAmount) || 0;
            const change = paid - due;

            // Format payment and delivery options to be more readable
            const paymentFormatted = payment === 'cash' ? 'Cash' : 'GCash';
            const deliveryFormatted = delivery === 'pickup' ? 'Pick-up' : 'Delivery';

            // Update the customer information fields
            document.getElementById('summary-customer-name').textContent = `Name: ${name}`;
            document.getElementById('summary-customer-phone').textContent = `Phone: ${phone}`;
            document.getElementById('summary-customer-payment').textContent = `Payment Method: ${paymentFormatted}`;
            document.getElementById('summary-customer-delivery').textContent = `Delivery Method: ${deliveryFormatted}`;

            // Update address based on delivery option
            if (delivery === 'delivery') {
                document.getElementById('summary-customer-address').textContent = `Address: ${address}`;
            } else {
                document.getElementById('summary-customer-address').textContent = 'Address: N/A (Pick-up)';
            }

            // Add payment amount and change information
            const paymentInfo = document.getElementById('summary-payment-amount') || document.createElement('p');
            if (!paymentInfo.id) {
                paymentInfo.id = 'summary-payment-amount';
                customerDetails.appendChild(paymentInfo);
            }
            paymentInfo.textContent = `Payment Amount: ₱${parseFloat(paymentAmount).toFixed(2)}`;

            const changeInfo = document.getElementById('summary-change-amount') || document.createElement('p');
            if (!changeInfo.id) {
                changeInfo.id = 'summary-change-amount';
                customerDetails.appendChild(changeInfo);
            }            changeInfo.textContent = `Change: ₱${Math.max(0, change).toFixed(2)}`;

            // Show total quantity from cart items
            const totalQuantity = cartItems.reduce((sum, item) => sum + (item.quantity || 0), 0);
            document.getElementById('summary-customer-quantity').textContent = 'Quantity: ' + totalQuantity;
        }
    }

    // --- Show stocks in tab-products ---    // Stock display is now handled only once at page load

    function processOrder() {
        // Here you would typically send the order to the server
        // For now, we'll just navigate to the success page
        currentTabIndex = tabContents.length - 1;
        updateTabs();
        updateNavButtons();

        // Generate order number
        const orderNumber = 'RJ' + Math.floor(100000 + Math.random() * 900000);

        // Update success message with order details
        const successTab = document.querySelector('#tab-success');
        if (successTab) {
            const orderNumberElement = successTab.querySelector('.order-number') || document.createElement('div');
            if (!orderNumberElement.classList.contains('order-number')) {
                orderNumberElement.classList.add('order-number');
                successTab.querySelector('.success-content')?.appendChild(orderNumberElement);
            }
            orderNumberElement.textContent = orderNumber;
        }
    }

    // Handle showing/hiding the address field based on delivery option
    const deliverySelect = document.getElementById('delivery');
    const addressGroup = document.querySelector('label[for="address"]').closest('.form-group');

    // Hide address field on page load (since pickup is default)
    addressGroup.style.display = 'none';

    // Add event listener to delivery select
    deliverySelect.addEventListener('change', function() {
        if (this.value === 'pickup') {
            // Hide address field for pickup
            addressGroup.style.display = 'none';
        } else {
            // Show address field for delivery
            addressGroup.style.display = 'block';
        }
    });

    // Get the phone input field
    const phoneInput = document.getElementById('phone');

    // Add input event listener for phone validation
    phoneInput.addEventListener('input', function() {
        // Remove non-digit characters
        this.value = this.value.replace(/\D/g, '');

        // Ensure it starts with 09
        if (this.value.length >= 2 && this.value.substring(0, 2) !== '09') {
            this.value = '09' + this.value.substring(2);
        }

        // Limit to 11 digits
        if (this.value.length > 11) {
            this.value = this.value.substring(0, 11);
        }
    });

    // Set cashier hidden input if available from PHP session
    const cashierInput = document.getElementById('cashier');
    if (cashierInput) {
        // This value will be set by PHP if embedded, or left blank
        cashierInput.value = (typeof window.cashierId !== 'undefined' && window.cashierId) ? window.cashierId : (typeof PHP_CASHIER_ID !== 'undefined' ? PHP_CASHIER_ID : '');
    }    // Make the submitOrder function accessible globally
    window.submitOrder = submitOrder;function updatePaymentTab() {
        // Update the order total in the payment tab
        const paymentOrderTotal = document.getElementById('payment-order-total');
        if (paymentOrderTotal) {
            // Get the cart total
            const cartTotal = document.getElementById('cart-total').textContent;
            paymentOrderTotal.textContent = cartTotal;
            console.log('Updated payment tab total to:', cartTotal);
        }
        // Reset payment field
        document.getElementById('payment-amount').value = '';
        updateChange();
    }

    function getFinalAmount() {
        // Get the numeric value from the order total span
        const text = document.getElementById('payment-order-total').textContent.replace(/[₱,]/g, '');
        return parseFloat(text) || 0;
    }    function updateChange() {
        const paymentInput = document.getElementById('payment-amount');
        const changeAmount = document.getElementById('change-amount');
        const paymentOrderTotal = document.getElementById('payment-order-total');
        
        if (!paymentInput || !changeAmount || !paymentOrderTotal) {
            console.error('Missing required elements for change calculation');
            return;
        }
        
        const paid = parseFloat(paymentInput.value) || 0;
        const totalText = paymentOrderTotal.textContent.replace(/[₱,]/g, '');
        const due = parseFloat(totalText) || 0;
        let change = paid - due;
        
        console.log('Change calculation - Paid:', paid, 'Due:', due, 'Change:', change);

        // Show alert if payment is insufficient
        if (change < 0) {
            changeAmount.textContent = '₱0.00';
            changeAmount.style.color = '#e74c3c';
            // Alert is used for validation in the next button handler
        } else {
            changeAmount.textContent = `₱${change.toFixed(2)}`;
            changeAmount.style.color = ''; // Reset to default
        }
    }

    // Quick payment buttons
    document.querySelectorAll('.quick-payment').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (this.classList.contains('exact-amount')) {
                document.getElementById('payment-amount').value = getFinalAmount();
            } else {
                document.getElementById('payment-amount').value = this.dataset.amount;
            }
            updateChange();
        });
    });

    // Also update the payment-amount input to show the message on input
    document.getElementById('payment-amount').addEventListener('input', function() {
        updateChange();
    });

    // Check for tab parameter in URL to navigate to specific tab
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');

    if (tabParam === 'success') {
        // Find the index of the success tab
        const successTabIndex = Array.from(tabContents).findIndex(tab => tab.id === 'tab-success');
        if (successTabIndex !== -1) {
            currentTabIndex = successTabIndex;
            updateTabs();
        }
    }
});

function setupTabNavigation() {
    // Tab navigation functionality
    const tabs = document.querySelectorAll('.tab');
    const nextButtons = document.querySelectorAll('.next-btn');
    const prevButtons = document.querySelectorAll('.prev-btn');
    
    // Forward navigation
    nextButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (validateCurrentTab()) {
                navigateToNextTab();
            }
        });
    });
    
    // Backward navigation
    prevButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            navigateToPrevTab();
        });
    });
}

function setupProductSelection() {
    // Product selection functionality
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        card.addEventListener('click', function() {
            // Toggle selection
            this.classList.toggle('selected');
            updateOrderSummary();
        });
    });
    
    // Quantity change handlers
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateOrderSummary();
        });
    });
}

function setupPaymentHandling() {
    // Payment handling
    const paymentAmount = document.getElementById('payment-amount');
    
    paymentAmount.addEventListener('input', function() {
        updateChange();
    });
}

function setupFormValidation() {
    // Form validation
    const customerForm = document.getElementById('customer-form');
    
    if (customerForm) {
        customerForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateCustomerForm()) {
                navigateToNextTab();
            }
        });
    }
}

function validateCurrentTab() {
    // Validation logic for the current tab
    const currentTab = document.querySelector('.tab-content:not([style*="display: none"])');
    
    if (currentTab.id === 'tab-products') {
        return validateProductSelection();
    } else if (currentTab.id === 'tab-customer') {
        return validateCustomerForm();
    } else if (currentTab.id === 'tab-payment') {
        return validatePayment();
    }
    
    return true;
}

function validateProductSelection() {
    const selectedProducts = document.querySelectorAll('.product-card.selected');
    if (selectedProducts.length === 0) {
        alert('Please select at least one product');
        return false;
    }
    return true;
}

function validateCustomerForm() {
    // Implement customer form validation
    return true; // Simplified for now
}

function validatePayment() {
    const paymentAmount = parseFloat(document.getElementById('payment-amount').value) || 0;
    const totalAmount = parseFloat(document.getElementById('order-total').textContent) || 0;
    
    if (paymentAmount < totalAmount) {
        addPaymentErrorMessage();
        return false;
    }
    
    return true;
}

function navigateToNextTab() {
    const tabContents = document.querySelectorAll('.tab-content');
    const tabs = document.querySelectorAll('.tab');
    let currentTabIndex = -1;
    
    tabContents.forEach((content, index) => {
        if (content.style.display !== 'none') {
            currentTabIndex = index;
        }
    });
    
    if (currentTabIndex < tabContents.length - 1) {
        // Hide current tab
        tabContents[currentTabIndex].style.display = 'none';
        tabs[currentTabIndex].classList.remove('active');
        
        // Show next tab
        tabContents[currentTabIndex + 1].style.display = 'block';
        tabs[currentTabIndex + 1].classList.add('active');
    }
}

function navigateToPrevTab() {
    const tabContents = document.querySelectorAll('.tab-content');
    const tabs = document.querySelectorAll('.tab');
    let currentTabIndex = -1;
    
    tabContents.forEach((content, index) => {
        if (content.style.display !== 'none') {
            currentTabIndex = index;
        }
    });
    
    if (currentTabIndex > 0) {
        // Hide current tab
        tabContents[currentTabIndex].style.display = 'none';
        tabs[currentTabIndex].classList.remove('active');
        
        // Show previous tab
        tabContents[currentTabIndex - 1].style.display = 'block';
        tabs[currentTabIndex - 1].classList.add('active');
    }
}

function updateOrderSummary() {
    // Update order summary based on selected products
    const selectedProducts = document.querySelectorAll('.product-card.selected');
    const orderItems = document.getElementById('order-items');
    const orderTotal = document.getElementById('order-total');
    
    // Clear current items
    orderItems.innerHTML = '';
    
    let total = 0;
    
    selectedProducts.forEach(product => {
        const productName = product.querySelector('h3').textContent;
        const productPrice = parseFloat(product.querySelector('.price').textContent.replace('₱', ''));
        const quantityInput = product.querySelector('.quantity-input');
        const quantity = parseInt(quantityInput ? quantityInput.value : 1);
        
        const itemTotal = productPrice * quantity;
        total += itemTotal;
        
        // Add to order summary
        const item = document.createElement('div');
        item.className = 'order-item';
        item.innerHTML = `
            <span>${productName} x ${quantity}</span>
            <span>₱${itemTotal.toFixed(2)}</span>
        `;
        
        orderItems.appendChild(item);
    });
    
    // Update total
    orderTotal.textContent = total.toFixed(2);
    
    // Update payment field if present
    updateChange();
}

function updateChange() {
    const totalAmount = parseFloat(document.getElementById('order-total').textContent) || 0;
    const paymentAmount = parseFloat(document.getElementById('payment-amount').value) || 0;
    const changeAmount = document.getElementById('change-amount');
      if (paymentAmount >= totalAmount) {
        const change = paymentAmount - totalAmount;
        changeAmount.textContent = change.toFixed(2);
    } else {
        changeAmount.textContent = '0.00';
    }
}

// Function to submit the order to PHP
function submitOrder() {
    console.log('submitOrder function called');
    try {
        // Use window.cartItems to ensure correct reference
        const items = window.cartItems || [];
        const name = document.getElementById('name').value;
        const phone = document.getElementById('phone').value;
        const payment = document.getElementById('payment').value;
        const delivery = document.getElementById('delivery').value;
        const address = document.getElementById('address').value;
        const paymentAmount = document.getElementById('payment-amount').value || '0';
        const cashierInput = document.getElementById('cashier');
        const cartTotalText = document.getElementById('cart-total').textContent.replace(/[₱,]/g, '');
        const finalAmount = parseFloat(cartTotalText) || 0;
        // Log values for debugging
        console.log('Payment amount:', paymentAmount);
        console.log('Final amount:', finalAmount);
        console.log('Cart total:', cartTotalText);
        console.log('Cart items:', items);
        console.log('Sending to URL:', window.location.href);
        // Validate cart items
        if (!items || items.length === 0) {
            alert('Your cart is empty. Please add items before placing an order.');
            currentTabIndex = 0;
            updateTabs();
            updateNavButtons();
            return;
        }
        // Additional validation for customer information
        if (!name || !phone) {
            alert('Please provide your name and phone number.');
            // Switch to the customer tab
            currentTabIndex = 1;
            updateTabs();
            updateNavButtons();
            return;
        }
        
        // Validate phone number format (must start with 09 and be 11 digits)
        if (!phone.match(/^09\d{9}$/)) {
            alert('Phone number must start with 09 and be exactly 11 digits');
            // Switch to the customer tab
            currentTabIndex = 1;
            updateTabs();
            updateNavButtons();
            return;
        }
        
        // Validate address for delivery
        if (delivery === 'delivery' && !address.trim()) {
            alert('Please provide a delivery address');
            // Switch to the customer tab
            currentTabIndex = 1;
            updateTabs();
            updateNavButtons();
            return;
        }
        // Validate payment amount
        if (parseFloat(paymentAmount) < finalAmount) {
            alert('Payment amount must be equal to or greater than the total amount due.');
            return;
        }
        // Create form data
        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('payment', payment);
        formData.append('delivery', delivery);
        formData.append('address', address);
        formData.append('cart_total', finalAmount);
        formData.append('payment_amount', paymentAmount);
        formData.append('cashier', cashierInput ? cashierInput.value : '');
        formData.append('cart_items', JSON.stringify(items));
        // Log what's being sent for debugging
        console.log('Submitting order with cart total:', finalAmount);
        console.log('Payment amount being sent:', paymentAmount);
        console.log('Cart items being sent:', JSON.stringify(cartItems));
        
        // Show loading indicator
        const nextBtn = document.getElementById('next-btn');
        const originalBtnText = nextBtn.textContent;
        nextBtn.disabled = true;
        nextBtn.textContent = 'Processing...';    
        
        // Send AJAX request to the current page (order.php)
        console.log('Sending order data to:', window.location.href);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    // First try to parse as JSON
                    return JSON.parse(text);
                } catch (e) {
                    // If it's not valid JSON, log the raw response and throw an error
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            // Enable button
            nextBtn.disabled = false;
            nextBtn.textContent = originalBtnText;
            
            console.log('Server response:', data);
            
            if (data.success) {
                console.log("Order successful");

                // Navigate to success tab
                const successTab = document.getElementById('tab-success');
                if (successTab) {
                    // Update current tab index to success tab
                    currentTabIndex = 4; // Set to index of success tab
                    
                    // Hide all tab contents
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.style.display = 'none';
                    });
                    
                    // Show success tab
                    successTab.style.display = 'block';
                    
                    // Update nav buttons for success state (no alert)
                    updateNavButtons();
                }

                // Get cashier name from response
                const cashierName = data.cashier_name || 'Cashier';

                // Get current date and time
                const orderDate = new Date().toLocaleString();

                // Prepare receipt data
                const receiptData = {
                    orderNumber: data.order_number,
                    orderDate: orderDate,
                    customerName: name,
                    cashierName: cashierName,
                    customerPhone: phone,
                    paymentMethod: payment === 'cash' ? 'Cash' : 'GCash',
                    deliveryMethod: delivery === 'pickup' ? 'Pick-up' : 'Home Delivery',
                    customerAddress: address,
                    items: cartItems || [],
                    total: finalAmount,
                    paymentAmount: parseFloat(paymentAmount),
                    change: data.change
                };                // Update the receipt link's href to include the order data
                const receiptLink = document.querySelector('.Receipt-link');
                if (receiptLink) {
                    receiptLink.href = `Reciept.php?latest=true`;
                }

                // Clear cart after successful order
                cartItems = [];
                updateCartUI();
            } else {
                alert(data.message || 'Order could not be processed. Please try again.');
            }
        })    .catch(error => {
            console.error('Error:', error);
            nextBtn.disabled = false;
            nextBtn.textContent = originalBtnText;
            
            // Provide a more detailed error message
            if (error.message.includes('SyntaxError') || error.message.includes('Unexpected token')) {
                alert('Error parsing server response. Please check the browser console for details.');
            } else {
                alert('There was an error processing your order: ' + error.message);
            }
        });
    } catch (error) {
        console.error('Error:', error);
        nextBtn.disabled = false;
        nextBtn.textContent = originalBtnText;
        
        // Provide a more detailed error message
        if (error.message.includes('SyntaxError') || error.message.includes('Unexpected token')) {
            alert('Error parsing server response. Please check the browser console for details.');
        } else {
            alert('There was an error processing your order: ' + error.message);
        }
    }
}
