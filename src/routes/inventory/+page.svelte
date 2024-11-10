<script lang="ts">
    import { goto } from '$app/navigation';
    import Header from '$lib/header.svelte';
    import SideNav from '$lib/sideNav.svelte';

    let selectedCategory: string = 'All Item Stocks';
    let itemName = '';
    let stockQuantity = 0;

    let y = 0;
    let innerWidth = 0;
    let innerHeight = 0;

    let items: Array<{ inventory_id: number, item_name: string, stock_quantity: number }> = [];
    let editingItem: number | null = null;

    function handleCategorySelect(category: string) {
        selectedCategory = category;
    }

    function navigateToCart() {
        goto('/Cart');
    }

    async function fetchItems() {
        const response = await fetch('http://localhost/POS/api/routes.php?request=get-items');
        const result = await response.json();
        if (result.status) {
            items = result.data;
        }
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

    function startEdit(item: typeof items[0]) {
        editingItem = item.inventory_id;
        itemName = item.item_name;
        stockQuantity = item.stock_quantity;
    }

    import { onMount } from 'svelte';
    onMount(fetchItems);
</script>

<div class="layout">
    <Header {y} {innerHeight} />
    <!-- <SideNav activeMenu="inventory" /> -->
    <div class="content">
        <!-- <Header {y} {innerHeight} /> -->
        <main>
            <div class="product-details">
                <div class="details">
                    <input type="text" placeholder="Product name" bind:value={itemName} />
                    <input type="number" placeholder="Quantity" bind:value={stockQuantity} />
                    {#if editingItem}
                        <button on:click={() => editingItem && updateItemStock(editingItem)}>Update</button>
                        <button on:click={() => {
                            editingItem = null;
                            itemName = '';
                            stockQuantity = 0;
                        }}>Cancel</button>
                    {:else}
                        <button on:click={addItemStock}>Add Item</button>
                    {/if}
                </div>
            </div>

            <div class="stock-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each items as item}
                            <tr>
                                <td>{item.item_name}</td>
                                <td>{item.stock_quantity}</td>
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
        padding: 5px 10px;
        cursor: pointer;
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
</style>