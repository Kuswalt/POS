<script lang="ts">
    import { goto } from '$app/navigation';
    import Header from '$lib/header.svelte';
    import SideNav from '$lib/sideNav.svelte';

    let selectedCategory: string = 'All Item Stocks';
    let productName = '';
    let description = '';
    let price = '';
    let stockQuantity = 0;

    let y = 0;
    let innerWidth = 0;
    let innerHeight = 0;

    function handleCategorySelect(category: string) {
        selectedCategory = category;
    }

    function navigateToCart() {
        goto('/Cart');
    }

    async function addItemStock() {
        const response = await fetch('http://localhost/Laz-Bean-Cafe-POS/api/routes.php?request=add-item-stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productName,
                stock_quantity: stockQuantity,
            }),
        });
        const result = await response.json();
        console.log(result);
    }

    async function updateItemStock(inventoryId: number) {
        const response = await fetch('http://localhost/Laz-Bean-Cafe-POS/api/routes.php?request=update-item-stock', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                inventory_id: inventoryId,
                stock_quantity: stockQuantity,
            }),
        });
        const result = await response.json();
        console.log(result);
    }

    async function deleteItemStock(inventoryId: number) {
        const response = await fetch('http://localhost/Laz-Bean-Cafe-POS/api/routes.php?request=delete-item-stock', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                inventory_id: inventoryId,
            }),
        });
        const result = await response.json();
        console.log(result);
    }
</script>

<div class="layout">
    <SideNav activeMenu="inventory" />
    <div class="content">
        <Header {y} {innerHeight} />
        <main>
            <div class="product-details">
                <div class="details">
                    <input type="text" placeholder="Product name" bind:value={productName} />
                    <input type="text" placeholder="DESCRIPTION" bind:value={description} />
                    <input type="text" placeholder="PRICE" bind:value={price} />
                    <input type="number" placeholder="Quantity" bind:value={stockQuantity} />
                    <button on:click={addItemStock}>Confirm</button>
                    <button on:click={() => updateItemStock(1)}>Edit</button>
                    <button on:click={() => deleteItemStock(1)}>Delete</button>
                </div>
            </div>

            <div class="stock-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each Array(3) as _, i}
                            <tr>
                                <td>Item {i + 1}</td>
                                <td>Description {i + 1}</td>
                                <td>0</td>
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
</style>