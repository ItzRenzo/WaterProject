<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

// Pagination logic
$orders_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $orders_per_page;

// Date filter logic
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
$date_sql = '';
if ($date_filter === 'today') {
    $date_sql = "AND DATE(t.TransactionDate) = CURDATE()";
} elseif ($date_filter === 'week') {
    $date_sql = "AND YEARWEEK(t.TransactionDate, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter === 'month') {
    $date_sql = "AND YEAR(t.TransactionDate) = YEAR(CURDATE()) AND MONTH(t.TransactionDate) = MONTH(CURDATE())";
} elseif ($date_filter === 'year') {
    $date_sql = "AND YEAR(t.TransactionDate) = YEAR(CURDATE())";
}

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$search_params = [];
if ($search !== '') {
    $search_sql = " AND (t.TransactionID LIKE ? OR c.CustomerName LIKE ? OR p.ProductName LIKE ?)";
    $search_params = ["%$search%", "%$search%", "%$search%"];
}

// Get total count for pagination with date filter and search
$count_sql = "SELECT COUNT(*) as total FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID JOIN Product p ON t.ProductID = p.ProductID WHERE t.Status = 'active' $date_sql $search_sql";
$count_stmt = $conn->prepare($count_sql);
if ($search !== '') {
    $count_stmt->bind_param(str_repeat('s', count($search_params)), ...$search_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_orders = 0;
if ($count_result && $row = $count_result->fetch_assoc()) {
    $total_orders = $row['total'];
}
$total_pages = ceil($total_orders / $orders_per_page);

// Retrieve paginated active transactions with date filter and search
$orders = [];
$sql = "SELECT t.TransactionID, c.CustomerName, p.ProductName, t.Price, t.Quantity, t.PaymentMethod, t.TransactionDate, t.Status FROM Transaction t JOIN Customer c ON t.CustomerID = c.CustomerID JOIN Product p ON t.ProductID = p.ProductID WHERE t.Status = 'active' $date_sql $search_sql ORDER BY t.TransactionDate DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($search !== '') {
    $types = str_repeat('s', count($search_params)) . 'ii';
    $params = array_merge($search_params, [$orders_per_page, $offset]);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $orders_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - RJane Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/order_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="Sidebar.html">
</head>

<body>
    <div id="navbar"></div>

    <script>
        fetch('Sidebar.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('navbar').innerHTML = data;
            });
    </script>

    <div class="main-content">
        <div class="orders-header">
            <h2>Order Management</h2>
        </div>

        <!-- Orders Table Section -->
        <div class="orders-table-container">
            <!-- Table, counts, and pagination will be loaded here via AJAX -->
        </div>
    </div>

    <script>
        function loadOrdersTable(params, pushState = true) {
            const tbody = document.querySelector('.orders-table-container tbody');
            const container = document.querySelector('.orders-table-container');
            // Show loading in tbody if table exists, else in container
            if (tbody) {
                // Use 9 columns for loading if checkboxes/actions are present
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px 0;">Loading...</td></tr>';
            } else {
                container.innerHTML = '<div style="text-align:center;padding:40px 0;">Loading...</div>';
            }
            const url = 'orders_table.php?' + params.toString();
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    // Parse the returned HTML and extract only the tbody and counts/pagination
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newTbody = tempDiv.querySelector('tbody');
                    const newCounts = tempDiv.querySelector('.orders-count');
                    const newPagination = tempDiv.querySelector('.pagination');
                    const newHeaderCheckbox = tempDiv.querySelector('#select-all-checkbox');
                    if (tbody && newTbody) {
                        tbody.replaceWith(newTbody);
                        // Update counts and pagination if present
                        const oldCounts = container.querySelector('.orders-count');
                        if (oldCounts && newCounts) oldCounts.replaceWith(newCounts);
                        const oldPagination = container.querySelector('.pagination');
                        if (oldPagination && newPagination) oldPagination.replaceWith(newPagination);
                        // Update thead checkbox state
                        const oldHeaderCheckbox = container.querySelector('#select-all-checkbox');
                        if (oldHeaderCheckbox && newHeaderCheckbox) {
                            oldHeaderCheckbox.replaceWith(newHeaderCheckbox);
                        }
                    } else {
                        // Fallback: replace the whole container if table not present
                        container.innerHTML = html;
                    }
                    if (pushState) {
                        history.pushState(null, '', '?' + params.toString());
                    }
                });
        }

        // On page load, load the table with current query params
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            loadOrdersTable(params, false);
        });

        // Date filter change
        document.addEventListener('change', function(e) {
            if (e.target && e.target.id === 'date-filter') {
                const params = new URLSearchParams(window.location.search);
                params.set('date', e.target.value);
                params.set('page', 1);
                const searchVal = document.getElementById('order-search')?.value || '';
                if (searchVal) params.set('search', searchVal);
                else params.delete('search');
                loadOrdersTable(params);
            }
        });

        // Live search
        let searchTimeout;
        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'order-search') {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const params = new URLSearchParams(window.location.search);
                    params.set('search', e.target.value);
                    params.set('page', 1);
                    params.set('date', document.getElementById('date-filter')?.value || 'all');
                    loadOrdersTable(params);
                }, 400);
            }
        });

        // Pagination click (event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('pagination-btn')) {
                e.preventDefault();
                const url = new URL(e.target.href);
                const params = new URLSearchParams(url.search);
                loadOrdersTable(params);
            }
        });

        // --- Bulk select and delete for admin orders table ---
        document.addEventListener('click', function(e) {
            // Select All button (selects all checkboxes on current page)
            if (e.target && e.target.id === 'select-all-orders') {
                document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = true);
                const headerCb = document.getElementById('select-all-checkbox');
                if (headerCb) headerCb.checked = true;
            }
            // Header checkbox (toggle all on current page)
            if (e.target && e.target.id === 'select-all-checkbox') {
                const checked = e.target.checked;
                document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = checked);
            }
            // Delete Selected button
            if (e.target && e.target.id === 'selected-delete') {
                const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
                if (selected.length === 0) {
                    alert('No orders selected.');
                    return;
                }
                if (!confirm('Are you sure you want to delete the selected orders?')) return;
                fetch('bulk_delete_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: selected })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Reload table (simulate filter/search state)
                        const params = new URLSearchParams(window.location.search);
                        loadOrdersTable(params, false);
                    } else {
                        alert('Failed to delete selected orders.');
                    }
                });
            }
            // Single row delete button
            if (e.target && (e.target.classList.contains('delete-order-btn') || (e.target.closest && e.target.closest('.delete-order-btn')))) {
                const btn = e.target.classList.contains('delete-order-btn') ? e.target : e.target.closest('.delete-order-btn');
                const orderId = btn.getAttribute('data-id');
                if (!orderId) return;
                if (!confirm('Are you sure you want to delete this order?')) return;
                fetch('bulk_delete_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: [orderId] })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const params = new URLSearchParams(window.location.search);
                        loadOrdersTable(params, false);
                    } else {
                        alert('Failed to delete order.');
                    }
                });
            }
            // Single row edit button
            if (e.target && (e.target.classList.contains('edit-order-btn') || (e.target.closest && e.target.closest('.edit-order-btn')))) {
                const btn = e.target.classList.contains('edit-order-btn') ? e.target : e.target.closest('.edit-order-btn');
                const orderId = btn.getAttribute('data-id');
                if (!orderId) return;
                // Find the row and get current values
                const row = btn.closest('tr');
                if (!row) return;
                // Get current values from the row's tds
                const tds = row.querySelectorAll('td');
                const current = {
                    TransactionID: tds[1].textContent.trim(),
                    CustomerName: tds[2].textContent.trim(),
                    ProductName: tds[3].textContent.trim(),
                    Price: tds[4].textContent.replace('₱','').replace(/,/g,'').trim(),
                    Quantity: tds[5].textContent.trim(),
                    PaymentMethod: tds[6].textContent.trim(),
                    TransactionDate: tds[7].textContent.trim()
                };                // Create a modal form
                let modal = document.getElementById('edit-order-modal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'edit-order-modal';
                    document.body.appendChild(modal);
                }
                
                modal.innerHTML = `
                    <div id="edit-order-form" class="modal-content">
                        <div class="modal-header">
                            <h3>Edit Order #${current.TransactionID}</h3>
                            <button type="button" class="close-btn" id="cancel-edit-order">&times;</button>
                        </div>
                        <form>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="CustomerName">Customer Name</label>
                                    <input type="text" id="CustomerName" name="CustomerName" value="${current.CustomerName}" required>
                                </div>
                                <div class="form-group">
                                    <label for="ProductName">Product Name</label>
                                    <input type="text" id="ProductName" name="ProductName" value="${current.ProductName}" required>
                                </div>                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="Price">Price</label>
                                        <input type="number" id="Price" name="Price" min="0" step="0.01" value="${current.Price}" readonly class="readonly-field">
                                        <small class="form-hint">Price is calculated automatically</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="Quantity">Quantity</label>
                                        <input type="number" id="Quantity" name="Quantity" min="1" value="${current.Quantity}" required>
                                        <div id="stock-info" style="color:#888;font-size:0.9em;margin-top:2px;"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="PaymentMethod">Payment Method</label>
                                    <input type="text" id="PaymentMethod" name="PaymentMethod" value="${current.PaymentMethod}" required>
                                </div>
                                <div class="form-group">
                                    <label for="TransactionDate">Transaction Date</label>
                                    <input type="datetime-local" id="TransactionDate" name="TransactionDate" value="${current.TransactionDate.replace(' ','T')}" required>
                                </div>
                            </div>                            <div class="modal-footer">
                                <button type="button" id="cancel-edit-order" class="cancel-btn">Cancel</button>
                                <button type="submit" class="submit-btn">Save Changes</button>
                            </div>
                        </form>
                    </div>
                `;
                  modal.classList.add('show');
                
                // Setup price calculation functionality
                const productNameInput = modal.querySelector('#ProductName');
                const quantityInput = modal.querySelector('#Quantity');
                const priceInput = modal.querySelector('#Price');
                  // Function to update price based on product and quantity
                function updatePrice() {
                    const productName = productNameInput.value.trim();
                    const quantity = parseInt(quantityInput.value) || 0;
                    
                    if (productName && quantity > 0) {
                        // Show loading indication (as text, not in the input)
                        const loadingHint = modal.querySelector('.form-hint');
                        if (loadingHint) {
                            loadingHint.textContent = "Loading price...";
                        }
                        // Temporarily set to 0 during loading - avoids parsing errors
                        priceInput.value = "0";
                        
                        // Fetch product price
                        fetch(`get_product_price.php?productName=${encodeURIComponent(productName)}`)
                            .then(res => {
                                // Check if response is ok before parsing JSON
                                if (!res.ok) {
                                    throw new Error(`HTTP error! Status: ${res.status}`);
                                }
                                return res.json();
                            })
                            .then(data => {
                                if (data.success && data.product) {
                                    // Calculate price
                                    const unitPrice = parseFloat(data.product.ProductPrice);
                                    const totalPrice = unitPrice * quantity;
                                    priceInput.value = totalPrice.toFixed(2);
                                    
                                    // Update hint with unit price
                                    if (loadingHint) {
                                        loadingHint.textContent = `Unit price: ₱${unitPrice.toFixed(2)} × ${quantity} units`;
                                    }
                                    
                                    // Also update stock info since we have the data
                                    const stockDiv = modal.querySelector('#stock-info');
                                    if (stockDiv && data.product.Stocks !== undefined) {
                                        stockDiv.textContent = `Available stock: ${data.product.Stocks}`;
                                        // Set max attribute for quantity input
                                        quantityInput.max = data.product.Stocks;
                                    }
                                } else {
                                    priceInput.value = "0";
                                    if (loadingHint) {
                                        loadingHint.textContent = "Price is calculated automatically (Product not found)";
                                    }
                                    console.error("Product not found or no price available");
                                }
                            })
                            .catch(error => {
                                console.error("Error fetching product price:", error);
                                priceInput.value = "0"; 
                                if (loadingHint) {
                                    loadingHint.textContent = "Price is calculated automatically (Error loading price)";
                                }
                            });
                    } else {
                        priceInput.value = "0";
                        const loadingHint = modal.querySelector('.form-hint');
                        if (loadingHint) {
                            loadingHint.textContent = "Price is calculated automatically";
                        }
                    }
                }
                
                // Function to update stock info based on product
                function updateStockInfo() {
                    const productName = productNameInput.value.trim();
                    if (productName) {
                        fetch(`get_product_price.php?productName=${encodeURIComponent(productName)}`)
                            .then(res => {
                                // Check if response is ok before parsing JSON
                                if (!res.ok) {
                                    throw new Error(`HTTP error! Status: ${res.status}`);
                                }
                                return res.json();
                            })
                            .then(data => {
                                if (data.success && data.product) {
                                    const stockDiv = modal.querySelector('#stock-info');
                                    if (stockDiv) {
                                        const stocks = data.product.Stocks !== undefined ? data.product.Stocks : 0;
                                        stockDiv.textContent = `Available stock: ${stocks}`;
                                        // Set max attribute for quantity input
                                        quantityInput.max = stocks;
                                    }
                                } else {
                                    const stockDiv = modal.querySelector('#stock-info');
                                    if (stockDiv) {
                                        stockDiv.textContent = 'Product not found';
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching product info:', error);
                                const stockDiv = modal.querySelector('#stock-info');
                                if (stockDiv) {
                                    stockDiv.textContent = 'Error loading stock information';
                                }
                            });
                    }
                }
                productNameInput.addEventListener('change', updateStockInfo);
                quantityInput.addEventListener('focus', updateStockInfo);
                // Initial stock info
                updateStockInfo();

                // Prevent entering quantity greater than stock
                quantityInput.addEventListener('input', function() {
                    const max = parseInt(quantityInput.max);
                    if (max && parseInt(quantityInput.value) > max) {
                        quantityInput.value = max;
                    }
                });
                
                // Event listeners for product and quantity changes
                productNameInput.addEventListener('change', updatePrice);
                quantityInput.addEventListener('input', updatePrice);
                
                // Initial price calculation when form opens
                updatePrice();
                
                // Close buttons - both the x button and the cancel button
                const closeButtons = modal.querySelectorAll('#cancel-edit-order, .close-btn');
                closeButtons.forEach(btn => {
                    btn.onclick = function() {
                        modal.classList.remove('show');
                    };
                });
                  // Submit handler
                modal.querySelector('form').onsubmit = function(ev) {
                    ev.preventDefault();
                    
                    // Show loading indicator in the submit button
                    const submitBtn = ev.target.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';
                    
                    const formData = Object.fromEntries(new FormData(ev.target).entries());
                    formData.TransactionID = orderId;
                      fetch('edit_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    })
                    .then(res => {
                        // Check if response is ok before parsing JSON
                        if (!res.ok) {
                            throw new Error(`HTTP error! Status: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Show success message 
                            const successMsg = document.createElement('div');
                            successMsg.className = 'alert alert-success';
                            successMsg.textContent = 'Order updated successfully!';
                            document.body.appendChild(successMsg);
                            
                            // Close the modal
                            modal.classList.remove('show');
                            
                            // Reload the orders table
                            const params = new URLSearchParams(window.location.search);
                            loadOrdersTable(params, false);
                            
                            // Remove success message after 3 seconds
                            setTimeout(() => {
                                successMsg.style.opacity = '0';
                                successMsg.style.transition = 'opacity 0.5s';
                                setTimeout(() => successMsg.remove(), 500);
                            }, 3000);
                        } else {
                            // Show error message
                            let errorMsg = 'Failed to update order.';
                            if (data.error) {
                                errorMsg += ' ' + data.error;
                            }
                            // If the error is about stock, show a special alert
                            if (data.error && data.error.includes('Not enough stock')) {
                                let stockMsg = data.stocks !== undefined ? `\nAvailable stock: ${data.stocks}` : '';
                                alert(errorMsg + stockMsg);
                            } else {
                                // Create an error notification
                                const errorNotice = document.createElement('div');
                                errorNotice.className = 'alert alert-error';
                                errorNotice.textContent = errorMsg;
                                document.body.appendChild(errorNotice);
                                setTimeout(() => {
                                    errorNotice.style.opacity = '0';
                                    errorNotice.style.transition = 'opacity 0.5s';
                                    setTimeout(() => errorNotice.remove(), 500);
                                }, 3000);
                            }
                            // Reset button
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Create an error notification
                        const errorNotice = document.createElement('div');
                        errorNotice.className = 'alert alert-error';
                        errorNotice.textContent = 'An error occurred while processing your request. Please try again.';
                        document.body.appendChild(errorNotice);
                        
                        // Remove error message after 3 seconds
                        setTimeout(() => {
                            errorNotice.style.opacity = '0';
                            errorNotice.style.transition = 'opacity 0.5s';
                            setTimeout(() => errorNotice.remove(), 500);
                        }, 3000);
                        
                        // Reset button
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalBtnText;
                    });
                };
                };
            }
        );


        // Keep header checkbox in sync with row checkboxes
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('order-checkbox')) {
                const all = document.querySelectorAll('.order-checkbox');
                const checked = document.querySelectorAll('.order-checkbox:checked');
                const headerCb = document.getElementById('select-all-checkbox');
                if (headerCb) headerCb.checked = all.length === checked.length && all.length > 0;
            }
        });

        // Handle browser back/forward navigation
        window.addEventListener('popstate', function() {
            const params = new URLSearchParams(window.location.search);
            loadOrdersTable(params, false);
        });
    </script>
</body>

</html>