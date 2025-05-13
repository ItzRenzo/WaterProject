<?php
session_start();
include_once '../../Database/db_config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - RJane Water</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Css/Driver.css">
</head>

<body>
    <div class="dashboard">
        <header>
            <h1><i class="fas fa-water"></i> RJane Water Driver</h1>
            <div class="user-actions">
                <span class="driver-info">John Driver (DRV-001)</span>
                <a href="../Controllers/Logout.php" class="action-btn logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <div class="action-toolbar">
            <h2><i class="fas fa-clipboard-list"></i> Available Orders</h2>
            <div class="toolbar-actions">
                <button id="select-all" class="toolbar-btn"><i class="fas fa-check-square"></i> Select All</button>
                <button id="print-selected" class="toolbar-btn print-btn"><i class="fas fa-print"></i> Print Selected</button>
            </div>
        </div>
        
        <div class="orders" id="available-orders">              
            <div class="order-card" data-order-id="WR-654321">
                <div class="order-checkbox">
                    <input type="checkbox" id="order-WR-654321" class="order-select">
                    <label for="order-WR-654321" class="select-indicator">
                        <i class="fas fa-check"></i>
                    </label>
                </div>
                <div class="order-header">
                    <div>Order #WR-654321</div>
                    <div class="status status-pending">New</div>
                </div>
                <div class="order-body">
                    <div class="order-items">
                        <p><span>1x Alkaline Water</span> <span>₱45.00</span></p>
                        <p><span>1x Mineral Water</span> <span>₱35.00</span></p>
                    </div>
                    <div class="order-address">
                        <p><i class="fas fa-map-marker-alt"></i> 456 Park Avenue, Barangay Poblacion</p>
                    </div>
                </div>
                <div class="order-footer">
                    <div>Total: ₱80.00</div>
                    <div>
                        <button class="btn-accept">Accept</button>
                        <button class="btn-skip">Skip</button>
                    </div>
                </div>
            </div>
            
            
            
            
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select all button functionality
            const selectAllButton = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.order-select');
            let allSelected = false;
            
            selectAllButton.addEventListener('click', function() {
                allSelected = !allSelected;
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = allSelected;
                    updateOrderCardSelection(checkbox);
                });
                
                // Update button text
                selectAllButton.innerHTML = allSelected ? 
                    '<i class="fas fa-square"></i> Deselect All' :
                    '<i class="fas fa-check-square"></i> Select All';
            });
            
            // Individual checkbox functionality
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateOrderCardSelection(this);
                    
                    // Check if all are selected
                    const totalCheckboxes = document.querySelectorAll('.order-select').length;
                    const selectedCheckboxes = document.querySelectorAll('.order-select:checked').length;
                    
                    if (selectedCheckboxes === totalCheckboxes) {
                        selectAllButton.innerHTML = '<i class="fas fa-square"></i> Deselect All';
                        allSelected = true;
                    } else {
                        selectAllButton.innerHTML = '<i class="fas fa-check-square"></i> Select All';
                        allSelected = false;
                    }
                });
            });
            
            // Helper function to update order card highlighting
            function updateOrderCardSelection(checkbox) {
                const orderCard = checkbox.closest('.order-card');
                const orderHeader = orderCard.querySelector('.order-header');
                
                if (checkbox.checked) {
                    orderHeader.classList.add('selected');
                } else {
                    orderHeader.classList.remove('selected');
                }
            }
            
            // Print functionality
            document.getElementById('print-selected').addEventListener('click', function() {
                const selectedOrders = document.querySelectorAll('.order-select:checked');
                
                if (selectedOrders.length === 0) {
                    alert('Please select at least one order to print');
                    return;
                }
                
                // Hide all non-selected orders before printing
                document.querySelectorAll('.order-card').forEach(card => {
                    const isChecked = card.querySelector('.order-select').checked;
                    if (!isChecked) {
                        card.style.display = 'none';
                    }
                });
                
                // Print the selected orders
                window.print();
                
                // Show all orders again
                document.querySelectorAll('.order-card').forEach(card => {
                    card.style.display = 'block';
                });
            });
            
            // Accept order functionality
            document.querySelectorAll('.btn-accept').forEach(button => {
                button.addEventListener('click', function() {
                    const orderCard = this.closest('.order-card');
                    const orderId = orderCard.getAttribute('data-order-id');
                    
                    alert(`Order ${orderId} has been accepted for delivery.`);
                    orderCard.remove();
                });
            });
            
            // Skip order functionality
            document.querySelectorAll('.btn-skip').forEach(button => {
                button.addEventListener('click', function() {
                    const orderCard = this.closest('.order-card');
                    orderCard.remove();
                });
            });
            
            // Add this to your existing JavaScript
            document.querySelectorAll('.order-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on buttons or checkbox directly
                    if (e.target.closest('.btn-accept') || 
                        e.target.closest('.btn-skip') || 
                        e.target.closest('.order-checkbox')) {
                        return;
                    }
                    
                    // Toggle the checkbox
                    const checkbox = this.querySelector('.order-select');
                    checkbox.checked = !checkbox.checked;
                    
                    // Trigger the change event
                    const event = new Event('change');
                    checkbox.dispatchEvent(event);
                });
            });
        });
    </script>
</body>
</html>