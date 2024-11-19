<script lang="ts">
  export let product: {
    product_id: number;
    name: string;
    image: string;
    price: string;
    category: string;
    is_available?: boolean;
  };

  let isAvailable = true;
  
  async function checkAvailability() {
    try {
      const response = await fetch(`http://localhost/POS/api/routes.php?request=get-product-ingredients&product_id=${product.product_id}`);
      const result = await response.json();
      
      if (!result.status || !result.data || result.data.length === 0) {
        isAvailable = false;
        return;
      }

      // Check if any ingredient is insufficient for the requested quantity
      for (const ingredient of result.data) {
        const requiredQuantity = ingredient.quantity_needed;
        if (ingredient.stock_quantity < requiredQuantity) {
          isAvailable = false;
          return;
        }
      }
      
      isAvailable = true;
    } catch (error) {
      console.error('Error checking availability:', error);
      isAvailable = false;
    }
  }

  $: if (product.product_id) {
    checkAvailability();
  }
</script>

<div class="item-card {!isAvailable ? 'unavailable' : ''}">
  <img src={product.image ? `uploads/${product.image}` : 'placeholder.jpg'} alt={product.name} />
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
    max-width: 200px;
    cursor: pointer;
  }

  .item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
</style>