<script lang="ts">
  import { productAvailability } from './stores/productAvailability.js';
  import type { MenuItem } from './types.js';
  
  export let product: MenuItem & {
    is_available?: boolean;
  };
  export let onAddToCart: () => void;

  $: isAvailable = $productAvailability[product.product_id] ?? false;
  
  // Function to get correct image URL
  function getImageUrl(image: string): string {
    if (!image) return '/images/placeholder.jpg';
    return `https://formalytics.me/uploads/${image}`;
  }

  async function handleAddToCart() {
    if (!isAvailable) {
      return;
    }
    onAddToCart();
  }
</script>

<div class="item-card {!isAvailable ? 'unavailable' : ''}" 
     on:click={handleAddToCart} 
     role="button" 
     tabindex="0">
  <div class="image-container">
    <img 
      src={getImageUrl(product.image)}
      alt={product.name} 
      on:error={(e) => {
        const img = e.currentTarget as HTMLImageElement;
        img.src = '/images/placeholder.jpg';
      }}
    />
  </div>
  <div class="item-details">
    <h3>{product.name}</h3>
    <p class="price">₱{product.price}</p>
    {#if !isAvailable}
      <div class="unavailable-badge">Unavailable</div>
    {/if}
  </div>
</div>

<style>
  .item-card {
    position: relative;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    cursor: pointer;
  }

  .image-container {
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    margin-bottom: 0.5rem;
  }

  .image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
  }

  .item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }

  .unavailable {
    opacity: 0.7;
    pointer-events: none;
    cursor: not-allowed;
  }

  .unavailable-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    background-color: rgba(255, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    text-transform: uppercase;
    z-index: 2;
  }

  /* Mobile styles */
  @media (max-width: 768px) {
    .item-card {
      padding: 0.5rem;
      border-radius: 12px;
    }
    
    .image-container {
      margin-bottom: 0.25rem;
    }
    
    .item-details h3 {
      font-size: 0.875rem;
      margin-bottom: 0.25rem;
    }
    
    .item-details p {
      font-size: 0.875rem;
      font-weight: 600;
    }
    
    .unavailable-badge {
      font-size: 0.75rem;
      padding: 4px 8px;
    }
  }
</style>