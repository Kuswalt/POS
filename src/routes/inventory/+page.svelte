<script lang="ts">
    import { goto } from '$app/navigation';
    import Header from '$lib/header.svelte';
    import SideNav from '$lib/sideNav.svelte';

    let selectedCategory: string = 'All Item Stocks';
    let itemName = '';
    let stockQuantity = 0;
    let unitOfMeasure = 'pieces';
    let searchQuery = '';
    let isEditModalOpen = false;

    let y = 0;
    let innerWidth = 0;
    let innerHeight = 0;

    let items: Array<{ 
        inventory_id: number, 
        item_name: string, 
        stock_quantity: number,
        unit_of_measure: string,
        last_updated?: string 
    }> = [];
    let editingItem: number | null = null;

    $: filteredItems = items.filter(item => 
        item.item_name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    async function fetchItems() {
        const response = await fetch('http://localhost/POS/api/routes.php?request=get-items');
        const result = await response.json();
        if (result.status) {
            items = result.data.map((item: any) => ({
                ...item,
                last_updated: new Date(item.last_updated).toLocaleString()
            }));
        }
    }

    function startEdit(item: typeof items[0]) {
        editingItem = item.inventory_id;
        itemName = item.item_name;
        stockQuantity = item.stock_quantity;
        isEditModalOpen = true;
    }

    function closeEditModal() {
        isEditModalOpen = false;
        editingItem = null;
        itemName = '';
        stockQuantity = 0;
    }

    async function addItemStock() {
        const response = await fetch('http://localhost/POS/api/routes.php?request=add-item-stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                item_name: itemName,
                stock_quantity: stockQuantity,
            }),
        });
        const result = await response.json();
        if (result.status) {
            itemName = '';
            stockQuantity = 0;
            await fetchItems();
        }
    }

    async function updateItemStock(inventoryId: number) {
        const response = await fetch('http://localhost/POS/api/routes.php?request=update-item-stock', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                inventory_id: inventoryId,
                item_name: itemName,
                stock_quantity: stockQuantity,
            }),
        });
        const result = await response.json();
        if (result.status) {
            editingItem = null;
            await fetchItems();
        }
    }

    async function deleteItemStock(inventoryId: number) {
        if (!confirm('Are you sure you want to delete this item?')) return;
        
        const response = await fetch('http://localhost/POS/api/routes.php?request=delete-item-stock', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                inventory_id: inventoryId,
            }),
        });
        const result = await response.json();
        if (result.status) {
            await fetchItems();
        }
    }

    import { onMount } from 'svelte';
    onMount(fetchItems);
</script>

<div class="layout">
    <Header {y} {innerHeight} />
    <div class="content">
        <main>
            <div class="product-details">
                <div class="details">
                    <input type="text" placeholder="Product name" bind:value={itemName} />
                    <div class="quantity-input">
                        <input type="number" placeholder="Quantity" bind:value={stockQuantity} />
                        <select bind:value={unitOfMeasure}>
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
                    <button on:click={addItemStock}>Add Item</button>
                </div>
            </div>

            <div class="search-bar">
                <input 
                    type="text" 
                    bind:value={searchQuery}
                    placeholder="Search items..." 
                    class="search-input"
                />
            </div>

            <div class="stock-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each filteredItems as item}
                            <tr>
                                <td>{item.item_name}</td>
                                <td>{item.stock_quantity}</td>
                                <td>{item.unit_of_measure}</td>
                                <td>{item.last_updated}</td>
                                <td>
                                    <button on:click={() => startEdit(item)}>Edit</button>
                                    <button on:click={() => deleteItemStock(item.inventory_id)}>Delete</button>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

{#if isEditModalOpen && editingItem}
    <div class="modal-backdrop">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Item Stock</h2>
                <button class="close-btn" on:click={closeEditModal}>&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" placeholder="Product name" bind:value={itemName} />
                <div class="quantity-input">
                    <input type="number" placeholder="Quantity" bind:value={stockQuantity} />
                    <select bind:value={unitOfMeasure}>
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
                <div class="modal-actions">
                    <button class="update-btn" on:click={() => {
                        updateItemStock(editingItem!);
                        closeEditModal();
                    }}>Update</button>
                    <button class="cancel-btn" on:click={closeEditModal}>Cancel</button>
                </div>
            </div>
        </div>
    </div>
{/if}

<style>
    .layout {
        display: flex;
        height: 100vh;
    }
    .content {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        padding: 20px;
        margin-top: 4rem;
        justify-content: flex-start;
    }
    .product-details {
        margin: 20px;
    }
    .stock-table {
        margin: 20px;
    }
    .side-nav {
        margin-top: 50px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
    }
    th {
        background-color: #f2f2f2;
    }
    button {
        margin: 0 5px;
        padding: 8px 16px;
        cursor: pointer;
        border: none;
        border-radius: 4px;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    
    .details {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    input {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .search-bar {
        margin: 20px;
    }

    .search-input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
    }

    .modal-body {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .update-btn {
        background: #4CAF50;
        color: white;
    }

    .cancel-btn {
        background: #f44336;
        color: white;
    }

    .details button {
        background-color: #4CAF50;
        color: white;
    }

    .details button:hover {
        background-color: #45a049;
    }

    td button:first-child {
        background-color: #2196F3;
        color: white;
    }

    td button:first-child:hover {
        background-color: #1976D2;
    }

    td button:last-child {
        background-color: #f44336;
        color: white;
    }

    td button:last-child:hover {
        background-color: #d32f2f;
    }

    .quantity-input {
        display: flex;
        gap: 5px;
    }

    .quantity-input input,
    .quantity-input select {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .quantity-input select {
        min-width: 120px;
    }
</style>