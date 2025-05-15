<!-- Edit Product Modal (Separate from Add Product Modal) -->
<div class="modal" id="editProductModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Product</h3>
            <button class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editProductForm">
                <input type="hidden" id="editProductOldName">
                <div class="form-group">
                    <label for="editProductName">Product Name</label>
                    <input type="text" id="editProductName" required>
                </div>
                <div class="form-group">
                    <label for="editProductPrice">Price</label>
                    <input type="number" id="editProductPrice" required>
                </div>                <div class="form-group">
                    <label for="editProductContainer">Container Type</label>
                    <select id="editProductContainer">
                        <option value="">Select Container</option>
                        <?php
                        // Re-query containers for the dropdown to avoid empty result set
                        $editContainerDropdownQuery = "SELECT * FROM Container ORDER BY ContainerID DESC";
                        $editContainerDropdownResult = $conn->query($editContainerDropdownQuery);
                        while ($container = $editContainerDropdownResult->fetch_assoc()) { ?>
                            <option value="<?php echo $container['ContainerID']; ?>" data-stocks="<?php echo $container['Stocks']; ?>"><?php echo $container['ContainerType']; ?></option>
                        <?php } ?>
                    </select>
                    <div id="containerStocksInfo" class="stock-info"></div>
                </div>
                <div class="form-group">
                    <label for="editProductDescription">Description</label>
                    <textarea id="editProductDescription"></textarea>
                </div>
                <div class="form-group">
                    <label for="editProductStatus">Status</label>
                    <select id="editProductStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>                <!-- Hidden field to store the original container type -->
                <input type="hidden" id="editContainerType">                <div class="form-group" id="addStocksGroup">
                    <label for="addStocks">Add Stocks</label>
                    <input type="number" id="addStocks" min="0" value="0">
                    <small>Adding stocks will subtract from container stocks</small>
                    <div id="stockWarning" class="stock-info" style="display: none; color: var(--danger);">
                        Warning: Requested stock exceeds available container stock
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="save-btn">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
