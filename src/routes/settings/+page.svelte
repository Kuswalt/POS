<script lang="ts">
  import { onMount } from 'svelte';
  import Header from '$lib/header.svelte';
  import Alert from '$lib/components/Alert.svelte';
  import RecipeManager from '$lib/RecipeManager.svelte';

  let y = 0;
  let innerHeight = 0;

  // Define types for items and newItem
  type Item = { product_id: number; name: string; image: string | File; price: string; category: string; size?: string; imageUrl?: string };
  type NewItem = { name: string; image: File; price: string; category: string; size?: string };

  let items: Item[] = [];
  const categories = ['Pizza', 'Burger & Fries', 'Nachos', 'Drinks', 'Chocolate Series', 'Cheesecake Series']; // Add your categories here
  const sizeOptions = {
    'Pizza': ['Small', 'Standard', 'Large'],
    'Burger & Fries': ['Small', 'Standard', 'Large'],
    'Nachos': ['Small', 'Standard', 'Large'],
    'Drinks': ['Small', 'Standard', 'Large'],
    'Chocolate Series': ['Small', 'Standard', 'Large'],
    'Cheesecake Series': ['Small', 'Standard', 'Large']
  };
  let newItem: NewItem = { name: '', image: new File([], ''), price: '', category: categories[0], size: '' };
  let editItem: Item; // Change the type declaration to allow null
  let isEditModalOpen = false;
  let searchQuery = '';
  let showAlert = false;
  let alertMessage = '';
  let alertType: 'success' | 'warning' | 'error' = 'warning';
  let showRecipeManager = false;
  let selectedProduct = null;

  onMount(async () => {
    await fetchItems();
  });

  async function fetchItems() {
    const response = await fetch('/api/get-menu-items');
    const data = await response.json();
    items = data.map((item: Item) => ({
      ...item,
      imageUrl: item.image ? `/POS/uploads/${item.image}` : '/placeholder.jpg'
      // imageUrl: item.image ? `http://localhost/POS/uploads/${item.image}` : '/placeholder.jpg'
    }));
  }

  async function addItem() {
    if (!newItem.image) {
      alertMessage = "Please select an image.";
      alertType = 'warning';
      showAlert = true;
      setTimeout(() => showAlert = false, 3000);
      return;
    }

    // Check if item already exists
    const existingItem = items.find(item => 
      item.name.toLowerCase() === newItem.name.toLowerCase() && 
      item.category === newItem.category &&
      (item.size === newItem.size || (!item.size && !newItem.size))
    );

    if (existingItem) {
      alertMessage = `A ${newItem.category} item with name "${newItem.name}" ${newItem.size ? `and size ${newItem.size}` : ''} already exists`;
      alertType = 'warning';
      showAlert = true;
      setTimeout(() => showAlert = false, 3000);
      return;
    }

    const formData = new FormData();
    formData.append('name', newItem.name);
    formData.append('image', newItem.image);
    formData.append('price', newItem.price);
    formData.append('category', newItem.category);
    formData.append('size', newItem.size || 'Standard');

    try {
      const response = await fetch('/api/add-menu-item', {
        method: 'POST',
        body: formData,
      });

      const result = await response.json();
      
      showAlert = true;
      if (result.status) {
        alertMessage = "Item added successfully";
        alertType = 'success';
        await fetchItems();
        // Reset form
        newItem = { name: '', image: new File([], ''), price: '', category: categories[0], size: '' };
        // Reset file input
        const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement;
        if (fileInput) fileInput.value = '';
      } else {
        if (result.duplicate) {
          alertType = 'warning';
          alertMessage = "This product already exists with these details";
        } else {
          alertType = 'error';
          alertMessage = result.message || "Failed to add item";
        }
      }
      
      // Auto-hide alert after 3 seconds
      setTimeout(() => showAlert = false, 3000);
      
    } catch (error) {
      showAlert = true;
      alertType = 'error';
      alertMessage = "Error adding item: Network error";
      setTimeout(() => showAlert = false, 3000);
      console.error('Error:', error);
    }
  }

  async function updateItem() {
    if (!editItem) return;

    const formData = new FormData();
    formData.append('product_id', editItem.product_id.toString());
    formData.append('name', editItem.name);
    formData.append('price', editItem.price);
    formData.append('category', editItem.category);
    formData.append('size', editItem.size || 'Standard');
    
    // Handle image upload if a new image was selected
    if (editItem.image instanceof File) {
        formData.append('image', editItem.image);
    }

    try {
        const response = await fetch('/api/update-menu-item', {
            method: 'POST',
            body: formData,
        });
        
        const result = await response.json();
        
        if (result.status) {
            alertMessage = "Menu item updated successfully";
            alertType = 'success';
            await fetchItems();
            isEditModalOpen = false;
        } else {
            alertMessage = result.message || "Failed to update item";
            alertType = 'error';
        }
        
        showAlert = true;
        setTimeout(() => showAlert = false, 3000);
    } catch (error) {
        alertMessage = "Error updating item: Network error";
        alertType = 'error';
        showAlert = true;
        setTimeout(() => showAlert = false, 3000);
        console.error('Error:', error);
    }
  }

  async function deleteItem(itemId: number) {
    const response = await fetch('/api/delete-menu-item', {
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
    editItem = { 
        ...item,
        size: item.size === 'base-size' ? 'Standard' : (item.size || 'Standard')
    };
    isEditModalOpen = true;
  }

  function closeEditModal() {
    isEditModalOpen = false;
    // editItem = null;
  }

  function filterItems(items: Item[], query: string) {
    const lowerQuery = query.toLowerCase();
    return items.filter(item => 
      item.name.toLowerCase().includes(lowerQuery) || 
      item.category.toLowerCase().includes(lowerQuery)
    );
  }

  // Add this function to handle category changes
  function handleCategoryChange(newCategory: string) {
    editItem.category = newCategory;
    // Set Standard as default size for all categories
    editItem.size = 'Standard';
  }

  function openRecipeManager(product) {
    selectedProduct = product;
    showRecipeManager = true;
  }
</script>

<Header {y} {innerHeight} />

{#if showAlert}
  <div class="alert-container">
    <Alert type={alertType} message={alertMessage} />
  </div>
{/if}

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
          <select 
            bind:value={newItem.category} 
            class="p-2 border rounded-md w-full"
            required 
          >
            {#each categories as category}
              <option value={category}>{category}</option>
            {/each}
          </select>
          <select 
            bind:value={newItem.size} 
            class="p-2 border rounded-md w-full"
            required
          >
            <option value="" disabled>Select size</option>
            {#each sizeOptions[newItem.category] as size}
              <option value={size} selected={size === 'Standard'}>{size}</option>
            {/each}
          </select>
        </div>
        <button type="submit" class="bg-[#d4a373] hover:bg-[#cba17f] text-[#faedcd] px-4 py-2 rounded-md w-full transition-colors">Add Item</button>      </form>
    </div>

    <div class="mb-6">
      <input 
        type="text" 
        bind:value={searchQuery}
        placeholder="Search items by name or category..." 
        class="p-2 border rounded-md w-full"
      />
    </div>

    {#if isEditModalOpen && editItem}
      <div class="modal-backdrop">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="text-xl font-bold">Edit Item</h2>
            <button class="close-btn" on:click={closeEditModal}>&times;</button>
          </div>
          <form on:submit|preventDefault={updateItem} class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
              <div class="current-image">
                <img 
                  src={editItem.image instanceof File 
                    ? URL.createObjectURL(editItem.image) 
                    : `http://localhost/POS/uploads/${editItem.image}`}
                  alt={editItem.name}
                  class="w-full h-48 object-cover rounded-md mb-2"
                  on:error={(e) => (e.currentTarget as HTMLImageElement).src = '/images/logo.png'}
                />
                <input 
                  type="file" 
                  accept="image/*" 
                  on:change={e => {
                    const input = e.target as HTMLInputElement;
                    const files = input.files;
                    if (files && files.length > 0) {
                      editItem.image = files[0] as File;
                    }
                  }} 
                  class="p-2 border rounded-md w-full"
                />
              </div>
              <input type="text" bind:value={editItem.name} placeholder="Item Name" class="p-2 border rounded-md" required />
              <input type="text" bind:value={editItem.price} placeholder="Price" class="p-2 border rounded-md" required />
              <select 
                bind:value={editItem.category} 
                on:change={(e) => handleCategoryChange(e.target.value)}
                class="p-2 border rounded-md"
                required 
              >
                {#each categories as category}
                  <option value={category}>{category}</option>
                {/each}
              </select>
              <select 
                bind:value={editItem.size} 
                class="p-2 border rounded-md"
                required
              >
                <option value="" disabled>Select size</option>
                {#each sizeOptions[editItem.category] as size}
                  <option value={size} selected={size === 'Standard'}>{size}</option>
                {/each}
              </select>
            </div>
            <div class="flex gap-2">
              <button type="submit" class="bg-[#d4a373] hover:bg-[#cba17f] text-[#faedcd] px-4 py-2 rounded-md flex-1 transition-colors">Update</button>
              <button type="button" on:click={closeEditModal} class="bg-[#d4a373] hover:bg-[#cba17f] text-[#faedcd] px-4 py-2 rounded-md flex-1 transition-colors">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    {/if}

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      {#each filterItems(items, searchQuery) as item}
        <div class="item-card">
          <img 
            src={`http://localhost/POS/uploads/${item.image}`}
            alt={item.name} 
            class="item-image" 
            on:error={(e) => (e.currentTarget as HTMLImageElement).src = '/images/logo.png'}
          />
          <div class="item-details">
            <h3 class="font-bold">{item.name}</h3>
            <p>₱{item.price}</p>
            <p class="text-gray-600">{item.category}</p>
            {#if item.size && item.size !== 'base-size'}
              <p class="text-sm text-gray-500">Size: {item.size}</p>
            {/if}
            <div class="item-actions">
              <button on:click={() => startEdit(item)} class="edit-btn">Edit</button>
              <button on:click={() => deleteItem(item.product_id)} class="delete-btn">Delete</button>
              <button
                on:click={() => openRecipeManager(item)}
                class="bg-blue-500 text-white px-2 py-1 rounded-md text-sm"
              >
                Manage Recipe
              </button>
            </div>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

{#if showRecipeManager && selectedProduct}
  <div class="modal-backdrop">
    <div class="modal-content max-w-2xl">
      <RecipeManager 
        productId={selectedProduct.product_id}
        productName={selectedProduct.name}
      />
      <button
        class="mt-4 bg-[#d4a373] hover:bg-[#cba17f] text-[#faedcd] px-4 py-2 rounded-md transition-colors"
        on:click={() => showRecipeManager = false}
      >
        Close
      </button>
    </div>
  </div>
{/if}

<style>
  .settings-container {
    width: 100%;
    min-height: 100vh;
    padding: 1rem;
    background-color: #faedcd;
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
    background-color: #d4a373;
    color: white;
  }

  .delete-btn {
    background-color: #f44336;
    color: white;
  }

  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  .modal-content {
  background: #faedcd;
  padding: 2rem;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  position: relative;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .close-btn {
    font-size: 1.5rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
  }

  .close-btn:hover {
    color: #666;
  }

  .current-image {
    border: 1px solid #e2e8f0;
    padding: 1rem;
    border-radius: 0.5rem;
  }

  .current-image img {
    border: 1px solid #e2e8f0;
  }

  .alert-container {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    width: 90%;
    max-width: 500px;
  }
</style>
