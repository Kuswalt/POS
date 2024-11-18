<script lang="ts">
  import SideNav from '$lib/sideNav.svelte';
  import Header from '$lib/header.svelte';
  import ItemCard from '$lib/itemCard.svelte';
  import Cart from '../cart/+page.svelte';
  import { onMount } from 'svelte';
  import { userStore } from '$lib/auth.js';
  import SizeSelectionModal from '$lib/sizeSelectionModal.svelte';

  type Product = {
    product_id: number;
    name: string;
    image: string;
    price: number;
    category: string;
    is_available: boolean;
    sizes?: Array<{
      size_id: number;
      size_name: string;
      price: string;
    }>;
  };
  type CartItem = {
    product_id: number;
    id: number;
    name: string;
    image: string;
    price: number;
    category: string;
    quantity: number;
    size_name?: string;
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
  let selectedProduct: Product | null;

  userStore.subscribe((user) => {
    if (!user.userId) {
        window.location.href = '/login';
        return;
    }
    userId = user.userId;
  });

  onMount(async () => {
    await fetchItems();
    await fetchCartItems();
  });

  async function fetchItems() {
    const response = await fetch('http://localhost/POS/api/routes.php?request=get-menu-items');
    const data = await response.json();
    products = data.map((p: any) => ({ 
        ...p,
        price: Number(p.price),
        product_id: p.product_id || p.id
    }));
  }

  async function fetchCartItems() {
    const response = await fetch(`http://localhost/POS/api/routes.php?request=get-cart-items&user_id=${userId}`);
    const result = await response.json();
    if (result.status) {
      cartItems = result.data;
    } else {
      console.error('Failed to fetch cart items:', result.message);
      cartItems = [];
    }
  }

  async function addToCart(product: Product) {
    if (!product.is_available) {
      alert('This item is currently unavailable');
      return;
    }

    selectedProduct = product;
    showSizeModal = true;
  }

  async function removeFromCart(productId: number) {
    try {
      const response = await fetch('http://localhost/POS/api/routes.php?request=remove-from-cart', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          user_id: userId,
          product_id: productId
        })
      });

      const result = await response.json();
      if (result.status) {
        cartItems = cartItems.filter(item => item.id !== productId);
        await fetchCartItems(); // Refresh cart from server
      } else {
        console.error('Failed to remove item from cart:', result.message);
      }
    } catch (error) {
      console.error('Error removing item from cart:', error);
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

  $: filteredProducts = products
    .filter(p => selectedCategory === 'All' || p.category === selectedCategory)
    .filter(p => p.name.toLowerCase().includes(searchQuery.toLowerCase()));

  $: categories = ['All', ...new Set(products.map(p => p.category))];
  $: total = getTotal();

  async function handleSizeSelect(sizeId: number, price: string) {
    if (!selectedProduct) return;

    try {
      const data = {
        product_id: selectedProduct.product_id,
        size_id: sizeId,
        quantity: 1,
        user_id: userId,
        price: price
      };

      const response = await fetch('http://localhost/POS/api/routes.php?request=add-to-cart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });

      const result = await response.json();
      if (result.status) {
        await fetchCartItems();
        showSizeModal = false;
      } else {
        alert(result.message);
      }
    } catch (error) {
      console.error('Error adding to cart:', error);
      alert('Failed to add item to cart');
    }
  }
</script>

<Header {y} {innerHeight} />
<div class="container">
  <div class="content">
    <SideNav activeMenu="pos" />
    
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

      <!-- Category Filter -->
      <div class="category-filter">
        {#each categories as category}
          <button 
            class="category-btn {selectedCategory === category ? 'active' : ''}"
            on:click={() => selectedCategory = category}
          >
            {category}
          </button>
        {/each}
      </div>

      <!-- Products Grid -->
      <div class="products-container">
        <div class="products-section">
          <div class="grid grid-cols-3 gap-4">
            {#each filteredProducts as product}
              <button 
                type="button" 
                class="product-card"
                on:click={() => addToCart(product)}
              >
                <ItemCard product={{
                  product_id: product.product_id,
                  name: product.name,
                  image: product.image,
                  price: product.price.toString(),
                  category: product.category,
                  is_available: product.is_available,
                  sizes: product.sizes || []
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
      on:cartCleared={() => {
        cartItems = [];
        fetchCartItems();
      }}
    />
  </div>
</div>

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
</style>

{#if showSizeModal && selectedProduct}
  <SizeSelectionModal
    show={showSizeModal}
    product={{
      ...selectedProduct,
      price: selectedProduct.price.toString()
    }}
    onSelect={handleSizeSelect}
    onClose={() => {
      showSizeModal = false;
    }}
  />
{/if}