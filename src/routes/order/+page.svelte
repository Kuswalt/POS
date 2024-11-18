<script lang="ts">
  import SideNav from '$lib/sideNav.svelte';
  import Header from '$lib/header.svelte';
  import ItemCard from '$lib/itemCard.svelte';
  import Cart from '../cart/+page.svelte';
  import { onMount } from 'svelte';
  import { userStore } from '$lib/auth';
  import { goto } from '$app/navigation';
  import { checkAuth } from '$lib/auth';

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

  userStore.subscribe(user => {
    userId = user.userId;
  });

  onMount(async () => {
    if (!checkAuth()) {
      goto('/');
    }
    await fetchItems();
  });

  async function fetchItems() {
    try {
        const response = await fetch('http://localhost/POS/api/routes.php?request=get-menu-items');
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

  async function checkInventoryStock(product: Product): Promise<boolean> {
    try {
        const response = await fetch(`http://localhost/POS/api/routes.php?request=get-product-ingredients&product_id=${product.product_id}`);
        const result = await response.json();
        
        if (!result.status || !result.data) {
            return false;
        }

        // Check if any ingredient is insufficient
        for (const ingredient of result.data) {
            if (ingredient.stock_quantity < ingredient.quantity_needed) {
                return false;
            }
        }
        
        return true;
    } catch (error) {
        console.error('Error checking inventory:', error);
        return false;
    }
  }

  async function addToCart(product: Product) {
    const hasStock = await checkInventoryStock(product);
    if (!hasStock) return;
    
    const existingItem = cartItems.find(item => item.product_id === product.product_id);
    
    try {
        const data = {
            product_id: product.product_id,
            quantity: 1,
            user_id: userId
        };
        console.log('Sending data:', data);

        const response = await fetch('http://localhost/POS/api/routes.php?request=add-to-cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        console.log('Response:', result);
        
        if (result.status) {
            if (existingItem) {
                cartItems = cartItems.map(item => 
                    item.product_id === product.product_id
                        ? { ...item, quantity: item.quantity + 1 }
                        : item
                );
            } else {
                const newItem = {
                    ...product,
                    id: product.product_id,
                    quantity: 1
                };
                cartItems = [...cartItems, newItem];
            }
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error adding item to cart:', error);
        alert('Failed to add item to cart');
    }
  }

  function removeFromCart(productId: number) {
    cartItems = cartItems.filter(item => item.product_id !== productId);
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
    const hasStock = await checkInventoryStock(variant);
    if (!hasStock) return;
    
    showSizeModal = false;
    await addToCart(variant);
    selectedProduct = null;
  }
</script>

<Header {y} {innerHeight} />
<div class="container">
  <div class="content">
    <SideNav 
      activeMenu="pos"
      bind:selectedCategory={selectedCategory}
    />
    
    <div class="main-content">
      <!-- Search Bar -->
      <div class="search-bar">
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Search products..."
          class="search-input"
        />
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

    <Cart 
      {cartItems} 
      {userId}
      onUpdateQuantity={updateQuantity}
      onRemoveFromCart={removeFromCart}
      {total}
    />
  </div>
</div>

{#if showSizeModal && selectedProduct}
  <div class="modal-backdrop">
    <div class="modal-content">
      <h2 class="text-xl font-bold mb-4">Select Size for {selectedProduct.name}</h2>
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

<style>
  .content {
    display: flex;
    margin-top: 4rem;
    height: calc(100vh - 4rem);
    overflow: hidden;
  }

  .main-content {
    flex: 1;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .products-container {
    flex: 1;
    overflow-y: auto;
    min-height: 0; /* Important for flex child scrolling */
    background: white;
    border-radius: 0.5rem;
    margin-top: 1rem;
  }

  .products-section {
    padding: 1rem;
  }

  .category-filter {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    overflow-x: auto;
    background: white;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
  }

  .category-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: #f3f4f6;
    white-space: nowrap;
  }

  .category-btn.active {
    background: #47cb50;
    color: white;
  }

  .product-card {
    cursor: pointer;
    transition: transform 0.2s;
  }

  .product-card:hover {
    transform: translateY(-2px);
  }

  .search-bar {
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
  }

  .search-input {
    width: 100%;
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 1rem;
  }

  .search-input:focus {
    outline: none;
    border-color: #47cb50;
    box-shadow: 0 0 0 2px rgba(71, 203, 80, 0.2);
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
    background: #f3f4f6;
    border-radius: 0.5rem;
    text-align: center;
    transition: background-color 0.2s;
  }

  .size-option:hover {
    background: #47cb50;
    color: white;
  }
</style>