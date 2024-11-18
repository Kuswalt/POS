<script lang="ts">
  import { onMount } from 'svelte';
  import Header from '$lib/header.svelte';
  import SideNav from '$lib/sideNav.svelte';

  let y = 0;
  let innerHeight = 0;
  let activeView = 'menu'; // 'menu' or 'ingredients'

  // Define types for items and newItem
  type Size = {
    size_id?: number;
    size_name: string;
    price: string;
    ingredients: {
      inventory_id: string;
      quantity_needed?: number;
      unit_of_measure: string;
    }[];
  };

  type Item = {
    product_id: number;
    name: string;
    image: string | File;
    price: string;
    category: string;
    imageUrl?: string;
    sizes: Size[];
  };

  type NewItem = { name: string; image: File; price: string; category: string };

  let items: Item[] = [];
  const categories = ['Pizza', 'Burger & Fries', 'Nachos', 'Fruit Soda', 'Yakult Mix', 'Iced Coffee',
   'Frappe Non-Coffee','Frappe Coffee Base', 'Classic Milk Tea', 'Chocolate Series', 'Cheesecake Series']; // Add your categories here
  let newItem: NewItem = { name: '', image: new File([], ''), price: '', category: categories[0] };
  let editItem: Item; // Change the type declaration to allow null
  let isEditModalOpen = false;
  let searchQuery = '';

  type InventoryItem = { inventory_id: string; item_name: string };
  type Ingredient = {
    inventory_id: string;
    quantity_needed?: number;
    unit_of_measure: string;
    item_name?: string;
  };

  let selectedProduct: number | null = null;
  let selectedIngredients: Ingredient[] = [];
  let inventoryItems: InventoryItem[] = [];

  let ingredientSearchQuery = '';
  let selectedCategory = 'All';

  let isIngredientModalOpen = false;
  let currentEditingProduct: Item | null = null;

  let selectedIngredientId = '';
  let newIngredientQuantity = 0;
  let newIngredientUnit = 'grams';

  let sizes: Size[] = [];
  let showSizeManagement = false;
  let selectedSize: Size | null = null;

  let editingIngredientIndex: number | null = null;
  let editIngredientQuantity: number = 0;
  let editIngredientUnit: string = 'grams';

  async function fetchInventoryItems() {
    const response = await fetch('http://localhost/POS/api/routes.php?request=get-items');
    const result = await response.json();
    if (result.status) {
      inventoryItems = result.data;
    }
  }

  async function fetchProductIngredients(productId: number) {
    const response = await fetch(`http://localhost/POS/api/routes.php?request=get-product-ingredients&product_id=${productId}`);
    const result = await response.json();
    if (result.status) {
      selectedIngredients = result.data || [];
    }
  }

  function addIngredient() {
    if (!selectedIngredientId || newIngredientQuantity <= 0) {
      alert('Please select an ingredient and enter a valid quantity');
      return;
    }

    selectedIngredients = [...selectedIngredients, {
      inventory_id: selectedIngredientId,
      quantity_needed: newIngredientQuantity,
      unit_of_measure: newIngredientUnit
    }];

    // Reset input fields
    selectedIngredientId = '';
    newIngredientQuantity = 0;
  }

  function removeIngredient(index: number) {
    selectedIngredients = selectedIngredients.filter((_, i) => i !== index);
  }

  async function saveIngredients() {
    if (!selectedProduct) {
        alert('Please ensure product is selected');
        return;
    }

    // Different endpoints and data structure for base recipe vs size-specific ingredients
    const endpoint = selectedSize?.size_id 
        ? 'add-size-ingredients'
        : 'add-product-ingredients';

    const requestBody = selectedSize?.size_id 
        ? {
            size_id: selectedSize.size_id,
            ingredients: selectedSize.ingredients
        }
        : {
            product_id: selectedProduct,
            ingredients: selectedIngredients
        };

    const response = await fetch(`http://localhost/POS/api/routes.php?request=${endpoint}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestBody)
    });

    const result = await response.json();
    if (result.status) {
        alert('Ingredients saved successfully');
    } else {
        alert('Failed to save ingredients: ' + result.message);
    }
  }

  $: if (selectedProduct) {
    fetchProductIngredients(selectedProduct);
  }

  onMount(async () => {
    await fetchItems();
    await fetchInventoryItems();
  });

  async function fetchItems() {
    const response = await fetch('http://localhost/POS/api/routes.php?request=get-menu-items');
    const data = await response.json();
    items = data.map((item: Item) => ({
      ...item,
      imageUrl: item.image ? `/POS/uploads/${item.image}` : 'placeholder.jpg'
    }));
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
    formData.append('sizes', JSON.stringify(sizes));

    const response = await fetch('http://localhost/POS/api/routes.php?request=add-menu-item', {
      method: 'POST',
      body: formData,
    });
    const result = await response.json();
    if (result.status) {
      await fetchItems(); // Refresh the items list
      newItem = { name: '', image: new File([], ''), price: '', category: categories[0] }; // Reset form
    } else {
      alert(result.message);
    }
  }

  async function updateItem() {
    if (!currentEditingProduct) return;

    const formData = new FormData();
    formData.append('product_id', currentEditingProduct.product_id.toString());
    formData.append('name', currentEditingProduct.name);
    formData.append('price', currentEditingProduct.price.toString());
    formData.append('category', currentEditingProduct.category);
    
    if (currentEditingProduct.image instanceof File) {
      formData.append('image', currentEditingProduct.image);
    }

    const response = await fetch('http://localhost/POS/api/routes.php?request=update-menu-item', {
      method: 'POST',
      body: formData,
    });
    
    const result = await response.json();
    if (result.status) {
      await fetchItems();
      isEditModalOpen = false;
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

  async function startEdit(item: Item) {
    currentEditingProduct = { ...item };
    showSizeManagement = false;
    
    try {
      await fetchProductSizes(item.product_id);
      isEditModalOpen = true;
    } catch (error) {
      console.error('Error fetching sizes:', error);
      sizes = [];
      isEditModalOpen = true;
    }
  }

  function closeEditModal() {
    isEditModalOpen = false;
    if (!showSizeManagement) {
      currentEditingProduct = null;
    }
  }

  function filterItems(items: Item[], query: string) {
    const lowerQuery = query.toLowerCase();
    return items.filter(item => 
      item.name.toLowerCase().includes(lowerQuery) || 
      item.category.toLowerCase().includes(lowerQuery)
    );
  }

  function filterProducts(items: Item[], query: string, category: string) {
    return items.filter(item => {
      const matchesSearch = item.name.toLowerCase().includes(query.toLowerCase());
      const matchesCategory = category === 'All' || item.category === category;
      return matchesSearch && matchesCategory;
    });
  }

  async function openIngredientModal(product: Item) {
    currentEditingProduct = product;
    selectedProduct = product.product_id;
    selectedSize = { size_name: 'Base Recipe', price: '0', ingredients: [] }; // Set base recipe as default
    selectedIngredients = []; // Reset selected ingredients
    
    try {
        const sizesResponse = await fetch(`http://localhost/POS/api/routes.php?request=get-product-sizes&product_id=${product.product_id}`);
        const sizesResult = await sizesResponse.json();
        
        if (sizesResult.status) {
            sizes = [
                { size_id: 0, size_name: 'Base Recipe', price: '0', ingredients: [] },
                ...sizesResult.data.map((size: any) => ({
                    size_id: size.size_id,
                    size_name: size.size_name,
                    price: size.price,
                    ingredients: []
                }))
            ];
        }

        // Fetch base recipe ingredients immediately
        const response = await fetch(`http://localhost/POS/api/routes.php?request=get-product-ingredients&product_id=${selectedProduct}`);
        const result = await response.json();
        if (result.status) {
            selectedIngredients = result.data || [];
        }

        isIngredientModalOpen = true;
    } catch (error) {
        console.error('Error fetching product data:', error);
        isIngredientModalOpen = true;
    }
  }

  function addSize() {
    sizes = [...sizes, { size_name: '', price: '', ingredients: [] }];
  }

  async function removeSize(index: number) {
    if (!currentEditingProduct) return;
    
    const sizeToRemove = sizes[index];
    if (!sizeToRemove.size_id) {
      // If it's a new size that hasn't been saved yet
      sizes = sizes.filter((_, i) => i !== index);
      return;
    }

    try {
      const response = await fetch('http://localhost/POS/api/routes.php?request=delete-product-size', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          size_id: sizeToRemove.size_id
        })
      });

      const result = await response.json();
      if (result.status) {
        sizes = sizes.filter((_, i) => i !== index);
      } else {
        alert('Failed to delete size: ' + result.message);
      }
    } catch (error) {
      console.error('Error deleting size:', error);
      alert('Error deleting size');
    }
  }

  async function saveSizes() {
    try {
      const response = await fetch('http://localhost/POS/api/routes.php?request=add-product-sizes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          product_id: currentEditingProduct?.product_id,
          sizes
        })
      });

      const result = await response.json();
      if (result.status) {
        alert('Sizes saved successfully');
        showSizeManagement = false;
        await fetchItems();
        if (currentEditingProduct) {
          await fetchProductSizes(currentEditingProduct.product_id);
        }
      } else {
        alert('Failed to save sizes');
      }
    } catch (error) {
      console.error('Error saving sizes:', error);
      alert('Error saving sizes');
    }
  }

  async function fetchProductSizes(productId: number) {
    const response = await fetch(`http://localhost/POS/api/routes.php?request=get-product-sizes&product_id=${productId}`);
    const result = await response.json();
    if (result.status) {
      sizes = result.data.sizes.map((size: any) => ({
        ...size,
        ingredients: size.ingredients || []
      }));
    }
  }

  function addIngredientToSize() {
    if (!selectedSize || !selectedIngredientId || newIngredientQuantity <= 0) {
      alert('Please select a size, ingredient and enter a valid quantity');
      return;
    }

    const newIngredient = {
      inventory_id: selectedIngredientId,
      quantity_needed: newIngredientQuantity,
      unit_of_measure: newIngredientUnit
    };

    selectedSize.ingredients = [...(selectedSize.ingredients || []), newIngredient];
    sizes = [...sizes]; // Trigger reactivity

    // Reset input fields
    selectedIngredientId = '';
    newIngredientQuantity = 0;
  }

  function removeIngredientFromSize(index: number) {
    if (selectedSize) {
      selectedSize.ingredients = selectedSize.ingredients.filter((_, i) => i !== index);
      sizes = [...sizes];
    }
  }

  function editIngredient(index: number) {
    const ingredient = selectedIngredients[index];
    editingIngredientIndex = index;
    editIngredientQuantity = ingredient.quantity_needed || 0;
    editIngredientUnit = ingredient.unit_of_measure;
    selectedIngredientId = ingredient.inventory_id;
  }

  function saveEditedIngredient() {
    if (editingIngredientIndex === null || !selectedIngredientId || editIngredientQuantity <= 0) {
      alert('Please ensure all fields are filled correctly');
      return;
    }

    selectedIngredients = selectedIngredients.map((ingredient, index) => 
      index === editingIngredientIndex 
        ? {
            inventory_id: selectedIngredientId,
            quantity_needed: editIngredientQuantity,
            unit_of_measure: editIngredientUnit
          }
        : ingredient
    );

    // Reset edit state
    editingIngredientIndex = null;
    editIngredientQuantity = 0;
    editIngredientUnit = 'grams';
    selectedIngredientId = '';
  }
</script>

<Header {y} {innerHeight} />
<div class="container">
  <div class="content">
    <!-- <SideNav activeMenu="settings" /> -->
    
    <div class="main-content">
      <div class="settings-nav">
        <button 
          class="nav-btn {activeView === 'menu' ? 'active' : ''}" 
          on:click={() => activeView = 'menu'}
        >
          Product Menu
        </button>
        <button 
          class="nav-btn {activeView === 'ingredients' ? 'active' : ''}" 
          on:click={() => activeView = 'ingredients'}
        >
          Product Ingredients
        </button>
      </div>

      {#if activeView === 'menu'}
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
                </div>
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md w-full">Add Item</button>
              </form>
            </div>

            <div class="mb-6">
              <input 
                type="text" 
                bind:value={searchQuery}
                placeholder="Search items by name or category..." 
                class="p-2 border rounded-md w-full"
              />
            </div>

            {#if isEditModalOpen && currentEditingProduct}
              <div class="modal-backdrop">
                <div class="modal-content">
                  <div class="modal-header">
                    <h2>Edit {currentEditingProduct.name}</h2>
                    <button class="close-btn" on:click={() => isEditModalOpen = false}>&times;</button>
                  </div>
                  <div class="modal-body">
                    <form on:submit|preventDefault={updateItem}>
                      <div class="form-group">
                        <label>Name</label>
                        <input 
                          type="text" 
                          bind:value={currentEditingProduct.name} 
                          class="input"
                          required
                        />
                      </div>
                      
                      <div class="form-group">
                        <label>Price</label>
                        <input 
                          type="number" 
                          bind:value={currentEditingProduct.price} 
                          class="input"
                          required
                        />
                      </div>

                      <div class="form-group">
                        <label>Category</label>
                        <select 
                          bind:value={currentEditingProduct.category} 
                          class="select"
                          required
                        >
                          {#each categories as category}
                            <option value={category}>{category}</option>
                          {/each}
                        </select>
                      </div>

                      <!-- Size Management Section -->
                      <div class="size-section">
                        <h3>Size Management</h3>
                        <button 
                          type="button"
                          class="btn-secondary"
                          on:click={() => showSizeManagement = !showSizeManagement}
                        >
                          {showSizeManagement ? 'Hide Sizes' : 'Manage Sizes'}
                        </button>

                        {#if showSizeManagement}
                          <div class="sizes-container">
                            <!-- Display existing sizes -->
                            {#if sizes.length > 0}
                              <div class="existing-sizes mb-4">
                                <h4>Current Sizes:</h4>
                                {#each sizes as size}
                                  <div class="size-display">
                                    <span>{size.size_name}</span>
                                    <span>₱{size.price}</span>
                                  </div>
                                {/each}
                              </div>
                            {/if}

                            <button type="button" class="btn-primary" on:click={addSize}>
                              Add New Size
                            </button>

                            {#each sizes as size, index}
                              <div class="size-item">
                                <input
                                  type="text"
                                  placeholder="Size name"
                                  bind:value={size.size_name}
                                  class="input"
                                />
                                <input
                                  type="number"
                                  placeholder="Price"
                                  bind:value={size.price}
                                  class="input"
                                />
                                <button 
                                  type="button"
                                  class="btn-danger"
                                  on:click={() => removeSize(index)}
                                >
                                  Remove
                                </button>
                              </div>
                            {/each}

                            {#if sizes.length > 0}
                              <button type="button" class="btn-success mt-4" on:click={saveSizes}>
                                Save Sizes
                              </button>
                            {/if}
                          </div>
                        {/if}
                      </div>

                      <div class="button-group">
                        <button type="submit" class="btn-success">Save Changes</button>
                        <button 
                          type="button" 
                          class="btn-secondary"
                          on:click={() => isEditModalOpen = false}
                        >
                          Cancel
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            {/if}

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {#each filterItems(items, searchQuery) as item}
                <div class="item-card">
                  <img 
                    src={`/POS/uploads/${item.image}`}
                    alt={item.name} 
                    class="item-image" 
                    on:error={(e) => (e.currentTarget as HTMLImageElement).src = '/images/logo.png'}
                  />
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
      {:else}
        <div class="settings-container">
          <div class="settings-content">
            <h1 class="text-2xl font-bold mb-6">Manage Product Ingredients</h1>
            
            <div class="mb-6 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input 
                  type="text"
                  bind:value={ingredientSearchQuery}
                  placeholder="Search products..."
                  class="p-2 border rounded-md w-full"
                />
                <select 
                  bind:value={selectedCategory}
                  class="p-2 border rounded-md w-full"
                >
                  <option value="All">All Categories</option>
                  {#each categories as category}
                    <option value={category}>{category}</option>
                  {/each}
                </select>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {#each filterProducts(items, ingredientSearchQuery, selectedCategory) as product}
                  <div class="item-card">
                    <img 
                      src={`/POS/uploads/${product.image}`}
                      alt={product.name} 
                      class="item-image" 
                      on:error={(e) => (e.currentTarget as HTMLImageElement).src = '/images/logo.png'}
                    />
                    <div class="item-details">
                      <h3 class="font-bold">{product.name}</h3>
                      <p>₱{product.price}</p>
                      <p class="text-gray-600">{product.category}</p>
                      <button 
                        on:click={() => openIngredientModal(product)} 
                        class="mt-2 w-full bg-blue-500 text-white px-4 py-2 rounded-md"
                      >
                        Edit Ingredients
                      </button>
                    </div>
                  </div>
                {/each}
              </div>
            </div>

            {#if isIngredientModalOpen && currentEditingProduct}
              <div class="modal-backdrop">
                <div class="modal-content max-w-3xl">
                  <div class="modal-header">
                    <h2 class="text-xl font-bold">Manage Ingredients for {currentEditingProduct.name}</h2>
                    <button class="close-btn" on:click={() => isIngredientModalOpen = false}>&times;</button>
                  </div>

                  <div class="modal-body">
                    <!-- Size Selection Dropdown -->
                    <div class="form-group mb-4">
                      <label class="block text-sm font-medium mb-2">Select Size</label>
                      <select 
                        class="w-full p-2 border rounded-md"
                        bind:value={selectedSize}
                        on:change={async () => {
                            if (selectedSize?.size_id) {
                                // Fetch size-specific ingredients
                                const response = await fetch(`http://localhost/POS/api/routes.php?request=get-size-ingredients&size_id=${selectedSize.size_id}`);
                                const result = await response.json();
                                if (result.status) {
                                    selectedSize.ingredients = result.data || [];
                                    sizes = [...sizes]; // Trigger reactivity
                                }
                            } else {
                                // Fetch base recipe ingredients
                                const response = await fetch(`http://localhost/POS/api/routes.php?request=get-product-ingredients&product_id=${selectedProduct}`);
                                const result = await response.json();
                                if (result.status) {
                                    selectedIngredients = result.data.map((ing: { 
                                        inventory_id: string; 
                                        quantity_needed: number; 
                                        unit_of_measure: string; 
                                        item_name: string;
                                    }) => ({
                                        inventory_id: ing.inventory_id,
                                        quantity_needed: ing.quantity_needed,
                                        unit_of_measure: ing.unit_of_measure,
                                        item_name: ing.item_name
                                    }));
                                }
                            }
                        }}
                      >
                        {#each sizes as size}
                            <option value={size}>
                                {size.size_name}
                            </option>
                        {/each}
                      </select>
                    </div>

                    <!-- Ingredient Management Section -->
                    <div class="ingredient-section">
                      <h3 class="text-lg font-semibold mb-4">
                        {selectedSize ? `Ingredients for ${selectedSize.size_name}` : 'Base Recipe Ingredients'}
                      </h3>

                      <!-- Add Ingredient Form -->
                      {#if selectedSize}
                        <div class="grid grid-cols-4 gap-2 mb-4">
                          <select 
                            class="col-span-2 p-2 border rounded-md"
                            bind:value={selectedIngredientId}
                          >
                            <option value="">Select ingredient</option>
                            {#each inventoryItems as item}
                              <option value={item.inventory_id}>{item.item_name}</option>
                            {/each}
                          </select>
                          
                          <input 
                            type="number" 
                            placeholder="Quantity"
                            class="p-2 border rounded-md"
                            bind:value={newIngredientQuantity}
                          />
                          
                          <select 
                            class="p-2 border rounded-md"
                            bind:value={newIngredientUnit}
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

                          <button 
                            class="bg-green-500 text-white px-4 py-2 rounded-md col-span-4"
                            on:click={addIngredientToSize}
                          >
                            Add Ingredient to {selectedSize.size_name}
                          </button>
                        </div>

                        <!-- Size-specific Ingredients List -->
                        <div class="ingredients-list space-y-2">
                          {#if selectedSize.ingredients && selectedSize.ingredients.length > 0}
                            {#each selectedSize.ingredients as ingredient, index}
                              <div class="flex justify-between items-center p-2 bg-gray-50 rounded-md">
                                <span>
                                  {inventoryItems.find(item => item.inventory_id === ingredient.inventory_id)?.item_name} - {ingredient.quantity_needed} {ingredient.unit_of_measure}
                                </span>
                                <button 
                                  class="text-red-500"
                                  on:click={() => removeIngredientFromSize(index)}
                                >
                                  Remove
                                </button>
                              </div>
                            {/each}
                          {:else}
                            <p class="text-gray-500">No ingredients added for {selectedSize.size_name} yet.</p>
                          {/if}

                          <button 
                            class="bg-green-500 text-white px-4 py-2 rounded-md mt-4 w-full"
                            on:click={saveIngredients}
                          >
                            Save {selectedSize.size_name} Ingredients
                          </button>
                        </div>

                      {:else}
                        <!-- Base Recipe Ingredient Form -->
                        <div class="base-recipe-ingredients">
                          <h3 class="text-lg font-semibold mb-4">Base Recipe Ingredients</h3>
                          
                          <!-- Display current base recipe ingredients -->
                          <div class="ingredients-list space-y-2">
                            {#if selectedIngredients.length > 0}
                              {#each selectedIngredients as ingredient, index}
                                <div class="flex justify-between items-center p-2 bg-gray-50 rounded-md">
                                  <span>
                                    {#if ingredient.item_name}
                                      {ingredient.item_name}
                                    {:else}
                                      {inventoryItems.find(item => item.inventory_id === ingredient.inventory_id)?.item_name}
                                    {/if}
                                    - {ingredient.quantity_needed} {ingredient.unit_of_measure}
                                  </span>
                                  <div class="ingredient-actions">
                                    <button 
                                      class="edit-btn"
                                      on:click={() => editIngredient(index)}
                                    >
                                      Edit
                                    </button>
                                    <button 
                                      class="delete-btn"
                                      on:click={() => removeIngredient(index)}
                                    >
                                      Remove
                                    </button>
                                  </div>
                                </div>
                              {/each}
                            {:else}
                              <p class="text-gray-500">No ingredients added to base recipe yet.</p>
                            {/if}
                          </div>

                          <!-- Add new ingredient form -->
                          <div class="add-ingredient-form grid grid-cols-4 gap-2 mt-4">
                            <select 
                              class="col-span-2 p-2 border rounded-md"
                              bind:value={selectedIngredientId}
                            >
                              <option value="">Select ingredient</option>
                              {#each inventoryItems as item}
                                <option value={item.inventory_id}>{item.item_name}</option>
                              {/each}
                            </select>
                            
                            <input 
                              type="number" 
                              placeholder="Quantity"
                              class="p-2 border rounded-md"
                              bind:value={newIngredientQuantity}
                            />
                            
                            <select 
                              class="p-2 border rounded-md"
                              bind:value={newIngredientUnit}
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

                            <button 
                              class="bg-green-500 text-white px-4 py-2 rounded-md col-span-4"
                              on:click={addIngredient}
                            >
                              Add Ingredient to Base Recipe
                            </button>
                          </div>
                        </div>
                      {/if}
                    </div>
                  </div>
                </div>
              </div>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  .container {
    width: 100%;
    min-height: 100vh;
    background-color: #f5f5f5;
  }

  .content {
    display: flex;
    padding-top: 60px;
  }

  .main-content {
    flex: 1;
    padding: 20px;
    
  }

  .settings-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    padding: 10px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .nav-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    background-color: #f0f0f0;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .nav-btn.active {
    background-color: #4CAF50;
    color: white;
  }

  .product-ingredients {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .ingredient-btn {
    display: inline-block;
    margin-top: 15px;
    padding: 12px 24px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s ease;
  }

  .ingredient-btn:hover {
    background-color: #45a049;
  }

  h2 {
    margin: 0 0 20px 0;
    color: #333;
  }

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
    background: white;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    position: relative;
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

  .modal-content.max-w-3xl {
    max-width: 48rem;
  }

  .ingredient-list {
    max-height: 300px;
    overflow-y: auto;
    margin: 10px 0;
  }

  .ingredient-item {
    display: flex;
    align-items: center;
    padding: 10px;
    background: #f9f9f9;
    margin: 5px 0;
    border-radius: 4px;
    justify-content: space-between;
  }

  .ingredient-search {
    margin-bottom: 15px;
    width: 100%;
  }

  .ingredient-details {
    display: flex;
    gap: 10px;
    align-items: center;
    flex: 1;
  }

  .ingredient-actions {
    display: flex;
    gap: 5px;
  }

  .size-management {
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
  }

  .sizes-container {
    max-height: 400px;
    overflow-y: auto;
    padding: 1rem;
  }

  .size-item {
    background: #f9fafb;
  }

  .ingredient-management {
    margin-top: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
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
    padding: 2rem;
    border-radius: 0.5rem;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .input, .select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 0.25rem;
    margin-top: 0.25rem;
  }

  .size-section {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #ddd;
  }

  .sizes-container {
    margin-top: 1rem;
  }

  .size-item {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background: #f9fafb;
    border-radius: 0.25rem;
  }

  .button-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
  }

  .btn-secondary {
    background: #6b7280;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
  }

  .btn-success {
    background: #10b981;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
  }

  .btn-danger {
    background: #ef4444;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
  }

  .size-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 0.5rem;
  }

  .tab-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: #f3f4f6;
    border: none;
    cursor: pointer;
  }

  .tab-btn.active {
    background: #47cb50;
    color: white;
  }

  .ingredient-section {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
  }

  .size-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #e5e7eb;
  }

  .size-item {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    margin: 1rem 0;
    align-items: center;
  }

  .input {
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
  }

  .btn-danger {
    background-color: #ef4444;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
  }

  .btn-success {
    background-color: #10b981;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    width: 100%;
  }

  .current-sizes {
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 1rem;
  }

  .size-display {
    border: 1px solid #e5e7eb;
  }

  .size-item {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: center;
  }

  .existing-sizes {
    margin: 1rem 0;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
  }

  .size-display {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    margin: 0.5rem 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
  }

  .size-item {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    margin: 1rem 0;
    align-items: center;
  }

  .ingredients-list {
    margin-top: 1rem;
  }

  .ingredient-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #eee;
  }

  .ingredient-actions {
    display: flex;
    gap: 0.5rem;
  }

  .edit-ingredient-form {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .edit-ingredient-form select,
  .edit-ingredient-form input {
    margin: 0.5rem 0;
    padding: 0.5rem;
    width: 100%;
  }

  .button-group {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
  }
</style>
