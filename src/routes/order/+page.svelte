<script lang="ts">
  import SideNav from '$lib/sideNav.svelte';
  import Header from '$lib/header.svelte';
  import ItemCard from '$lib/itemCard.svelte';
  import { onMount } from 'svelte';

  type Product = { id: number; name: string; image: string; price: string; category: string };
  type CartItem = Product & { quantity: number };

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;
  let products: Product[] = [];
  let cartItems: CartItem[] = [];
  let selectedCategory = 'All';

  onMount(async () => {
    await fetchItems();
  });

  async function fetchItems() {
    const response = await fetch('http://localhost/POS/api/routes.php?request=get-menu-items');
    products = await response.json();
  }

  function addToCart(product: Product) {
    const existingItem = cartItems.find(item => item.id === product.id);
    if (existingItem) {
      cartItems = cartItems.map(item =>
        item.id === product.id 
          ? { ...item, quantity: item.quantity + 1 }
          : item
      );
    } else {
      cartItems = [...cartItems, { ...product, quantity: 1 }];
    }
  }

  function removeFromCart(productId: number) {
    cartItems = cartItems.filter(item => item.id !== productId);
  }

  function updateQuantity(productId: number, newQuantity: number) {
    if (newQuantity < 1) {
      removeFromCart(productId);
      return;
    }
    cartItems = cartItems.map(item =>
      item.id === productId 
        ? { ...item, quantity: newQuantity }
        : item
    );
  }

  function getTotal() {
    return cartItems.reduce((sum, item) => sum + (Number(item.price) * item.quantity), 0);
  }

  $: filteredProducts = selectedCategory === 'All' 
    ? products 
    : products.filter(p => p.category === selectedCategory);

  $: categories = ['All', ...new Set(products.map(p => p.category))];
</script>

<Header {y} {innerHeight} />
<div class="container">
  <div class="content">
    <SideNav activeMenu="pos" />
    
    <div class="main-content">
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
      <div class="products-section">
        <div class="grid grid-cols-3 gap-4">
          {#each filteredProducts as product}
            <div class="product-card" on:click={() => addToCart(product)}>
              <ItemCard {product} />
            </div>
          {/each}
        </div>
      </div>
    </div>

    <!-- Cart Section -->
    <div class="cart-section">
      <h2 class="cart-title">Order Summary</h2>
      <div class="cart-items">
        {#each cartItems as item}
          <div class="cart-item">
            <img src={item.image ? `uploads/${item.image}` : 'placeholder.jpg'} alt={item.name} class="cart-item-image" />
            <div class="cart-item-details">
              <h3>{item.name}</h3>
              <p>₱{item.price}</p>
              <div class="quantity-controls">
                <button on:click={() => updateQuantity(item.id, item.quantity - 1)}>-</button>
                <span>{item.quantity}</span>
                <button on:click={() => updateQuantity(item.id, item.quantity + 1)}>+</button>
              </div>
            </div>
            <button class="remove-btn" on:click={() => removeFromCart(item.id)}>×</button>
          </div>
        {/each}
      </div>
      <div class="cart-total">
        <h3>Total: ₱{getTotal().toFixed(2)}</h3>
        <button class="checkout-btn">Proceed to Checkout</button>
      </div>
    </div>
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
    overflow-y: auto;
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

  .products-section {
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
  }

  .product-card {
    cursor: pointer;
    transition: transform 0.2s;
  }

  .product-card:hover {
    transform: translateY(-2px);
  }

  .cart-section {
    width: 400px;
    background: white;
    padding: 1rem;
    border-left: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
  }

  .cart-title {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
  }

  .cart-items {
    flex: 1;
    overflow-y: auto;
  }

  .cart-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    gap: 1rem;
  }

  .cart-item-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 0.25rem;
  }

  .cart-item-details {
    flex: 1;
  }

  .quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .quantity-controls button {
    padding: 0.5rem;
    border-radius: 0.25rem;
    border: none;
    cursor: pointer;
  }
</style>