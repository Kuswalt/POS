<script lang="ts">
  import { onMount } from 'svelte';

  // Define types for items and newItem
  type Item = { product_id: number; name: string; image: string; price: string; category: string };
  type NewItem = { name: string; image: File; price: string; category: string };

  let items: Item[] = [];
  let newItem: NewItem = { name: '', image: new File([], ''), price: '', category: '' };
  let editItem: Item; // Initialize editItem as null

  onMount(async () => {
    await fetchItems();
  });

  async function fetchItems() {
    const response = await fetch('http://localhost/POS/api/routes.php?request=get-menu-items');
    items = await response.json();
  }

  async function addItem() {
    console.log('New Item:', newItem);
    if (!newItem.image) {
      alert("Please select an image.");
      return;
    }

    const formData = new FormData();
    formData.append('name', newItem.name);
    formData.append('image', newItem.image);
    formData.append('price', newItem.price);
    formData.append('category', newItem.category);

    const response = await fetch('http://localhost/POS/api/routes.php?request=add-menu-item', {
      method: 'POST',
      body: formData,
    });
    const result = await response.json();
    if (result.status) {
      await fetchItems(); // Refresh the items list
      newItem = { name: '', image: new File([], ''), price: '', category: '' }; // Reset form
    } else {
      alert(result.message);
    }
  }

  async function updateItem() {
    if (!editItem) return; // Ensure editItem is not null
    if (!editItem.image) {
      alert("Please select an image.");
      return;
    }

    const formData = new FormData();
    formData.append('product_id', editItem.product_id.toString());
    formData.append('name', editItem.name);
    formData.append('image', editItem.image);
    formData.append('price', editItem.price);
    formData.append('category', editItem.category);

    const response = await fetch('http://localhost/POS/api/routes.php?request=update-menu-item', {
      method: 'PUT',
      body: formData,
    });
    const result = await response.json();
    if (result.status) {
      const index = items.findIndex(item => item.product_id === editItem.product_id);
      items[index] = editItem;
      // editItem = null; // Reset editItem after update
    } else {
      alert(result.message);
    }
  }

  async function deleteItem(itemId: number) {
    const response = await fetch('http://localhost/POS/api/routes.php?request=delete-menu-item', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ product_id: itemId }),
    });
    const result = await response.json();
    if (result.status) {
      items = items.filter(item => item.product_id !== itemId);
    } else {
      alert(result.message);
    }
  }

  function startEdit(item: Item) {
    editItem = { ...item }; // Clone item for editing
  }
</script>



<h1>Manage Menu Items</h1>

<form on:submit|preventDefault={addItem}>
  <input type="text" bind:value={newItem.name} placeholder="Item Name" required />
  <input type="file" accept="image/*" on:change={e => {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (files && files.length > 0) {
      newItem.image = files[0] as File;
    }
  }} required />
  <input type="text" bind:value={newItem.price} placeholder="Price" required />
  <input type="text" bind:value={newItem.category} placeholder="Category" required />
  <button type="submit">Add Item</button>
</form>

{#if editItem}
  <h2>Edit Item</h2>
  <form on:submit|preventDefault={updateItem}>
    <input type="text" bind:value={editItem!.name} placeholder="Item Name" required />
    <!-- <input type="file" accept="image/*" on:change={e => editItem.image = (e.target.files[0] as File)} required /> -->
    <input type="text" bind:value={editItem!.price} placeholder="Price" required />
    <input type="text" bind:value={editItem!.category} placeholder="Category" required />
    <button type="submit">Update Item</button>
    <button type="button" on:click={() => editItem}>Cancel</button>
  </form>
{/if}

<h2>Current Menu Items</h2>
<div class="grid grid-cols-4 gap-4">
  {#each items as item}
    <div class="item-card">
      <img src={item.image ? `uploads/${item.image}` : 'placeholder.jpg'} alt={item.name} class="item-image" />
      <div class="item-details">
        <h3>{item.name}</h3>
        <p>Price: ${item.price}</p>
        <p>Category: {item.category}</p>
        <div class="item-actions">
          <button on:click={() => startEdit(item)} class="edit-btn">Edit</button>
          <button on:click={() => deleteItem(item.product_id)} class="delete-btn">Delete</button>
        </div>
      </div>
    </div>
  {/each}
</div>

<style>
  .grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    padding: 1rem;
  }

  .item-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s;
  }

  .item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }

  .item-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
  }

  .item-details {
    padding: 1rem;
  }

  .item-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
  }

  .edit-btn, .delete-btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
  }

  .edit-btn {
    background-color: #4CAF50;
    color: white;
  }

  .delete-btn {
    background-color: #f44336;
    color: white;
  }
</style>
