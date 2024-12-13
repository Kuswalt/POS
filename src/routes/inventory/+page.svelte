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
                    <h3 class="text-lg font-semibold">Current Stock</h3>
                    <button 
                        class="delete-all-button"
                        on:click={async () => {
                            if (confirm('Are you sure you want to delete ALL stocks? This cannot be undone.')) {
                                // ... existing delete code ...
                            }
                        }}
                    >
                        Delete All Stocks
                    </button>
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
                        {#each filteredItems as item}
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
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .delete-all-button {
        background: #ef4444;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
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
</style>