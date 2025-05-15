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
    <title>Inventory - RJane Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/inventory.css">
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

        <!-- Inventory Stats -->
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
                <?php 
                // Reset the result pointer to the beginning
                if($productResult && $productResult->num_rows > 0) {
                    $productResult->data_seek(0);
                    
                    while ($product = $productResult->fetch_assoc()) { 
                ?>
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
                            <span><?php echo $product['Stocks']; ?> L</span>
                        </div>
                        <div class="detail-item">
                            <span>Status:</span>
                            <span class="status <?php echo $product['ProductStatus'] == 'Available' ? 'active' : 'inactive'; ?>">
                                <?php echo ucfirst($product['ProductStatus']); ?>
                            </span>
                        </div>
                        <?php if (!empty($product['ProductDescription'])) { ?>
                        <div class="detail-item">
                            <span>Description:</span>
                            <span class="description"><?php echo $product['ProductDescription']; ?></span>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="product-actions">
                        <button class="edit-btn"><i class="fas fa-edit"></i></button>
                        <button class="delete-btn"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo '<div class="no-products">No products found</div>';
                }
                ?>
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
                <?php 
                // Reset the result pointer to the beginning
                if($containerResult && $containerResult->num_rows > 0) {
                    $containerResult->data_seek(0);
                    
                    while ($container = $containerResult->fetch_assoc()) { 
                ?>
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
                            <span><?php echo $container['ContainerCapacity']; ?> L</span>
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
                <?php 
                    }
                } else {
                    echo '<div class="no-containers">No containers found</div>';
                }
                ?>
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
                        <input type="number" id="productPrice" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="productDescription">Description</label>
                        <textarea id="productDescription"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="productStatus">Status</label>
                        <select id="productStatus">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality for inventory management
        document.addEventListener('DOMContentLoaded', function () {
            // Get DOM elements
            const modal = document.getElementById('addProductModal');
            const addProductBtn = document.querySelector('.add-product-btn');
            const closeBtn = modal.querySelector('.close-btn');
            const cancelBtn = modal.querySelector('.cancel-btn');
            const saveBtn = modal.querySelector('.save-btn');
            const productForm = document.getElementById('productForm');
            const productsGrid = document.querySelector('.products-grid');
            const totalProductsCount = document.querySelector('.stat-card:first-child .stat-info p');

            // Counter for new products (for generating unique IDs)
            let productCounter = <?php echo $totalProducts + 1; ?>; // Start after existing products

            // Open modal
            addProductBtn.addEventListener('click', function () {
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
            });

            // Close modal function
            function closeModal() {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    productForm.reset(); // Reset form fields
                }, 300);
                document.body.style.overflow = ''; // Restore scrolling
            }

            // Close modal when clicking close button
            closeBtn.addEventListener('click', closeModal);

            // Close modal when clicking cancel button
            cancelBtn.addEventListener('click', closeModal);

            // Close modal when clicking outside the modal content
            window.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            // Handle form submission
            productForm.addEventListener('submit', function (event) {
                event.preventDefault();

                // Get form values
                const name = document.getElementById('productName').value;
                const price = document.getElementById('productPrice').value;
                const description = document.getElementById('productDescription').value;
                const status = document.getElementById('productStatus').value;

                // Create a new product card
                const newProduct = createProductCard(name, price, description, status);

                // Add the new product to the grid
                productsGrid.insertBefore(newProduct, productsGrid.firstChild);

                // Update product count
                const currentCount = parseInt(totalProductsCount.textContent);
                totalProductsCount.textContent = currentCount + 1;

                // Show success message
                showNotification('Product added successfully!', 'success');

                // Close the modal
                closeModal();
            });

            // Function to create a new product card
            function createProductCard(name, price, description, status) {
                const productCard = document.createElement('div');
                productCard.className = 'product-card';
                productCard.id = 'product-' + productCounter++;

                // Generate random available amount for demonstration
                const available = Math.floor(Math.random() * 900) + 100; // 100-999 L
                const isActive = status === 'Available';

                // Add stock badge class based on available amount
                let stockBadgeClass = 'in-stock';
                let stockBadgeText = 'In Stock';

                if (available < 10) {
                    stockBadgeClass = 'low-stock';
                    stockBadgeText = 'Low Stock';
                }

                productCard.innerHTML = `
                <div class="product-header">
                    <div class="product-name">${name}</div>
                    <span class="stock-badge ${stockBadgeClass}">${stockBadgeText}</span>
                </div>
                <div class="product-details">
                    <div class="detail-item">
                        <span>Price:</span>
                        <span>₱${parseFloat(price).toFixed(2)}</span>
                    </div>
                    <div class="detail-item">
                        <span>Available:</span>
                        <span>${available} L</span>
                    </div>
                    <div class="detail-item">
                        <span>Status:</span>
                        <span class="status ${isActive ? 'active' : 'inactive'}">${status}</span>
                    </div>
                    ${description ? `
                    <div class="detail-item">
                        <span>Description:</span>
                        <span class="description">${description}</span>
                    </div>` : ''}
                </div>
                <div class="product-actions">
                    <button class="edit-btn"><i class="fas fa-edit"></i></button>
                    <button class="delete-btn"><i class="fas fa-trash"></i></button>
                </div>
            `;

                // Add event listeners to the new product card buttons
                setTimeout(() => {
                    const deleteBtn = productCard.querySelector('.delete-btn');
                    const editBtn = productCard.querySelector('.edit-btn');

                    deleteBtn.addEventListener('click', function () {
                        confirmDelete(productCard);
                    });

                    editBtn.addEventListener('click', function () {
                        editProduct(productCard);
                    });
                }, 0);

                return productCard;
            }

            // Function to show notification
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

                // Show notification
                setTimeout(() => {
                    notification.classList.add('show');
                }, 10);

                // Hide and remove notification after 3 seconds
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }

            // Function to confirm deletion of a product
            function confirmDelete(productCard) {
                if (confirm('Are you sure you want to delete this product?')) {
                    // Remove product card with animation
                    productCard.classList.add('removing');
                    setTimeout(() => {
                        productsGrid.removeChild(productCard);

                        // Update product count
                        const currentCount = parseInt(totalProductsCount.textContent);
                        totalProductsCount.textContent = currentCount - 1;

                        // Show success message
                        showNotification('Product deleted successfully!', 'success');
                    }, 300);
                }
            }

            // Function to edit a product
            function editProduct(productCard) {
                // Get product details
                const name = productCard.querySelector('.product-name').textContent;
                const price = productCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent.replace('₱', '');
                const status = productCard.querySelector('.status').classList.contains('active') ? 'Available' : 'Unavailable';
                const description = productCard.querySelector('.description')?.textContent || '';

                // Set form values
                document.getElementById('productName').value = name;
                document.getElementById('productPrice').value = parseFloat(price);
                document.getElementById('productDescription').value = description;
                document.getElementById('productStatus').value = status;

                // Update form submit to edit product instead of adding new one
                const originalSubmitHandler = productForm.onsubmit;
                productForm.onsubmit = function (event) {
                    event.preventDefault();

                    // Update product details
                    productCard.querySelector('.product-name').textContent = document.getElementById('productName').value;
                    productCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent = '₱' + parseFloat(document.getElementById('productPrice').value).toFixed(2);

                    const newStatus = document.getElementById('productStatus').value;
                    const statusElement = productCard.querySelector('.status');
                    statusElement.textContent = newStatus;
                    statusElement.className = `status ${newStatus === 'Available' ? 'active' : 'inactive'}`;

                    const newDescription = document.getElementById('productDescription').value;
                    let descriptionItem = productCard.querySelector('.detail-item:nth-child(4)');

                    if (newDescription) {
                        if (descriptionItem) {
                            descriptionItem.querySelector('.description').textContent = newDescription;
                        } else {
                            const newDescriptionItem = document.createElement('div');
                            newDescriptionItem.className = 'detail-item';
                            newDescriptionItem.innerHTML = `
                            <span>Description:</span>
                            <span class="description">${newDescription}</span>
                        `;
                            productCard.querySelector('.product-details').appendChild(newDescriptionItem);
                        }
                    } else if (descriptionItem) {
                        productCard.querySelector('.product-details').removeChild(descriptionItem);
                    }

                    // Show success message
                    showNotification('Product updated successfully!', 'success');

                    // Close the modal
                    closeModal();

                    // Restore original submit handler
                    productForm.onsubmit = originalSubmitHandler;
                };

                // Open modal
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            }

            // Add event listeners to all delete buttons
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const productCard = this.closest('.product-card');
                    confirmDelete(productCard);
                });
            });

            // Add event listeners to all edit buttons
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const productCard = this.closest('.product-card');
                    editProduct(productCard);
                });
            });
        });

        // Add Container Modal Functionality
        document.addEventListener('DOMContentLoaded', function () {
            // Get DOM elements for Add Container functionality
            const addContainerBtn = document.querySelector('.add-container-btn');
            const containersGrid = document.querySelector('.containers-grid');
            const lowStockCount = document.querySelector('.stat-card:nth-child(2) .stat-info p');

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
                            <label for="containerName">Container Name</label>
                            <input type="text" id="containerName" placeholder="Enter container name" required>
                        </div>
                        <div class="form-group">
                            <label for="containerCapacity">Capacity (in L)</label>
                            <div class="capacity-input">
                                <input type="number" id="containerCapacity" step="0.01" min="0" placeholder="0.00" required>
                                <span class="unit">L</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="availableStock">Available Stock</label>
                            <div class="stock-input">
                                <input type="number" id="availableStock" min="0" placeholder="0" required>
                                <span class="unit">pcs</span>
                            </div>
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
            let containerCounter = <?php echo $containerResult->num_rows + 1; ?>; // Start after existing containers

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
                const name = document.getElementById('containerName').value;
                const capacity = document.getElementById('containerCapacity').value;
                const availableStock = document.getElementById('availableStock').value;
                const status = document.getElementById('containerStatus').value;

                // Create a new container card
                const newContainer = createContainerCard(name, capacity, availableStock, status);

                // Add the new container to the grid
                containersGrid.insertBefore(newContainer, containersGrid.firstChild);

                // Update low stock count if needed
                if (parseInt(availableStock) < 20) {
                    lowStockCount.textContent = parseInt(lowStockCount.textContent) + 1;
                }

                // Show success message
                showNotification('Container added successfully!', 'success');

                // Close the modal
                closeContainerModal();
            });

            // Function to edit a container
            function editContainer(containerCard) {
                // Get container details
                const name = containerCard.querySelector('.product-name').textContent;
                const capacity = containerCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent.split(' ')[0];
                const availableStock = containerCard.querySelector('.detail-item:nth-child(2) span:last-child').textContent.split(' ')[0];
                const status = containerCard.querySelector('.status').classList.contains('active') ? 'Available' : 'Unavailable';

                // Set form values
                document.getElementById('containerName').value = name;
                document.getElementById('containerCapacity').value = parseFloat(capacity);
                document.getElementById('availableStock').value = parseInt(availableStock);
                document.getElementById('containerStatus').value = status;

                // Update form submit to edit container instead of adding new one
                const originalSubmitHandler = containerForm.onsubmit;
                containerForm.onsubmit = function (event) {
                    event.preventDefault();

                    // Get updated values
                    const newName = document.getElementById('containerName').value;
                    const newCapacity = document.getElementById('containerCapacity').value;
                    const newAvailableStock = document.getElementById('availableStock').value;
                    const newStatus = document.getElementById('containerStatus').value;

                    // Update container details
                    containerCard.querySelector('.product-name').textContent = newName;
                    containerCard.querySelector('.detail-item:nth-child(1) span:last-child').textContent = parseFloat(newCapacity).toFixed(2) + ' L';
                    containerCard.querySelector('.detail-item:nth-child(2) span:last-child').textContent = newAvailableStock + ' pcs';

                    const statusElement = containerCard.querySelector('.status');
                    statusElement.textContent = newStatus;
                    statusElement.className = `status ${newStatus === 'Available' ? 'active' : 'inactive'}`;

                    // Update stock badge
                    const stockBadge = containerCard.querySelector('.stock-badge');
                    const wasLowStock = stockBadge.classList.contains('low-stock');
                    const isNowLowStock = parseInt(newAvailableStock) < 20;

                    if (isNowLowStock) {
                        stockBadge.className = 'stock-badge low-stock';
                        stockBadge.textContent = 'Low Stock';

                        // Update low stock count if it wasn't low stock before
                        if (!wasLowStock) {
                            lowStockCount.textContent = parseInt(lowStockCount.textContent) + 1;
                        }
                    } else {
                        stockBadge.className = 'stock-badge in-stock';
                        stockBadge.textContent = 'In Stock';

                        // Update low stock count if it was low stock before
                        if (wasLowStock) {
                            lowStockCount.textContent = Math.max(0, parseInt(lowStockCount.textContent) - 1);
                        }
                    }

                    // Show success message and close modal
                    showNotification('Container updated successfully!', 'success');
                    closeContainerModal();
                    containerForm.onsubmit = originalSubmitHandler;
                };

                // Open modal
                containerModal.style.display = 'flex';
                setTimeout(() => {
                    containerModal.classList.add('show');
                }, 10);
                document.body.style.overflow = 'hidden';
            }

            // Function to create container cards
            function createContainerCard(name, capacity, availableStock, status) {
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

                const isActive = status === 'Available';

                containerCard.innerHTML = `
                <div class="product-header">
                    <div class="product-name">${name}</div>
                    <span class="stock-badge ${stockBadgeClass}">${stockBadgeText}</span>
                </div>
                <div class="product-details">
                    <div class="detail-item">
                        <span>Capacity:</span>
                        <span>${parseFloat(capacity).toFixed(2)} L</span>
                    </div>
                    <div class="detail-item">
                        <span>Available:</span>
                        <span class="stock-value editable-stock" data-id="${containerId}">${availableStock} pcs</span>
                    </div>
                    <div class="detail-item">
                        <span>Status:</span>
                        <span class="status ${isActive ? 'active' : 'inactive'}">${status}</span>
                    </div>
                </div>
                <div class="product-actions">
                    <button class="update-stock-btn" data-id="${containerId}" title="Update Stock"><i class="fas fa-sync-alt"></i></button>
                    <button class="edit-btn" title="Edit Container"><i class="fas fa-edit"></i></button>
                    <button class="delete-btn" title="Delete Container"><i class="fas fa-trash"></i></button>
                </div>
            `;

                // Add event listeners to the buttons
                setTimeout(() => {
                    const deleteBtn = containerCard.querySelector('.delete-btn');
                    const editBtn = containerCard.querySelector('.edit-btn');
                    const updateStockBtn = containerCard.querySelector('.update-stock-btn');

                    deleteBtn.addEventListener('click', function () {
                        confirmDeleteContainer(containerCard);
                    });

                    editBtn.addEventListener('click', function () {
                        editContainer(containerCard);
                    });

                    updateStockBtn.addEventListener('click', function () {
                        const containerId = this.getAttribute('data-id');
                        updateContainerStock(containerId);
                    });
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
                                    <label for="currentStock">Current Stock</label>
                                    <input type="text" id="currentStock" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="updateAction">Action</label>
                                    <select id="updateAction" required>
                                        <option value="add">Add to Stock</option>
                                        <option value="remove">Remove from Stock</option>
                                        <option value="set">Set Specific Value</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="updateQuantity">Quantity</label>
                                    <div class="stock-input">
                                        <input type="number" id="updateQuantity" min="1" required>
                                        <span class="unit">pcs</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="updateNote">Note (Optional)</label>
                                    <textarea id="updateNote" placeholder="Reason for update"></textarea>
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

                        const containerId = document.getElementById('updateContainerId').value;
                        const action = document.getElementById('updateAction').value;
                        const quantity = parseInt(document.getElementById('updateQuantity').value);

                        // Get the container stock element
                        const stockElement = document.querySelector(`.stock-value[data-id="${containerId}"]`);
                        const currentStock = parseInt(stockElement.textContent);

                        // Calculate new stock value
                        let newStock;
                        switch (action) {
                            case 'add':
                                newStock = currentStock + quantity;
                                break;
                            case 'remove':
                                newStock = Math.max(0, currentStock - quantity);
                                break;
                            case 'set':
                                newStock = quantity;
                                break;
                        }

                        // Update the stock display
                        stockElement.textContent = newStock + ' pcs';

                        // Update the stock badge
                        const containerCard = stockElement.closest('.container-card');
                        const stockBadge = containerCard.querySelector('.stock-badge');
                        const wasLowStock = stockBadge.classList.contains('low-stock');
                        const isNowLowStock = newStock < 20;

                        if (isNowLowStock) {
                            stockBadge.className = 'stock-badge low-stock';
                            stockBadge.textContent = 'Low Stock';

                            // Update low stock count if it wasn't low stock before
                            if (!wasLowStock) {
                                lowStockCount.textContent = parseInt(lowStockCount.textContent) + 1;
                            }
                        } else {
                            stockBadge.className = 'stock-badge in-stock';
                            stockBadge.textContent = 'In Stock';

                            // Update low stock count if it was low stock before
                            if (wasLowStock) {
                                lowStockCount.textContent = Math.max(0, parseInt(lowStockCount.textContent) - 1);
                            }
                        }

                        // Show success message
                        showNotification('Container stock updated successfully!', 'success');

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

                // Get current stock value
                const stockElement = document.querySelector(`.stock-value[data-id="${containerId}"]`);
                const currentStock = stockElement.textContent;

                // Set values in the update stock form
                document.getElementById('updateContainerId').value = containerId;
                document.getElementById('currentStock').value = currentStock;
                document.getElementById('updateQuantity').value = '';

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

        // Function to show notification (global)
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

            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);

            // Hide and remove notification after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add CSS for inputs
        const style = document.createElement('style');
        style.textContent = `
        /* Input Styles */
        .price-input, .stock-input, .capacity-input {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .price-input .currency, .stock-input .unit, .capacity-input .unit {
            position: absolute;
            color: var(--text-light);
        }
        
        .price-input .currency {
            left: 12px;
        }
        
        .price-input input {
            padding-left: 25px;
        }
        
        .stock-input .unit, .capacity-input .unit {
            right: 12px;
        }
        
        .stock-input input, .capacity-input input {
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

        /* Empty state messages */
        .no-products, .no-containers {
            grid-column: 1 / -1;
            padding: 20px;
            text-align: center;
            background: var(--white);
            border-radius: 8px;
            color: var(--text-light);
            font-style: italic;
        }
    `;
        document.head.appendChild(style);
    </script>
</body>

</html>
