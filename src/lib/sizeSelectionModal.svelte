<script lang="ts">
  export let show: boolean;
  export let product: {
    product_id: number;
    name: string;
    price: string;
    sizes?: Array<{
      size_id: number;
      size_name: string;
      price: string;
    }>;
  };
  export let onSelect: (sizeId: number, price: string) => void;
  export let onClose: () => void;

  let selectedSize: number | null = null;
  let baseSize = { size_id: 0, size_name: 'Regular', price: product.price };
  
  // Create sizes array with base size first
  $: sizes = product.sizes && product.sizes.length > 0 
    ? [baseSize, ...product.sizes.filter(s => s.size_name !== 'Regular')] 
    : [baseSize];
</script>

<div class="modal-backdrop" class:show>
  <div class="modal-content">
    <div class="modal-header">
      <h2>{product.name}</h2>
      <button class="close-btn" on:click={onClose}>&times;</button>
    </div>

    <div class="size-options">
      {#each sizes as size}
        <button 
          class="size-btn {selectedSize === size.size_id ? 'selected' : ''}"
          on:click={() => {
            selectedSize = size.size_id;
            onSelect(size.size_id, size.price);
          }}
        >
          <div class="size-info">
            <span class="size-name">{size.size_name}</span>
            <span class="size-price">₱{parseFloat(size.price).toFixed(2)}</span>
          </div>
        </button>
      {/each}
    </div>
  </div>
</div>

<style>
  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  .modal-backdrop.show {
    display: flex;
  }

  .modal-content {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .modal-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
  }

  .size-options {
    display: grid;
    gap: 0.75rem;
  }

  .size-btn {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
  }

  .size-btn:hover {
    border-color: #47cb50;
    background: #f9fdf9;
  }

  .size-btn.selected {
    border-color: #47cb50;
    background: #f0fdf0;
  }

  .size-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .size-name {
    font-weight: 500;
  }

  .size-price {
    font-weight: 600;
    color: #47cb50;
  }
</style> 