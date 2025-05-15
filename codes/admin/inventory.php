<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';

// Retrieve products from database
$productQuery = "SELECT p.*, c.ContainerType FROM Product p 
                LEFT JOIN Container c ON p.ContainerID = c.ContainerID 
                ORDER BY p.ProductID DESC";
$productResult = $conn->query($productQuery);

// Retrieve containers from database
$containerQuery = "SELECT * FROM Container ORDER BY ContainerID DESC";
$containerResult = $conn->query($containerQuery);

// Count total products
$totalProducts = $productResult->num_rows;

// Count low stock items (products with stock less than 10)
$lowStockQuery = "SELECT COUNT(*) as low_stock_count FROM Product WHERE Stocks < 10";
$lowStockResult = $conn->query($lowStockQuery);
$lowStockCount = $lowStockResult->fetch_assoc()['low_stock_count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - RJane Water Refilling Station</title>    <link rel="stylesheet" href="../../Css/inventory.css">
    <link rel="stylesheet" href="../../Css/stock-info.css">
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
        <!-- Inventory Header -->
        <div class="inventory-header">
            <h2>Inventory Management</h2>
            <div class="header-actions">
                <button class="add-product-btn">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>
        </div>

        <!-- Inventory Stats - Fixed Structure -->
        <div class="inventory-stats">
            <div class="stat-card">
                <div class="stat-icon products">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Products</h3>
                    <p><?php echo $totalProducts; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon low-stock">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3>Low Stock Items</h3>
                    <p><?php echo $lowStockCount; ?></p>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="inventory-section">
            <div class="section-header">
                <h3>Products</h3>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search products...">
                </div>
            </div>

            <div class="products-grid">
                <?php while ($product = $productResult->fetch_assoc()) { ?>
                <div class="product-card">
                    <div class="product-header">
                        <div class="product-name"><?php echo $product['ProductName']; ?></div>
                        <span class="stock-badge <?php echo $product['Stocks'] < 10 ? 'low-stock' : 'in-stock'; ?>">
                            <?php echo $product['Stocks'] < 10 ? 'Low Stock' : 'In Stock'; ?>
                        </span>
                    </div>
                    <div class="product-details">
                        <div class="detail-item">
                            <span>Price:</span>
                            <span>₱<?php echo number_format($product['ProductPrice'], 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <span>Available:</span>
                            <span><?php echo $product['Stocks']; ?> pcs</span>
                        </div>
                        <div class="detail-item" data-container="<?php echo htmlspecialchars($product['ContainerType'] ?? ''); ?>">
                            <span>Container Type:</span>
                            <span><?php echo htmlspecialchars($product['ContainerType'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span>Status:</span>
                            <span class="status <?php echo $product['ProductStatus'] == 'Available' ? 'active' : 'inactive'; ?>">
                                <?php echo ucfirst($product['ProductStatus']); ?>
                            </span>
                        </div>
                        <?php if (!empty($product['ProductDescription'])): ?>
                        <div class="detail-item">
                            <span>Description:</span>
                            <span class="description"><?php echo htmlspecialchars($product['ProductDescription']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <button class="edit-btn"><i class="fas fa-edit"></i></button>
                        <button class="delete-btn"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Containers Section -->
        <div class="inventory-section">
            <div class="section-header">
                <h3>Containers</h3>
                <div class="header-actions">
                    <button class="add-container-btn">
                        <i class="fas fa-plus"></i> Add Container
                    </button>
                </div>
            </div>

            <div class="containers-grid">
                <?php while ($container = $containerResult->fetch_assoc()) { ?>
                <div class="product-card container-card">
                    <div class="product-header">
                        <div class="product-name"><?php echo $container['ContainerType']; ?></div>
                        <span class="stock-badge <?php echo $container['Stocks'] < 20 ? 'low-stock' : 'in-stock'; ?>">
                            <?php echo $container['Stocks'] < 20 ? 'Low Stock' : 'In Stock'; ?>
                        </span>
                    </div>
                    <div class="product-details">
                        <div class="detail-item">
                            <span>Capacity:</span>
                            <span><?php echo $container['ContainerCapacity(L)']; ?> L</span>
                        </div>
                        <div class="detail-item">
                            <span>Available:</span>
                            <span class="stock-value editable-stock" data-id="container-<?php echo $container['ContainerID']; ?>">
                                <?php echo $container['Stocks']; ?> pcs
                            </span>
                        </div>
                        <div class="detail-item">
                            <span>Status:</span>
                            <span class="status <?php echo $container['ContainerStatus'] == 'Available' ? 'active' : 'inactive'; ?>">
                                <?php echo ucfirst($container['ContainerStatus']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="product-actions">
                        <button class="update-stock-btn" data-id="container-<?php echo $container['ContainerID']; ?>"><i class="fas fa-sync-alt"></i></button>
                        <button class="edit-btn"><i class="fas fa-edit"></i></button>
                        <button class="delete-btn"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal" id="addProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Product</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <div class="form-group">
                        <label for="productName">Product Name</label>
                        <input type="text" id="productName" required>
                    </div>
                    <div class="form-group">
                        <label for="productPrice">Price</label>
                        <input type="number" id="productPrice" required>
                    </div>                    <div class="form-group">
                        <label for="productContainer">Container Type</label>
                        <select id="productContainer">
                            <option value="">Select Container</option>
                            <?php
                            // Re-query containers for the dropdown to avoid empty result set
                            $containerDropdownQuery = "SELECT * FROM Container ORDER BY ContainerID DESC";
                            $containerDropdownResult = $conn->query($containerDropdownQuery);
                            while ($container = $containerDropdownResult->fetch_assoc()) { ?>
                                <option value="<?php echo $container['ContainerID']; ?>" data-stocks="<?php echo $container['Stocks']; ?>"><?php echo $container['ContainerType']; ?></option>
                            <?php } ?>
                        </select>
                        <div id="addProductContainerStocksInfo" class="stock-info"></div>
                    </div>
                    <div class="form-group">
                        <label for="productDescription">Description</label>
                        <textarea id="productDescription"></textarea>
                    </div>                    <div class="form-group">
                        <label for="productStatus">Status</label>
                        <select id="productStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>                    <div class="form-group">
                        <label for="initialStocks">Initial Stock</label>
                        <input type="number" id="initialStocks" value="0" min="0">
                        <small>This will subtract from container stocks</small>
                        <div id="initialStockWarning" class="stock-info" style="display: none; color: var(--danger);">
                            Warning: Requested stock exceeds available container stock
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Include the Edit Product Modal -->
    <?php include 'edit_product_modal.php'; ?>

    <!-- Edit Container Modal (duplicate of Add Container Modal) -->
    <div class="modal" id="editContainerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Container</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editContainerForm">
                    <input type="hidden" id="editContainerId">
                    <div class="form-group">
                        <label for="editContainerName">Container Type</label>
                        <input type="text" id="editContainerName" required>
                    </div>
                    <div class="form-group">
                        <label for="editContainerCapacity">Container Capacity (L)</label>
                        <input type="number" id="editContainerCapacity" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="editContainerStatus">Status</label>
                        <select id="editContainerStatus">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Global notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas ${type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                        'fa-info-circle'}"></i>
            </div>
            <div class="notification-message">${message}</div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Modal functionality for inventory management
        document.addEventListener('DOMContentLoaded', function () {
            // Add product modal elements
            const addProductModal = document.getElementById('addProductModal');
            const addProductBtn = document.querySelector('.add-product-btn');
            const addProductCloseBtn = addProductModal.querySelector('.close-btn');
            const addProductCancelBtn = addProductModal.querySelector('.cancel-btn');
            const productForm = document.getElementById('productForm');
            const productsGrid = document.querySelector('.products-grid');
            const totalProductsCount = document.querySelector('.stat-card:first-child .stat-info p');            const productContainer = document.getElementById('productContainer');
            const addProductContainerStocksInfo = document.getElementById('addProductContainerStocksInfo');
            const initialStocks = document.getElementById('initialStocks');
            const initialStockWarning = document.getElementById('initialStockWarning');            // Edit product modal elements
            const editProductModal = document.getElementById('editProductModal');
            const editProductCloseBtn = editProductModal.querySelector('.close-btn');
            const editProductCancelBtn = editProductModal.querySelector('.cancel-btn');
            const editProductForm = document.getElementById('editProductForm');
            const editProductContainer = document.getElementById('editProductContainer');
            const containerStocksInfo = document.getElementById('containerStocksInfo');
            const addStocksInput = document.getElementById('addStocks');
            const stockWarning = document.getElementById('stockWarning');// Container stock info update for Add Product modal
            productContainer.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const availableStocks = parseInt(selectedOption.getAttribute('data-stocks'));
                    addProductContainerStocksInfo.textContent = `Available container stocks: ${availableStocks} pcs`;
                    
                    if (availableStocks < 10) {
                        addProductContainerStocksInfo.className = 'stock-info low';
                    } else {
                        addProductContainerStocksInfo.className = 'stock-info good';
                    }
                    
                    // Validate current initial stock input
                    validateInitialStockInput();
                } else {
                    addProductContainerStocksInfo.textContent = '';
                    addProductContainerStocksInfo.className = 'stock-info';
                }
            });
            
            // Validation for initial stock input in Add Product form
            function validateInitialStockInput() {
                const selectedOption = productContainer.options[productContainer.selectedIndex];
                if (selectedOption.value) {
                    const availableStocks = parseInt(selectedOption.getAttribute('data-stocks'));
                    const requestedStocks = parseInt(initialStocks.value) || 0;
                    
                    if (requestedStocks > availableStocks) {
                        initialStockWarning.style.display = 'block';
                        return false;
                    } else {
                        initialStockWarning.style.display = 'none';
                        return true;
                    }
                }
                return true;
            }
            
            initialStocks.addEventListener('input', validateInitialStockInput);

            // Counter for new products (for generating unique IDs)
            let productCounter = 5; // Starting after the initial 4 products

            // Open Add Product modal
            addProductBtn.addEventListener('click', function () {
                productForm.reset();
                addProductModal.style.display = 'flex';
                setTimeout(() => {
                    addProductModal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            });

            // Close Add Product modal
            function closeAddProductModal() {
                addProductModal.classList.remove('show');
                setTimeout(() => {
                    addProductModal.style.display = 'none';
                    productForm.reset();
                }, 300);
                document.body.style.overflow = '';
            }

            // Close Edit Product modal
            function closeEditProductModal() {
                editProductModal.classList.remove('show');
                setTimeout(() => {
                    editProductModal.style.display = 'none';
                    editProductForm.reset();
                }, 300);
                document.body.style.overflow = '';
            }

            // Add event listeners for add product modal close/cancel buttons
            addProductCloseBtn.addEventListener('click', closeAddProductModal);
            addProductCancelBtn.addEventListener('click', closeAddProductModal);

            // Add event listeners for edit product modal close/cancel buttons
            editProductCloseBtn.addEventListener('click', closeEditProductModal);
            editProductCancelBtn.addEventListener('click', closeEditProductModal);

            // Close modals when clicking outside
            window.addEventListener('click', function (event) {
                if (event.target === addProductModal) {
                    closeAddProductModal();
                }
                if (event.target === editProductModal) {
                    closeEditProductModal();
                }
            });            // Handle Add Product form submission
            productForm.addEventListener('submit', function (event) {
                event.preventDefault();
                
                // Check stock validation first
                if (!validateInitialStockInput()) {
                    showNotification('Cannot add more initial stocks than available in container!', 'error');
                    return;
                }
                
                // Get form values
                const name = document.getElementById('productName').value;
                const price = document.getElementById('productPrice').value;
                const description = document.getElementById('productDescription').value;
                const status = document.getElementById('productStatus').value;
                const containerId = document.getElementById('productContainer').value;
                const initialStocks = document.getElementById('initialStocks').value;

                // AJAX to PHP
                const formData = new FormData();
                formData.append('productName', name);
                formData.append('productPrice', price);
                formData.append('productDescription', description);
                formData.append('productStatus', status);
                formData.append('productContainer', containerId);
                formData.append('initialStocks', initialStocks);

                fetch('../../codes/Controllers/add_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())                .then(result => {
                    if (result.trim() === 'success') {
                        showNotification('Product added successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else if (result.trim() === 'not_enough_container_stocks') {
                        showNotification('Not enough container stocks to add the product!', 'error');
                    } else {
                        showNotification('Failed to add product.', 'error');
                    }
                })
                .catch(() => showNotification('Server error.', 'error'));
            });        // Function to edit a product (opens edit modal with product details)
            function editProduct(productCard) {
                // Get product details
                const name = productCard.querySelector('.product-name').textContent;
                const price = productCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent.replace('₱', '');
                const status = productCard.querySelector('.status').classList.contains('active') ? 'active' : 'inactive';
                const description = productCard.querySelector('.description')?.textContent || '';
                // Get container type
                const containerType = productCard.querySelector('.detail-item[data-container]')?.getAttribute('data-container') || '';
                
                // Set form values in edit modal
                document.getElementById('editProductOldName').value = name;
                document.getElementById('editProductName').value = name;
                document.getElementById('editProductPrice').value = parseFloat(price);
                document.getElementById('editProductDescription').value = description;
                document.getElementById('editProductStatus').value = status;
                document.getElementById('editContainerType').value = containerType;
                document.getElementById('addStocks').value = 0;
                
                // Set the container in dropdown
                const productContainerSelect = document.getElementById('editProductContainer');
                let found = false;
                for (let i = 0; i < productContainerSelect.options.length; i++) {
                    if (productContainerSelect.options[i].textContent.trim() === containerType) {
                        productContainerSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (!found) productContainerSelect.selectedIndex = 0;
                
                // Trigger the change event to update container stock info
                const changeEvent = new Event('change');
                productContainerSelect.dispatchEvent(changeEvent);
                
                // Open edit modal
                editProductModal.style.display = 'flex';
                setTimeout(() => {
                    editProductModal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            }            // Handle Edit Product form submission
            editProductForm.addEventListener('submit', function (event) {
                event.preventDefault();
                
                // Check stock validation first
                if (!validateStockInput()) {
                    showNotification('Cannot add more stocks than available in container!', 'error');
                    return;
                }
                
                // Gather form values
                const newName = document.getElementById('editProductName').value;
                const newPrice = document.getElementById('editProductPrice').value;
                const newDescription = document.getElementById('editProductDescription').value;
                const newStatus = document.getElementById('editProductStatus').value;
                const newContainerId = document.getElementById('editProductContainer').value;
                const oldName = document.getElementById('editProductOldName').value;
                const addStocks = parseInt(document.getElementById('addStocks').value);
                
                // AJAX to update product info
                const formData = new FormData();
                formData.append('productName', newName);
                formData.append('productPrice', newPrice);
                formData.append('productDescription', newDescription);
                formData.append('productStatus', newStatus);
                formData.append('productContainer', newContainerId);
                formData.append('oldProductName', oldName);
                formData.append('addStocks', addStocks);
                
                fetch('../../codes/Controllers/update_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result.trim() === 'success') {
                        showNotification('Product updated successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else if (result.trim() === 'not_enough_container_stocks') {
                        showNotification('Not enough container stocks to add the requested product stocks.', 'error');
                    } else {
                        showNotification('Failed to update product.', 'error');
                    }
                })
                .catch(() => showNotification('Server error.', 'error'));
            });

            // Add event listeners to all edit buttons
            document.querySelectorAll('.product-card:not(.container-card) .edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const productCard = this.closest('.product-card');
                    editProduct(productCard);
                });
            });

            // Validation for add stock input in Edit Product form
            function validateStockInput() {
                const selectedOption = editProductContainer.options[editProductContainer.selectedIndex];
                if (selectedOption.value) {
                    const availableStocks = parseInt(selectedOption.getAttribute('data-stocks'));
                    const requestedStocks = parseInt(addStocksInput.value) || 0;
                    
                    if (requestedStocks > availableStocks) {
                        stockWarning.style.display = 'block';
                        return false;
                    } else {
                        stockWarning.style.display = 'none';
                        return true;
                    }
                }
                return true;
            }

            // Container stock info update for Edit Product modal
            editProductContainer.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const availableStocks = parseInt(selectedOption.getAttribute('data-stocks'));
                    containerStocksInfo.textContent = `Available container stocks: ${availableStocks} pcs`;
                    
                    if (availableStocks < 10) {
                        containerStocksInfo.className = 'stock-info low';
                    } else {
                        containerStocksInfo.className = 'stock-info good';
                    }
                    
                    // Validate current add stock input
                    validateStockInput();
                } else {
                    containerStocksInfo.textContent = '';
                    containerStocksInfo.className = 'stock-info';
                }
            });
            addStocksInput.addEventListener('input', validateStockInput);
        });

        // Add Container Modal Functionality
        document.addEventListener('DOMContentLoaded', function () {
            // Get DOM elements for Add Container functionality
            const addContainerBtn = document.querySelector('.add-container-btn');
            const containersGrid = document.querySelector('.containers-grid');
            const totalContainersCount = document.querySelector('.stat-card:nth-child(2) .stat-info p');

            // Create the modal if it doesn't exist
            let containerModal = document.createElement('div');
            containerModal.id = 'addContainerModal';
            containerModal.className = 'modal';
            containerModal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-cube"></i> Add New Container</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="containerForm">
                        <div class="form-group">
                            <label for="containerName">Container Type</label>
                            <input type="text" id="containerName" placeholder="Enter container type" required>
                        </div>
                        <div class="form-group">
                            <label for="containerCapacity">Container Capacity (L)</label>
                            <input type="number" id="containerCapacity" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label for="containerStatus">Status</label>
                            <select id="containerStatus">
                                <option value="Available">Available</option>
                                <option value="Unavailable">Unavailable</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
                            <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Container</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
            document.body.appendChild(containerModal);

            // Counter for new containers (for generating unique IDs)
            let containerCounter = 3; // Starting after the initial 2 containers

            // Get modal elements
            const closeBtn = containerModal.querySelector('.close-btn');
            const cancelBtn = containerModal.querySelector('.cancel-btn');
            const containerForm = document.getElementById('containerForm');

            // Open container modal
            addContainerBtn.addEventListener('click', function () {
                containerModal.style.display = 'flex';
                setTimeout(() => {
                    containerModal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            });

            // Close container modal function
            function closeContainerModal() {
                containerModal.classList.remove('show');
                setTimeout(() => {
                    containerModal.style.display = 'none';
                    containerForm.reset(); // Reset form fields
                }, 300);
                document.body.style.overflow = ''; // Restore scrolling
            }

            // Close modal when clicking close button
            closeBtn.addEventListener('click', closeContainerModal);

            // Close modal when clicking cancel button
            cancelBtn.addEventListener('click', closeContainerModal);

            // Close modal when clicking outside the modal content
            window.addEventListener('click', function (event) {
                if (event.target === containerModal) {
                    closeContainerModal();
                }
            });

            // Handle container form submission
            containerForm.addEventListener('submit', function (event) {
                event.preventDefault();

                // Get form values
                const type = document.getElementById('containerName').value;
                const capacity = document.getElementById('containerCapacity').value;
                const status = document.getElementById('containerStatus').value;

                // AJAX to PHP
                const formData = new FormData();
                formData.append('containerName', type);
                formData.append('containerCapacity', capacity);
                formData.append('containerStatus', status);

                fetch('../../codes/Controllers/add_container.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result.trim() === 'success') {
                        showNotification('Container added successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification('Failed to add container.', 'error');
                    }
                })
                .catch(() => showNotification('Server error.', 'error'));
            });

            // Updated edit container function without circulation
            function editContainer(containerCard) {
                // Get container details
                const name = containerCard.querySelector('.product-name').textContent;
                const depositFee = containerCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent.replace('₱', '');
                const availableStock = containerCard.querySelector('.detail-item:nth-child(2) span:last-child').textContent.split(' ')[0];
                const status = containerCard.querySelector('.status').classList.contains('active') ? 'active' : 'inactive';
                const description = containerCard.querySelector('.description')?.textContent || '';

                // Set form values
                document.getElementById('containerName').value = name;
                document.getElementById('depositFee').value = parseFloat(depositFee);
                document.getElementById('availableStock').value = parseInt(availableStock);
                document.getElementById('containerStatus').value = status;
                document.getElementById('containerDescription').value = description;

                // Change modal header to Edit Container
                document.querySelector('#addContainerModal .modal-header h3').textContent = 'Edit Container';

                // Update form submit to edit container instead of adding new one
                const originalSubmitHandler = containerForm.onsubmit;
                containerForm.onsubmit = function (event) {
                    event.preventDefault();

                    // Get updated values
                    const newName = document.getElementById('containerName').value;
                    const newDepositFee = document.getElementById('depositFee').value;
                    const newAvailableStock = document.getElementById('availableStock').value;
                    const newStatus = document.getElementById('containerStatus').value;
                    const newDescription = document.getElementById('containerDescription').value;

                    // Update container details (removing circulation update)
                    containerCard.querySelector('.product-name').textContent = newName;
                    containerCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent = '₱' + parseFloat(newDepositFee).toFixed(2);
                    containerCard.querySelector('.detail-item:nth-child(2) span:last-child').textContent = newAvailableStock + ' pcs';

                    const statusElement = containerCard.querySelector('.status');
                    statusElement.textContent = newStatus === 'active' ? 'Active' : 'Inactive';
                    statusElement.className = `status ${newStatus}`;

                    // Stock badge update code remains the same

                    // Description update code remains the same

                    // Show success message and close modal
                    showNotification('Container updated successfully!', 'success');
                    closeContainerModal();
                    containerForm.onsubmit = originalSubmitHandler;

                    // Restore modal header
                    document.querySelector('#addContainerModal .modal-header h3').textContent = 'Add New Container';
                };

                // Open modal
                containerModal.style.display = 'flex';
                setTimeout(() => {
                    containerModal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            }

            // Updated function to create container cards without circulation
            function createContainerCard(name, depositFee, availableStock, status, description) {
                const containerId = 'container-' + containerCounter++;
                const containerCard = document.createElement('div');
                containerCard.className = 'product-card container-card';

                // Add stock badge class based on available amount
                let stockBadgeClass = 'in-stock';
                let stockBadgeText = 'In Stock';

                if (parseInt(availableStock) < 20) {
                    stockBadgeClass = 'low-stock';
                    stockBadgeText = 'Low Stock';
                }

                const isActive = status === 'active';

                containerCard.innerHTML = `
                <div class="product-header">
                    <div class="product-name">${name}</div>
                    <span class="stock-badge ${stockBadgeClass}">${stockBadgeText}</span>
                </div>
                <div class="product-details">
                    <div class="detail-item">
                        <span>Deposit Fee:</span>
                        <span>₱${parseFloat(depositFee).toFixed(2)}</span>
                    </div>
                    <div class="detail-item">
                        <span>Available:</span>
                        <span class="stock-value editable-stock" data-id="${containerId}">${availableStock} pcs</span>
                    </div>
                    <div class="detail-item">
                        <span>Status:</span>
                        <span class="status ${isActive ? 'active' : 'inactive'}">${isActive ? 'Active' : 'Inactive'}</span>
                    </div>
                    ${description ? `
                    <div class="detail-item">
                        <span>Description:</span>
                        <span class="description">${description}</span>
                    </div>` : ''}
                </div>
                <div class="product-actions">
                    <button class="update-stock-btn" data-id="${containerId}" title="Update Stock"><i class="fas fa-sync-alt"></i></button>
                    <button class="edit-btn" title="Edit Container"><i class="fas fa-edit"></i></button>
                    <button class="delete-btn" title="Delete Container"><i class="fas fa-trash"></i></button>
                </div>
            `;

                // Add event listeners to the buttons
                setTimeout(() => {
                    // Event listener code remains the same
                }, 0);

                return containerCard;
            }

            // Function to confirm deletion of a container
            function confirmDeleteContainer(containerCard) {
                if (confirm('Are you sure you want to delete this container?')) {
                    // Remove container card with animation
                    containerCard.classList.add('removing');
                    setTimeout(() => {
                        containersGrid.removeChild(containerCard);

                        // Check if it was a low stock item
                        const stockBadge = containerCard.querySelector('.stock-badge');
                        if (stockBadge.classList.contains('low-stock')) {
                            // Update low stock count
                            const lowStockCount = document.querySelector('.stat-card:nth-child(2) .stat-info p');
                            lowStockCount.textContent = Math.max(0, parseInt(lowStockCount.textContent) - 1);
                        }

                        // Show success message
                        showNotification('Container deleted successfully!', 'success');
                    }, 300);
                }
            }

            // Function to update container stock
            function updateContainerStock(containerId) {
                // Create update stock modal if it doesn't exist
                let updateStockModal = document.getElementById('updateStockModal');
                if (!updateStockModal) {
                    updateStockModal = document.createElement('div');
                    updateStockModal.id = 'updateStockModal';
                    updateStockModal.className = 'modal';
                    updateStockModal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Update Container Stock</h3>
                            <button class="close-btn">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="updateStockForm">
                                <input type="hidden" id="updateContainerId">
                                <div class="form-group">
                                    <label for="updateContainerType">Container Type</label>
                                    <input type="text" id="updateContainerType" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="updateQuantity">Stocks</label>
                                    <input type="number" id="updateQuantity" min="0" required>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="cancel-btn">Cancel</button>
                                    <button type="submit" class="save-btn">Update Stock</button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                    document.body.appendChild(updateStockModal);

                    // Add event listeners to the update stock modal
                    const updateStockCloseBtn = updateStockModal.querySelector('.close-btn');
                    const updateStockCancelBtn = updateStockModal.querySelector('.cancel-btn');
                    const updateStockForm = updateStockModal.querySelector('#updateStockForm');

                    // Close update stock modal
                    updateStockCloseBtn.addEventListener('click', function () {
                        closeUpdateStockModal();
                    });

                    updateStockCancelBtn.addEventListener('click', function () {
                        closeUpdateStockModal();
                    });

                    // Close on outside click
                    window.addEventListener('click', function (event) {
                        if (event.target === updateStockModal) {
                            closeUpdateStockModal();
                        }
                    });

                    // Handle update stock form submission
                    updateStockForm.addEventListener('submit', function (event) {
                        event.preventDefault();

                        const containerId = document.getElementById('updateContainerId').value.replace('container-', '');
                        const newStock = parseInt(document.getElementById('updateQuantity').value);

                        // AJAX to update stock and log to StockReports
                        const formData = new FormData();
                        formData.append('containerId', containerId);
                        formData.append('newStocks', newStock);

                        fetch('../../codes/Controllers/update_container_stock.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(result => {
                            if (result.trim() === 'success') {
                                showNotification('Container stock updated successfully!', 'success');
                                setTimeout(() => window.location.reload(), 1000);
                            } else if (result.trim() === 'invalid_stock') {
                                showNotification('Invalid stock amount!', 'error');
                            } else {
                                showNotification('Failed to update stock!', 'error');
                            }
                        })
                        .catch(() => showNotification('Server error occurred!', 'error'));

                        // Close the modal
                        closeUpdateStockModal();
                    });
                }

                function closeUpdateStockModal() {
                    updateStockModal.classList.remove('show');
                    setTimeout(() => {
                        updateStockModal.style.display = 'none';
                        document.getElementById('updateStockForm').reset();
                    }, 300);
                    document.body.style.overflow = '';
                }

                // Get current stock value and container type
                const stockElement = document.querySelector(`.stock-value[data-id="${containerId}"]`);
                const currentStock = stockElement.textContent.split(' ')[0];
                const containerCard = stockElement.closest('.container-card');
                const containerType = containerCard.querySelector('.product-name').textContent;
                // Set values in the update stock form
                document.getElementById('updateContainerId').value = containerId;
                document.getElementById('updateContainerType').value = containerType;
                document.getElementById('updateQuantity').value = currentStock;
                // Open the update stock modal
                updateStockModal.style.display = 'flex';
                setTimeout(() => {
                    updateStockModal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            }

            // Add event listeners to existing container edit, delete, and update stock buttons
            document.querySelectorAll('.container-card .edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const containerCard = this.closest('.container-card');
                    editContainer(containerCard);
                });
            });

            document.querySelectorAll('.container-card .delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const containerCard = this.closest('.container-card');
                    confirmDeleteContainer(containerCard);
                });
            });

            document.querySelectorAll('.container-card .update-stock-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const containerId = this.getAttribute('data-id');
                    updateContainerStock(containerId);
                });
            });
        });

        // Add CSS for price and stock inputs
        const style = document.createElement('style');
        style.textContent = `
        /* Add Container Modal Styles */
        .price-input, .stock-input {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .price-input .currency, .stock-input .unit {
            position: absolute;
            color: var(--text-light);
        }
        
        .price-input .currency {
            left: 12px;
        }
        
        .price-input input {
            padding-left: 25px;
        }
        
        .stock-input .unit {
            right: 12px;
        }
        
        .stock-input input {
            padding-right: 40px;
        }
        
        /* Update Stock Button */
        .update-stock-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 5px;
        }
        
        .update-stock-btn:hover {
            background: var(--success);
            color: white;
        }
        
        /* Container Card Styles */
        .container-card {
            border-left: 3px solid var(--primary);
        }
        
        .containers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
    `;
        document.head.appendChild(style);
    </script>
    <script src="../../js/inventory-manager.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // ...existing code...
        // Edit Container Modal elements
        const editContainerModal = document.getElementById('editContainerModal');
        const editContainerCloseBtn = editContainerModal.querySelector('.close-btn');
        const editContainerCancelBtn = editContainerModal.querySelector('.cancel-btn');
        const editContainerForm = document.getElementById('editContainerForm');
        const editContainerName = document.getElementById('editContainerName');
        const editContainerCapacity = document.getElementById('editContainerCapacity');
        const editContainerStatus = document.getElementById('editContainerStatus');
        const editContainerId = document.getElementById('editContainerId');

        // Open Edit Container Modal and populate fields
        document.querySelectorAll('.container-card .edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                const containerCard = this.closest('.container-card');
                // Get container details
                const name = containerCard.querySelector('.product-name').textContent;
                const capacity = containerCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent.replace(' L', '');
                const status = containerCard.querySelector('.status').classList.contains('active') ? 'Available' : 'Unavailable';
                // Optionally, get container ID if available
                const containerId = containerCard.querySelector('.stock-value').getAttribute('data-id').replace('container-', '');

                // Set values in modal
                editContainerName.value = name;
                editContainerCapacity.value = parseFloat(capacity);
                editContainerStatus.value = status;
                editContainerId.value = containerId;

                // Show modal
                editContainerModal.style.display = 'flex';
                setTimeout(() => {
                    editContainerModal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            });
        });

        // Close Edit Container Modal
        function closeEditContainerModal() {
            editContainerModal.classList.remove('show');
            setTimeout(() => {
                editContainerModal.style.display = 'none';
                editContainerForm.reset();
            }, 300);
            document.body.style.overflow = '';
        }
        editContainerCloseBtn.addEventListener('click', closeEditContainerModal);
        editContainerCancelBtn.addEventListener('click', closeEditContainerModal);
        window.addEventListener('click', function (event) {
            if (event.target === editContainerModal) {
                closeEditContainerModal();
            }
        });

        // Handle Edit Container form submission (AJAX to update_container.php or similar)
        editContainerForm.addEventListener('submit', function (event) {
            event.preventDefault();
            // Gather form values
            const id = editContainerId.value;
            const name = editContainerName.value;
            const capacity = editContainerCapacity.value;
            const status = editContainerStatus.value;
            // TODO: Implement AJAX to update container in DB
            // Example:
            /*
            const formData = new FormData();
            formData.append('containerId', id);
            formData.append('containerName', name);
            formData.append('containerCapacity', capacity);
            formData.append('containerStatus', status);
            fetch('../../codes/Controllers/update_container.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                if (result.trim() === 'success') {
                    showNotification('Container updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification('Failed to update container.', 'error');
                }
            })
            .catch(() => showNotification('Server error.', 'error'));
            */
            // For now, just close the modal
            closeEditContainerModal();
        });
        // ...existing code...
    });
    </script>
</body>

</html>