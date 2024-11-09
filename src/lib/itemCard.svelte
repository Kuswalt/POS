<script>
    export let product;
  </script>
  
  <button class="product-card">
    <img src={'/images/logo.png'} alt={product.name}>
    <div class="product-info">
      <p class="product-name">{product.name}</p>
      <p class="product-price">{product.price}</p>
    </div>
  </button>
  
  <style>
    .product-card {
      cursor: pointer;
      transition: box-shadow 0.3s;
      overflow: hidden;
      border-radius: 1rem;
      background-color: white;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .product-card:hover {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .product-info {
      display: flex;
      padding: 0.5rem;
      font-size: 0.875rem;
    }
    .product-name {
      flex-grow: 1;
      margin-right: 0.25rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .product-price {
      font-weight: 600;
    }
  </style>