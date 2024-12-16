<script lang="ts">
  import { productAvailability } from './stores/productAvailability.js';
  import type { MenuItem } from './types.js';
  
  export let product: MenuItem & {
    is_available?: boolean;
  };
  export let onAddToCart: () => void;

  let isClickable = true;
  const COOLDOWN_MS = 500; // 500ms cooldown between clicks

  $: isAvailable = $productAvailability[product.product_id] ?? false;
  
  // Function to get correct image URL
  function getImageUrl(image: string): string {
    if (!image) return '/images/placeholder.jpg';
    return `https://formalytics.me/uploads/${image}`;
  }

  async function handleAddToCart() {
    if (!isAvailable || !isClickable) {
      return;
    }

    isClickable = false;
    await onAddToCart();
    
    // Reset clickable after cooldown
    setTimeout(() => {
      isClickable = true;
    }, COOLDOWN_MS);
  }
</script>

<div class="item-card {!isAvailable ? 'unavailable' : ''} {!isClickable ? 'processing' : ''}" 
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
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
  }

  .image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    -webkit-transform: scale(1);
    transform: scale(1);
    -webkit-transition: transform 0.3s ease;
    transition: transform 0.3s ease;
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

  .processing {
    pointer-events: none;
    opacity: 0.7;
  }

  /* Mobile styles */
  @media (max-width: 768px) {
    .item-card {
      padding: 0.5rem;
      border-radius: 12px;
      width: 150px; /* Standard width for mobile */
      height: 231.88px; /* Fixed height as requested */
      -webkit-box-sizing: border-box;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    .image-container {
      width: 140px; /* Fixed width for image container */
      height: 140px; /* Fixed height to maintain aspect ratio */
      -webkit-box-sizing: border-box;
      box-sizing: border-box;
      margin-bottom: 0.25rem;
    }
    
    .item-details {
      height: 76.88px; /* Remaining height for details */
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 0.25rem 0;
    }
    
    .item-details h3 {
      font-size: 0.875rem;
      margin: 0;
      line-height: 1.2;
      /* Ensure text doesn't overflow */
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .item-details p {
      font-size: 0.875rem;
      font-weight: 600;
      margin: 0;
    }
    
    .unavailable-badge {
      font-size: 0.75rem;
      padding: 4px 8px;
    }
  }
</style>