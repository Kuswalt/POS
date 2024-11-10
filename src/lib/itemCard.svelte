<script lang="ts">
  export let product: {
    id: number;
    name: string;
    image: string;
    price: string;
    category: string;
  };
</script>

<div class="card">
  <div class="image-container">
    <img src={product.image ? `uploads/${product.image}` : 'placeholder.jpg'} alt={product.name} class="card-image" />
  </div>
  <div class="card-content">
    <h3 class="card-title">{product.name}</h3>
    <p class="card-price">₱ {product.price}</p>
  </div>
</div>

<style>
  .card {
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

  .card:hover {
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
</style>