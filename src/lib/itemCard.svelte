<script lang="ts">
  export let product: {
    product_id: number;
    name: string;
    image: string;
    price: string;
    category: string;
    is_available: boolean;
    sizes?: Array<{
      size_id: number;
      size_name: string;
      price: string;
    }>;
  };
</script>

<div class="item-card {!product.is_available ? 'unavailable' : ''}">
  <img src={product.image ? `uploads/${product.image}` : 'placeholder.jpg'} alt={product.name} />
  <div class="item-details">
    <h3>{product.name}</h3>
    {#if product.sizes && product.sizes.length > 0}
      <p class="price">
        ₱{Math.min(...product.sizes.map(s => Number(s.price)))} - 
        ₱{Math.max(...product.sizes.map(s => Number(s.price)))}
      </p>
      <span class="size-badge">Multiple Sizes</span>
    {:else}
      <p class="price">₱{product.price}</p>
      <span class="size-badge">Regular</span>
    {/if}
    {#if !product.is_available}
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
    max-width: 200px;
    cursor: pointer;
  }

  .item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }

  .image-container {
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
  }

  .card-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .card-content {
    width: 100%;
  }

  .card-title {
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: #333;
  }

  .card-price {
    color: #333;
    font-weight: bold;
    font-size: 0.9rem;
  }

  .unavailable {
    opacity: 0.7;
    pointer-events: none;
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
  }

  .size-badge {
    font-size: 0.75rem;
    color: #4CAF50;
    background: #e8f5e9;
    padding: 2px 8px;
    border-radius: 12px;
    margin-top: 4px;
  }
</style>