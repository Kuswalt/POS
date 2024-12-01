<script lang="ts">
  import SideNav from '$lib/sideNav.svelte';
  import Header from '$lib/header.svelte';
  import ItemCard from '$lib/itemCard.svelte';
  import Cart from '../cart/+page.svelte';
  import { onMount } from 'svelte';
  import { userStore } from '$lib/auth';
  import { goto } from '$app/navigation';
  import { checkAuth } from '$lib/auth';
  import { productAvailability, availabilityLoading } from '$lib/stores/productAvailability';

  type Product = {
    product_id: number;
    name: string;
    image: string;
    price: number;
    category: string;
    size?: string;
    isAvailable?: boolean;
  };
  type CartItem = {
    product_id: number;
    id: number;
    name: string;
    image: string;
    price: number;
    category: string;
    quantity: number;
  };

  type GroupedProduct = {
    name: string;
    image: string;
    category: string;
    variants: Product[];
  };

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;
  let products: Product[] = [];
  let cartItems: CartItem[] = [];
  let selectedCategory = 'All';
  let searchQuery = '';
  let userId: number;
  let showSizeModal = false;
  let selectedProduct: GroupedProduct | null = null;
  let showMobileCart = false;

  userStore.subscribe(user => {
    userId = user.userId;
  });

  onMount(async () => {
    if (!checkAuth()) {
      goto('/');
    }
    await fetchItems();
    await fetchCartItems();
  });

  async function fetchItems() {
    try {
        const response = await fetch('/api/get-menu-items');
        const data = await response.json();
        
        if (Array.isArray(data)) {
            products = data.map((p: any) => ({ 
                ...p,
                price: Number(p.price),
                product_id: p.product_id || p.id,
                image: p.image || ''
            }));
        } else {
            console.error('Unexpected data format:', data);
            products = [];
        }
    } catch (error) {
        console.error('Error fetching items:', error);
        products = [];
    }
  }

  async function fetchCartItems() {
    try {
      const response = await fetch(`/api/get-cart-items?user_id=${userId}`);
      const result = await response.json();
      
      if (result.status && Array.isArray(result.data)) {
        cartItems = result.data.map((item: any) => ({
          product_id: item.product_id,
          id: item.product_id,
          name: item.name,
          price: Number(item.price),
          quantity: Number(item.quantity),
          image: item.image,
          category: item.category
        }));
      } else {
        cartItems = [];
      }
    } catch (error) {
      console.error('Error fetching cart items:', error);
      cartItems = [];
    }
  }

  async function checkInventoryStock(product: Product, quantity: number = 1): Promise<{ available: boolean; message: string }> {
    try {
      const response = await fetch(`/api/get-product-ingredients&product_id=${product.product_id}`);
      const result = await response.json();
      
      if (!result.status || !result.data || result.data.length === 0) {
        return { available: false, message: 'No Recipe Set' };
      }

      // Check if any ingredient is insufficient for the requested quantity
      for (const ingredient of result.data) {
        const requiredQuantity = ingredient.quantity_needed * quantity;
        if (ingredient.stock_quantity < requiredQuantity) {
          return { available: false, message: 'Insufficient Stock' };
        }
      }
      
      return { available: true, message: '' };
    } catch (error) {
      console.error('Error checking inventory:', error);
      return { available: false, message: 'Error' };
    }
  }

  async function addToCart(product: Product) {
    try {
        // First, get current cart quantity
        const response = await fetch(`/api/get-cart-item&product_id=${product.product_id}&user_id=${userId}`);
        const cartResult = await response.json();
        const currentQuantity = cartResult.status ? (cartResult.data?.quantity || 0) : 0;
        
        // Check stock for new total quantity
        const stockCheck = await checkInventoryStock(product, currentQuantity + 1);
        if (!stockCheck.available) {
            alert(stockCheck.message);
            return;
        }
        
        const data = {
            product_id: product.product_id,
            quantity: 1,
            user_id: userId
        };

        const addResponse = await fetch('/api/add-to-cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await addResponse.json();
        if (result.status) {
            await fetchCartItems();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add item to cart');
    }
  }

  async function removeFromCart(productId: number) {
    try {
        const response = await fetch('/api/remove-from-cart', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                user_id: userId
            })
        });

        const result = await response.json();
        if (result.status) {
            cartItems = cartItems.filter(item => item.product_id !== productId);
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error removing item from cart:', error);
        alert('Failed to remove item from cart');
    }
  }

  function updateQuantity(productId: number, newQuantity: number) {
    if (newQuantity < 1) {
      removeFromCart(productId);
      return;
    }
    cartItems = cartItems.map(item =>
      item.product_id === productId 
        ? { ...item, quantity: newQuantity }
        : item
    );
  }

  function getTotal() {
    return cartItems.reduce((sum, item) => sum + (Number(item.price) * item.quantity), 0);
  }

  $: groupedProducts = products.reduce((groups: { [key: string]: GroupedProduct }, product) => {
    const key = `${product.name}-${product.category}`;
    if (!groups[key]) {
      groups[key] = {
        name: product.name,
        image: product.image,
        category: product.category,
        variants: []
      };
    }
    groups[key].variants.push(product);
    return groups;
  }, {});

  $: filteredGroupedProducts = Object.values(groupedProducts)
    .filter(group => selectedCategory === 'All' || group.category === selectedCategory)
    .filter(group => group.name.toLowerCase().includes(searchQuery.toLowerCase()));

  $: categories = ['All', ...new Set(products.map(p => p.category))];
  $: total = getTotal();

  async function handleProductClick(group: GroupedProduct) {
    if (['Drinks', 'Pizza'].includes(group.category) && group.variants.length > 1) {
      selectedProduct = group;
      showSizeModal = true;
    } else {
      await addToCart(group.variants[0]);
    }
  }

  async function handleSizeSelection(variant: Product) {
    const stockCheck = await checkInventoryStock(variant);
    if (!stockCheck.available) {
        alert(stockCheck.message);
        return;
    }
    
    showSizeModal = false;
    await addToCart(variant);
    selectedProduct = null;
  }

  function toggleMobileCart() {
    showMobileCart = !showMobileCart;
  }

  async function checkBatchAvailability(products: Product[]) {
    try {
        availabilityLoading.set(true);
        const productIds = products.map(p => p.product_id);
        const response = await fetch(`/api/get-batch-product-ingredients&product_ids=${JSON.stringify(productIds)}`);
        const result = await response.json();
        
        if (result.status && result.data) {
            const availability: Record<number, boolean> = {};
            Object.entries(result.data).forEach(([productId, data]: [string, any]) => {
                availability[Number(productId)] = data.isAvailable;
            });
            productAvailability.set(availability);
        }
    } catch (error) {
        console.error('Error checking batch availability:', error);
    } finally {
        availabilityLoading.set(false);
    }
  }

  // Call this when products are loaded
  $: if (products.length > 0) {
    checkBatchAvailability(products);
  }
</script>

<Header {y} {innerHeight} />
<div class="container">
  <div class="content">
    <SideNav 
      activeMenu="pos"
      bind:selectedCategory={selectedCategory}
      onToggleCart={toggleMobileCart}
    />
    
    <div class="main-content">
      <!-- Search Bar -->
      <div class="search-controls">
        <div class="search-bar">
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Search products..."
            class="search-input"
          />
        </div>
        
        <!-- Category Labels -->
        <div class="category-tabs">
          {#each categories as category}
            <button 
              class="category-tab {selectedCategory === category ? 'active' : ''}"
              on:click={() => selectedCategory = category}
            >
              {category}
            </button>
          {/each}
        </div>
      </div>

      <!-- Products Grid -->
      <div class="products-container">
        <div class="products-section">
          <div class="grid grid-cols-3 gap-4">
            {#each filteredGroupedProducts as group}
              <button 
                type="button" 
                class="product-card"
                on:click={() => handleProductClick(group)}
              >
                <ItemCard product={{
                  product_id: group.variants[0].product_id,
                  name: group.name,
                  image: group.variants[0].image,
                  price: group.variants[0].price.toString(),
                  category: group.category
                }} />
              </button>
            {/each}
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop Cart -->
    <div class="cart-container hidden md:block">
      <Cart 
        {cartItems} 
        {userId}
        onUpdateQuantity={updateQuantity}
        onRemoveFromCart={removeFromCart}
        {total}
      />
    </div>
  </div>
</div>

{#if showSizeModal && selectedProduct}
  <div class="modal-backdrop" on:click={() => {
    showSizeModal = false;
    selectedProduct = null;
  }}>
    <div class="modal-content" on:click|stopPropagation>
      <div class="modal-header">
        <h2 class="text-xl font-bold">Select Size for {selectedProduct.name}</h2>
        <button 
          class="close-modal-btn"
          on:click={() => {
            showSizeModal = false;
            selectedProduct = null;
          }}
        >
          ×
        </button>
      </div>
      <div class="grid gap-4">
        {#each selectedProduct.variants as variant}
          <button
            class="size-option"
            on:click={() => handleSizeSelection(variant)}
          >
            {variant.size} - ₱{variant.price}
          </button>
        {/each}
      </div>
    </div>
  </div>
{/if}

<!-- Mobile Cart Modal -->
{#if showMobileCart}
  <div class="fixed inset-0 z-50 md:hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" on:click={toggleMobileCart}></div>
    <div class="mobile-cart-container">
      <div class="h-full overflow-y-auto">
        <div class="p-4 border-b">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Your Cart</h2>
            <button 
              class="text-gray-500 hover:text-gray-700"
              on:click={toggleMobileCart}
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <Cart 
          {cartItems} 
          {userId}
          onUpdateQuantity={updateQuantity}
          onRemoveFromCart={removeFromCart}
          {total}
        />
      </div>
    </div>
  </div>
{/if}

<style>
  .search-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .search-bar {
    width: 100%;
  }

  .search-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.2s;
  }

  .search-input:focus {
    outline: none;
    border-color: #d4a373;
    box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.1);
  }

  .category-tabs {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.5rem 0;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
    -webkit-overflow-scrolling: touch;
  }

  .category-tabs::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
  }

  .category-tab {
    padding: 0.5rem 1rem;
    background: #faedcd;
    border: none;
    border-radius: 0.5rem;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
    color: #4b5563;
  }

  .category-tab:hover {
    background: #d4a373;
    color: white;
  }

  .category-tab.active {
    background: #d4a373;
    color: white;
  }

  .content {
    display: flex;
    height: calc(100vh - 4rem);
    margin-top: 4rem;
    overflow: hidden;
  }

  .main-content {
    flex: 1;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color:  #fefae0;
    scrollbar-color: #fefae0 #fefae0;
    transition: margin-right 0.3s ease;
  }

  .main-content::-webkit-scrollbar {
    width: 8px;
  }

  .main-content::-webkit-scrollbar-track {
    background: #faedcd;
  }

  .main-content::-webkit-scrollbar-thumb {
    background: #fefae0;
    border-radius: 4px;
  }

  .products-container {
    flex: 1;
    overflow-y: auto;
    min-height: 0;
    scrollbar-width: thin; /* Firefox */
    scrollbar-color: #fefae0 #fefae0; /* Firefox */
  }

  .products-container::-webkit-scrollbar {
    width: 8px; /* Chrome, Safari, Opera */
  }

  .products-container::-webkit-scrollbar-track {
    background: #faedcd;
  }

  .products-container::-webkit-scrollbar-thumb {
    background: #d4a373;
    border-radius: 4px;
  }

  .products-section {
    padding: 1rem;
  }




  .product-card {
    cursor: pointer;
    transition: transform 0.2s;
  }

  .product-card:hover {
    transform: translateY(-2px);
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
    border-radius: 0.5rem;
    width: 90%;
    max-width: 400px;
  }

  .size-option {
    width: 100%;
    padding: 1rem;
    background: #faedcd;
    border-radius: 0.5rem;
    text-align: center;
    transition: background-color 0.2s;
  }

  .size-option:hover {
    background: #d4a373;
    color: white;
  }

  .container {
    width: 100%;
    height: 100vh;
    background-color: #fefae0;
    overflow: hidden;
  }

  .content {
    display: flex;
    height: calc(100vh - 4rem);
    margin-top: 4rem;
    overflow: hidden;
  }

  .main-content {
    flex: 1;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #fefae0 #fefae0;
    margin-top: 20px;
  }

  /* Responsive grid for products */
  :global(.grid-cols-3) {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1rem;
  }

  @media (max-width: 768px) {
    .content {
      margin-top: 3rem;
    }

    .main-content {
      padding: 0.5rem;
    }

    :global(.grid-cols-3) {
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 0.75rem;
      padding: 0.5rem;
    }

    .category-tabs {
      margin: 0 -0.5rem;
      padding: 0.5rem;
    }
  }

  .mobile-cart-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 400px;
    max-height: 90vh;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  @media (max-width: 768px) {
    .mobile-cart-container {
      width: 95%;
      max-height: 80vh;
    }
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .close-modal-btn {
    font-size: 1.5rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #666;
    transition: color 0.2s;
  }

  .close-modal-btn:hover {
    color: #000;
  }

  .cart-container {
    position: fixed;
    right: 0;
    top: 4rem;
    width: 400px;
    height: calc(100vh - 4rem);
    background: #fefae0;
    border-left: 1px solid #faedcd;
    overflow-y: auto;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    transition: width 0.3s ease;
  }

  /* Desktop layout */
  @media (min-width: 1401px) {
    .main-content {
      margin-right: 100px;
    }
  }

  /* Large tablets and small desktops */
  @media (max-width: 1400px) and (min-width: 1024px) {
    .cart-container {
      width: 350px;
    }
    .main-content {
      margin-right: 100px;
    }
  }

  /* Tablets */
  @media (max-width: 1023px) and (min-width: 768px) {
    .cart-container {
      width: 300px;
    }
    .main-content {
      margin-right: 200px;
    }
  }

  /* Mobile devices */
  @media (max-width: 767px) {
    .cart-container {
      display: none;
    }
    .main-content {
      margin-right: 0;
      padding: 0.5rem;
    }
    .cart-container.mobile-visible {
      display: block;
      width: 100%;
      z-index: 50;
    }
  }

  /* Responsive grid for products */
  :global(.grid-cols-3) {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1rem;
  }

  @media (max-width: 768px) {
    :global(.grid-cols-3) {
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 0.75rem;
      padding: 0.5rem;
    }
  }
</style>