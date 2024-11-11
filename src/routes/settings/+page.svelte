<script lang="ts">
  import { onMount } from 'svelte';
  import Header from '$lib/header.svelte';

  let y = 0;
  let innerHeight = 0;

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

<Header {y} {innerHeight} />

<div class="settings-container">
  <div class="settings-content">
    <h1 class="text-2xl font-bold mb-6">Manage Menu Items</h1>

    <div class="form-section bg-white p-6 rounded-lg shadow-md mb-6">
      <form on:submit|preventDefault={addItem} class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <input 
            type="text" 
            bind:value={newItem.name} 
            placeholder="Item Name" 
            class="p-2 border rounded-md w-full"
            required 
          />
          <input 
            type="file" 
            accept="image/*" 
            on:change={e => {
              const input = e.target as HTMLInputElement;
              const files = input.files;
              if (files && files.length > 0) {
                newItem.image = files[0] as File;
              }
            }} 
            class="p-2 border rounded-md w-full"
            required 
          />
          <input 
            type="text" 
            bind:value={newItem.price} 
            placeholder="Price" 
            class="p-2 border rounded-md w-full"
            required 
          />
          <input 
            type="text" 
            bind:value={newItem.category} 
            placeholder="Category" 
            class="p-2 border rounded-md w-full"
            required 
          />
        </div>
        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md w-full">Add Item</button>
      </form>
    </div>

    {#if editItem}
      <div class="edit-section bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-bold mb-4">Edit Item</h2>
        <form on:submit|preventDefault={updateItem} class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <input type="text" bind:value={editItem.name} placeholder="Item Name" class="p-2 border rounded-md" required />
            <input type="text" bind:value={editItem.price} placeholder="Price" class="p-2 border rounded-md" required />
            <input type="text" bind:value={editItem.category} placeholder="Category" class="p-2 border rounded-md" required />
          </div>
          <div class="flex gap-2">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md flex-1">Update</button>
            <button type="button" on:click={() => editItem} class="bg-gray-500 text-white px-4 py-2 rounded-md flex-1">Cancel</button>
          </div>
        </form>
      </div>
    {/if}

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      {#each items as item}
        <div class="item-card">
          <img src={item.image ? `uploads/${item.image}` : 'placeholder.jpg'} alt={item.name} class="item-image" />
          <div class="item-details">
            <h3 class="font-bold">{item.name}</h3>
            <p>₱{item.price}</p>
            <p class="text-gray-600">{item.category}</p>
            <div class="item-actions">
              <button on:click={() => startEdit(item)} class="edit-btn">Edit</button>
              <button on:click={() => deleteItem(item.product_id)} class="delete-btn">Delete</button>
            </div>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .settings-container {
    width: 100%;
    min-height: 100vh;
    padding: 1rem;
    background-color: #f5f5f5;
  }

  .settings-content {
    max-width: 1200px;
    margin: 5rem auto 2rem auto;
    padding: 1rem;
  }

  .item-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    margin-top: 1rem;
  }

  .edit-btn, .delete-btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    flex: 1;
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
