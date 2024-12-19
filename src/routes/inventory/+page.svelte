<script lang="ts">
    import { goto } from '$app/navigation';
    import Header from '$lib/header.svelte';
    import SideNav from '$lib/sideNav.svelte';
    import { ApiService } from '$lib/services/api';
    import type { ProductIngredient, ProductIngredientResponse, AlertType } from '$lib/types';
    import { encryptionService } from '$lib/services/encryption';

    interface InventoryItem {
        inventory_id: number;
        item_name: string;
        stock_quantity: number;
        unit_of_measure: string;
        last_updated?: string;
    }

    let selectedCategory: string = 'All Item Stocks';
    let itemName = '';
    let stockQuantity = 0;
    let unitOfMeasure = 'pieces';
    let searchQuery = '';
    let isEditModalOpen = false;

    let y = 0;
    let innerWidth = 0;
    let innerHeight = 0;

    let items: InventoryItem[] = [];
    let editingItem: number | null = null;

    $: filteredItems = items.filter(item => 
        item.item_name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    // Add pagination variables
    let currentPage = 1;
    let itemsPerPage = 10;
    let totalPages = 0;

    // Calculate displayed items based on pagination
    $: {
        totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        if (currentPage > totalPages) {
            currentPage = totalPages || 1;
        }
    }

    $: displayedItems = filteredItems.slice(
        (currentPage - 1) * itemsPerPage,
        currentPage * itemsPerPage
    );

    // Pagination controls
    function changePage(page: number) {
        currentPage = page;
    }

    // Helper function to generate page numbers
    function getPageNumbers() {
        const pages = [];
        const maxVisiblePages = 5;
        
        if (totalPages <= maxVisiblePages) {
            // Show all pages if total pages is less than max visible
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            // Always show first page
            pages.push(1);
            
            // Calculate start and end of visible pages
            let start = Math.max(2, currentPage - 1);
            let end = Math.min(totalPages - 1, currentPage + 1);
            
            // Add ellipsis after first page if needed
            if (start > 2) {
                pages.push('...');
            }
            
            // Add visible pages
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            
            // Add ellipsis before last page if needed
            if (end < totalPages - 1) {
                pages.push('...');
            }
            
            // Always show last page
            pages.push(totalPages);
        }
        
        return pages;
    }

    async function fetchItems() {
        try {
            const result = await ApiService.get<InventoryItem[]>('get-items');
            if (result.status) {
                items = result.data.map(item => ({
                    ...item,
                    last_updated: new Date(item.last_updated).toLocaleString()
                }));
            }
        } catch (error) {
            console.error('Error fetching items:', error);
            items = [];
        }
    }

    function startEdit(item: InventoryItem) {
        editingItem = item.inventory_id;
        let editValues = {
            name: item.item_name,
            quantity: item.stock_quantity,
            unit: item.unit_of_measure
        };
        itemName = editValues.name;
        stockQuantity = editValues.quantity;
        unitOfMeasure = editValues.unit;
        isEditModalOpen = true;
    }

    function closeEditModal() {
        isEditModalOpen = false;
        editingItem = null;
        itemName = '';
        stockQuantity = 0;
        unitOfMeasure = 'pieces';
    }

    let errorMessage = '';
    let showError = false;

    async function addItemStock() {
        if (!itemName.trim() || stockQuantity <= 0) {
            errorMessage = "Product name and quantity are required. Quantity must be greater than 0.";
            showError = true;
            setTimeout(() => showError = false, 3000);
            return;
        }

        const data = {
            item_name: itemName,
            stock_quantity: stockQuantity,
            unit_of_measure: unitOfMeasure
        };

        try {
            const result = await ApiService.post<{status: boolean; message: string}>('add-item-stock', data);

            if (result.status) {
                itemName = '';
                stockQuantity = 0;
                unitOfMeasure = 'pieces';
                await fetchItems();
            } else {
                errorMessage = result.message;
                showError = true;
                setTimeout(() => showError = false, 3000);
            }
        } catch (error) {
            console.error('Error:', error);
            errorMessage = "Failed to add item";
            showError = true;
            setTimeout(() => showError = false, 3000);
        }
    }

    async function updateItemStock(inventoryId: number) {
        try {
            if (!itemName.trim()) {
                alert('Item name cannot be empty');
                return;
            }

            const result = await ApiService.put('update-item-stock', {
                inventory_id: inventoryId,
                item_name: itemName.trim(),
                stock_quantity: stockQuantity,
                unit_of_measure: unitOfMeasure
            });

            if (result.status) {
                await fetchItems();
                closeEditModal();
                showAlert = true;
                alertType = 'success';
                alertMessage = 'Item updated successfully';
                setTimeout(() => showAlert = false, 3000);
            } else {
                showAlert = true;
                alertType = 'error';
                alertMessage = result.message || 'Failed to update item';
                setTimeout(() => showAlert = false, 3000);
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert = true;
            alertType = 'error';
            alertMessage = 'Failed to update item';
            setTimeout(() => showAlert = false, 3000);
        }
    }

    let showAlert = false;
    let alertType: AlertType = 'success';
    let alertMessage = '';
    let showProductsModal = false;
    let selectedItem: any = null;
    let productsUsingIngredient: any[] = [];

    async function showProductsUsingIngredient(item: InventoryItem) {
        try {
            // console.log('Fetching products for item:', item);
            
            const response = await fetch(`${import.meta.env.VITE_API_URL}/get-products-using-ingredient?inventory_id=${item.inventory_id}`);
            const result = await response.json();
            
            // console.log('Raw API Response:', result);

            if (result.status && Array.isArray(result.data)) {
                productsUsingIngredient = result.data;
                selectedItem = item;
                showProductsModal = true;
            } else {
                console.error('API error:', result.message);
                alertMessage = result.message || "Failed to fetch products";
                alertType = 'error';
                showAlert = true;
            }
        } catch (error) {
            console.error('Network error:', error);
            alertMessage = "Failed to fetch products";
            alertType = 'error';
            showAlert = true;
        }
    }

    import { onMount } from 'svelte';
    onMount(fetchItems);

    async function deleteItemStock(inventoryId: number) {
        if (confirm('Are you sure you want to delete this item?')) {
            try {
                const result = await ApiService.delete<{
                    status: boolean;
                    message: string;
                    products?: any[];
                }>('delete-item-stock', { 
                    inventory_id: inventoryId 
                });
                
                if (result.status) {
                    await fetchItems();
                    showAlert = true;
                    alertType = 'success';
                    alertMessage = result.message;
                } else {
                    if (result.products && result.products.length > 0) {
                        productsUsingIngredient = result.products;
                        selectedItem = items.find(i => i.inventory_id === inventoryId);
                        showProductsModal = true;
                    } else {
                        showAlert = true;
                        alertType = 'error';
                        alertMessage = result.message || 'Failed to delete item';
                    }
                }
            } catch (error) {
                console.error('Error deleting item:', error);
                showAlert = true;
                alertType = 'error';
                alertMessage = 'Failed to delete item';
                setTimeout(() => showAlert = false, 3000);
            }
        }
    }

    let showItemsModal = false;
    let activeTab: 'used' | 'deleted' = 'used';
    let usedIngredients: any[] = [];
    let deletedItems: any[] = [];

    async function deleteAllUnusedStocks() {
        if (!confirm('Are you sure you want to delete all unused items? This cannot be undone.')) {
            return;
        }

        try {
            const result = await ApiService.delete<{
                status: boolean;
                message: string;
                usedIngredients?: any[];
                deletedItems?: any[];
            }>('delete-all-stocks', {});

            if (result.deletedItems) {
                deletedItems = result.deletedItems;
                activeTab = 'deleted';
                showItemsModal = true;
            }

            if (result.status) {
                showAlert = true;
                alertType = 'success';
                alertMessage = result.message;
            } else {
                if (result.usedIngredients && result.usedIngredients.length > 0) {
                    usedIngredients = result.usedIngredients;
                    activeTab = 'used';
                    showItemsModal = true;
                }
                showAlert = true;
                alertType = 'warning';
                alertMessage = result.message;
            }
            
            await fetchItems(); // Refresh the table
            setTimeout(() => showAlert = false, 3000);
        } catch (error) {
            console.error('Error deleting stocks:', error);
            showAlert = true;
            alertType = 'error';
            alertMessage = 'Failed to delete stocks';
            setTimeout(() => showAlert = false, 3000);
        }
    }
</script>

{#if showError}
    <div class="alert-container">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{errorMessage}</span>
        </div>
    </div>
{/if}

{#if showAlert}
    <div class="alert-container">
        <div class={`px-4 py-3 rounded relative ${
            alertType === 'success' 
                ? 'bg-green-100 border border-green-400 text-green-700' 
                : 'bg-red-100 border border-red-400 text-red-700'
        }`} role="alert">
            <span class="block sm:inline">{alertMessage}</span>
        </div>
    </div>
{/if}

<div class="layout">
    <Header {y} {innerHeight} />
    <div class="content">
        <main>
            <!-- Product Details Section -->
            <div class="form-section">
                <h2 class="text-2xl font-bold mb-6">Add New Item</h2>
                <div class="input-grid">
                    <input
                        type="text"
                        bind:value={itemName}
                        placeholder="Item name"
                        required
                        class="input-field"
                    />
                    <div class="quantity-group">
                        <input
                            type="number"
                            bind:value={stockQuantity}
                            min="0"
                            required
                            placeholder="Quantity"
                            class="input-field"
                        />
                        <select bind:value={unitOfMeasure} class="input-field">
                            <option value="pieces">Pieces</option>
                            <option value="grams">Grams</option>
                            <option value="kilograms">Kilograms</option>
                            <option value="milliliters">Milliliters</option>
                            <option value="liters">Liters</option>
                            <option value="cups">Cups</option>
                            <option value="tablespoons">Tablespoons</option>
                            <option value="teaspoons">Teaspoons</option>
                        </select>
                    </div>
                    <button on:click={addItemStock} class="add-button">Add Item</button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-section">
                <input 
                    type="text" 
                    bind:value={searchQuery}
                    placeholder="Search items..." 
                    class="search-input"
                />
            </div>

            <!-- Inventory Table -->
            <div class="table-section">
                <div class="table-header">
                    <h2 class="text-2xl font-bold">Current Stock</h2>
                    <button 
                        class="delete-all-button"
                        on:click={deleteAllUnusedStocks}
                    >
                        Delete All Unused Items
                    </button>
                </div>

                {#if filteredItems.length > 0}
                    <div class="pagination-controls">
                        <div class="pagination-info">
                            Showing {(currentPage - 1) * itemsPerPage + 1} to {Math.min(currentPage * itemsPerPage, filteredItems.length)} of {filteredItems.length} entries
                        </div>
                        <div class="pagination-buttons">
                            <button 
                                class="pagination-button"
                                disabled={currentPage === 1}
                                on:click={() => changePage(currentPage - 1)}
                            >
                                Previous
                            </button>

                            {#each getPageNumbers() as pageNum}
                                {#if typeof pageNum === 'string'}
                                    <span class="pagination-ellipsis">{pageNum}</span>
                                {:else}
                                    <button 
                                        class="pagination-button {pageNum === currentPage ? 'active' : ''}"
                                        on:click={() => changePage(pageNum)}
                                    >
                                        {pageNum}
                                    </button>
                                {/if}
                            {/each}

                            <button 
                                class="pagination-button"
                                disabled={currentPage === totalPages}
                                on:click={() => changePage(currentPage + 1)}
                            >
                                Next
                            </button>
                        </div>
                    </div>

                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Stock Quantity</th>
                                <th>Unit</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each displayedItems as item}
                                <tr>
                                    <td data-label="Item Name">{item.item_name}</td>
                                    <td data-label="Stock Quantity">{item.stock_quantity}</td>
                                    <td data-label="Unit">{item.unit_of_measure}</td>
                                    <td data-label="Last Updated">{item.last_updated}</td>
                                    <td class="actions" data-label="Actions">
                                        <button class="edit-btn" on:click={() => startEdit(item)}>
                                            Edit
                                        </button>
                                        <button class="delete-btn" on:click={() => deleteItemStock(item.inventory_id)}>
                                            Delete
                                        </button>
                                        <button class="used-in-btn" on:click={() => showProductsUsingIngredient(item)}>
                                            Used In Products
                                        </button>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                {:else}
                    <p class="text-center text-gray-500 my-4">No items found</p>
                {/if}
            </div>
        </main>
    </div>
</div>

{#if isEditModalOpen}
    <div class="modal-overlay" on:click|self={() => closeEditModal()}>
        <div class="modal-content" on:click|stopPropagation>
            <h2 class="text-xl font-bold mb-4">Edit Item</h2>
            <div class="grid gap-4">
                <input
                    type="text"
                    bind:value={itemName}
                    placeholder="Item name"
                    class="input-field"
                    required
                />
                <div class="flex gap-4">
                    <input
                        type="number"
                        bind:value={stockQuantity}
                        min="0"
                        placeholder="Quantity"
                        class="input-field"
                        required
                    />
                    <select 
                        bind:value={unitOfMeasure} 
                        class="input-field"
                    >
                        <option value="pieces">Pieces</option>
                        <option value="grams">Grams</option>
                        <option value="kilograms">Kilograms</option>
                        <option value="milliliters">Milliliters</option>
                        <option value="liters">Liters</option>
                        <option value="cups">Cups</option>
                        <option value="tablespoons">Tablespoons</option>
                        <option value="teaspoons">Teaspoons</option>
                    </select>
                </div>
                <div class="flex gap-4">
                    <button
                        class="flex-1 bg-[#d4a373] text-white py-2 px-4 rounded hover:bg-[#c49363]"
                        on:click={() => {
                            if (editingItem) {
                                updateItemStock(editingItem);
                            }
                        }}
                    >
                        Update
                    </button>
                    <button
                        class="flex-1 bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600"
                        on:click={closeEditModal}
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
{/if}

{#if showProductsModal && selectedItem}
    <div class="modal-overlay" on:click|self={() => showProductsModal = false}>
        <div class="modal-content">
            <h2 class="text-xl font-bold mb-4">
                Products Using {selectedItem.item_name}
            </h2>
            
            {#if productsUsingIngredient.length === 0}
                <p class="text-gray-500">No products are using this ingredient.</p>
            {:else}
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left py-2">Product Name</th>
                                <th class="text-left py-2">Category</th>
                                <th class="text-right py-2">Quantity Needed</th>
                                <th class="text-left py-2">Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each productsUsingIngredient as product}
                                <tr class="border-t">
                                    <td class="py-2">{product.product_name}</td>
                                    <td class="py-2">{product.category}</td>
                                    <td class="py-2 text-right">{product.quantity_needed}</td>
                                    <td class="py-2">{product.unit_of_measure}</td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
            
            <button 
                class="mt-4 w-full bg-[#d4a373] text-white py-2 px-4 rounded hover:bg-[#c49363]"
                on:click={() => showProductsModal = false}
            >
                Close
            </button>
        </div>
    </div>
{/if}

{#if showItemsModal}
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="tabs">
                <button 
                    class="tab-button {activeTab === 'used' ? 'active' : ''}"
                    on:click={() => activeTab = 'used'}
                >
                    Items In Use
                </button>
                <button 
                    class="tab-button {activeTab === 'deleted' ? 'active' : ''}"
                    on:click={() => activeTab = 'deleted'}
                >
                    Deleted Items
                </button>
            </div>

            {#if activeTab === 'used'}
                <div class="tab-content">
                    <h3 class="text-xl font-bold mb-4">Items Currently In Use</h3>
                    <p class="mb-4">The following items cannot be deleted because they are being used in products:</p>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="text-left py-2">Item Name</th>
                                    <th class="text-left py-2">Used In Product</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each usedIngredients as item}
                                    <tr class="border-t">
                                        <td class="py-2">{item.item_name}</td>
                                        <td class="py-2">{item.product_name}</td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </div>
            {:else}
                <div class="tab-content">
                    <h3 class="text-xl font-bold mb-4">Deleted Items</h3>
                    <p class="mb-4">The following items have been deleted:</p>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="text-left py-2">Item Name</th>
                                    <th class="text-left py-2">Quantity</th>
                                    <th class="text-left py-2">Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each deletedItems as item}
                                    <tr class="border-t">
                                        <td class="py-2">{item.item_name}</td>
                                        <td class="py-2">{item.stock_quantity}</td>
                                        <td class="py-2">{item.unit_of_measure}</td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </div>
            {/if}
            
            <button 
                class="mt-4 w-full bg-[#d4a373] text-white py-2 px-4 rounded hover:bg-[#c49363]"
                on:click={() => showItemsModal = false}
            >
                Close
            </button>
        </div>
    </div>
{/if}

<style>
    .layout {
        width: 100%;
        min-height: 100vh;
    }

    .content {
        margin-top: 4rem;
        padding: 2rem;
        background-color: #fefae0;
    }

    .form-section {
        background: #faedcd;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .input-grid {
        display: grid;
        gap: 1rem;
    }

    .input-field {
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        width: 100%;
    }

    .quantity-group {
        display: flex;
        gap: 1rem;
    }

    .add-button {
        background: #d4a373;
        color: white;
        padding: 0.75rem;
        border-radius: 0.5rem;
        width: 100%;
    }

    .search-section {
        margin-bottom: 1rem;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }

    .table-section {
        background: #faedcd;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-top: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .delete-all-button {
        background-color: #ef4444;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: background-color 0.2s;
    }

    .delete-all-button:hover {
        background-color: #dc2626;
    }

    /* Mobile styles */
    @media (max-width: 768px) {
        .content {
            padding: 1rem;
        }

        .form-section, .table-section {
            padding: 1rem;
        }

        .quantity-group {
            flex-direction: column;
        }

        .responsive-table tr {
            display: flex;
            flex-direction: column;
            background: white;
            margin-bottom: 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 0.75rem;
        }

        .responsive-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .responsive-table td:last-child {
            border-bottom: none;
        }

        .responsive-table thead {
            display: none;
        }

        .responsive-table td::before {
            content: attr(data-label);
            font-weight: 600;
            margin-right: 1rem;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .actions button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .edit-btn {
            background: #3B82F6;
            color: white;
        }

        .delete-btn {
            background: #DEB887;
            color: white;
        }

        .used-in-btn {
            background: #d4a373;
            color: white;
        }
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 50;
    }

    .modal-content {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    /* Mobile styles for modals */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 1rem;
            padding: 1rem;
        }
    }

    .actions {
        display: flex;
        gap: 0.5rem;
    }

    .actions button {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: opacity 0.2s;
        color: white;
    }

    /* Action button colors */
    button.edit-btn {
        background-color: #3B82F6;  /* Blue */
    }

    button.delete-btn {
        background-color: #DEB887;  /* Tan */
    }

    button.used-in-btn {
        background-color: #d4a373;  /* Brown */
    }

    /* Hover effects */
    .actions button:hover {
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        /* ... mobile styles ... */

        .actions {
            flex-direction: column;
        }

        .actions button {
            width: 100%;
            text-align: center;
            padding: 0.75rem;
        }
    }

    .alert-container {
        position: fixed;
        top: 1rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 100;
        width: 90%;
        max-width: 600px;
    }

    .tabs {
        display: flex;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 1rem;
    }

    .tab-button {
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        color: #6b7280;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
    }

    .tab-button:hover {
        color: #d4a373;
    }

    .tab-button.active {
        color: #d4a373;
        border-bottom-color: #d4a373;
    }

    .tab-content {
        padding: 1rem 0;
    }

    @media (max-width: 640px) {
        .tab-button {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
    }

    /* Add these pagination styles */
    .pagination-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: white;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .pagination-info {
        color: #6b7280;
    }

    .pagination-buttons {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .pagination-button {
        padding: 0.5rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background: white;
        color: #374151;
        cursor: pointer;
        transition: all 0.2s;
    }

    .pagination-button:hover:not(:disabled) {
        background: #f3f4f6;
    }

    .pagination-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination-button.active {
        background: #d4a373;
        color: white;
        border-color: #d4a373;
    }

    .pagination-ellipsis {
        padding: 0.5rem;
        color: #6b7280;
    }

    /* Add responsive styles for pagination */
    @media (max-width: 768px) {
        .pagination-controls {
            flex-direction: column;
            gap: 1rem;
        }

        .pagination-buttons {
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }

        .pagination-info {
            text-align: center;
        }
    }
</style>