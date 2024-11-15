<script lang="ts">
  interface CartItem {
    id: number;
    name: string;
    price: number;
    quantity: number;
    image?: string;
  }

  export let cartItems: CartItem[] = [];
  export let onUpdateQuantity: (productId: number, newQuantity: number) => void;
  export let onRemoveFromCart: (productId: number) => void;
  export let total: number;

  let customerName = '';
  let amountPaid = 0;
  let change = 0;

  $: change = amountPaid - total;
  $: canCheckout = customerName.trim() && amountPaid >= total;

  async function checkout() {
    if (!canCheckout) {
      alert('Please enter customer name and sufficient payment amount');
      return;
    }

    try {
      const response = await fetch('http://localhost/POS/api/routes.php?request=create-order', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          customer_name: customerName,
          amount_paid: amountPaid,
          total_amount: total,
          user_id: 1, // Get from session
          payment_status: 'paid',
          order_items: cartItems.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            price: item.price
          }))
        })
      });

      const result = await response.json();
      if (result.status) {
        alert(`Order placed successfully!\nChange: ₱${change.toFixed(2)}`);
        cartItems = []; // Clear cart after successful order
        customerName = '';
        amountPaid = 0;
      } else {
        alert(result.message);
      }
    } catch (error) {
      console.error('Error creating order:', error);
      alert('Failed to place order');
    }
  }
</script>

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
            <button on:click={() => onUpdateQuantity(item.id, item.quantity - 1)}>-</button>
            <span>{item.quantity}</span>
            <button on:click={() => onUpdateQuantity(item.id, item.quantity + 1)}>+</button>
          </div>
        </div>
        <button class="remove-btn" on:click={() => onRemoveFromCart(item.id)}>×</button>
      </div>
    {/each}
  </div>

  <div class="customer-details">
    <input
      type="text"
      bind:value={customerName}
      placeholder="Customer Name"
      class="input-field"
    />
    <input
      type="number"
      bind:value={amountPaid}
      placeholder="Amount Paid"
      min={total}
      class="input-field"
    />
    {#if amountPaid > 0}
      <div class="change-display">
        <span>Change: ₱{change.toFixed(2)}</span>
      </div>
    {/if}
  </div>

  <div class="cart-total">
    <h3>Total: ₱{total.toFixed(2)}</h3>
    <button 
      class="checkout-btn" 
      on:click={checkout}
      disabled={!canCheckout}
    >
      Proceed to Checkout
    </button>
  </div>
</div>

<style>
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

  .remove-btn {
    padding: 0.25rem 0.5rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.25rem;
    color: #ef4444;
  }

  .cart-total {
    padding-top: 1rem;
    border-top: 2px solid #e5e7eb;
  }

  .checkout-btn {
    width: 100%;
    padding: 0.75rem;
    background: #47cb50;
    color: white;
    border: none;
    border-radius: 0.5rem;
    margin-top: 1rem;
    cursor: pointer;
  }

  .checkout-btn:hover {
    background: #3db845;
  }

  .customer-details {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .input-field {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    font-size: 1rem;
  }

  .change-display {
    padding: 0.5rem;
    background: #f3f4f6;
    border-radius: 0.25rem;
    text-align: right;
    font-weight: bold;
  }

  .checkout-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
  }
</style>
