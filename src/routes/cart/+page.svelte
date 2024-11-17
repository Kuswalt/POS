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
  export let userId: number;

  let customerName = '';
  let amountPaid = 0;
  let change = 0;

  // Calculate total from cart items
  $: total = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  // Calculate change whenever amountPaid or total changes
  $: change = amountPaid - total;

  async function saveCustomer() {
    if (!customerName.trim()) {
      alert('Please enter customer name');
      return;
    }

    if (!amountPaid) {
      alert('Please enter amount paid');
      return;
    }

    try {
      // First save customer info
      const customerResponse = await fetch('http://localhost/POS/api/routes.php?request=add-customer', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          Name: customerName,
          total_amount: amountPaid
        })
      });

      let customerResult;
      try {
        customerResult = await customerResponse.json();
      } catch (e) {
        console.error('Customer Response:', await customerResponse.text());
        throw new Error('Invalid JSON in customer response');
      }

      if (customerResult.status) {
        // Create order
        const orderResponse = await fetch('http://localhost/POS/api/routes.php?request=create-order', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            customer_id: customerResult.customer_id,
            total_amount: total,
            user_id: userId,
            payment_status: 'paid',
            order_items: cartItems.map(item => ({
              product_id: item.id,
              quantity: item.quantity,
              price: item.price
            }))
          })
        });

        let orderResult;
        try {
          orderResult = await orderResponse.json();
        } catch (e) {
          console.error('Order Response:', await orderResponse.text());
          throw new Error('Invalid JSON in order response');
        }

        if (orderResult.status) {
          // Record sale
          const saleResponse = await fetch('http://localhost/POS/api/routes.php?request=add-sale', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              order_id: orderResult.order_id,
              total_sales: total,
              user_id: userId
            })
          });

          let saleResult;
          try {
            saleResult = await saleResponse.json();
          } catch (e) {
            console.error('Sale Response:', await saleResponse.text());
            throw new Error('Invalid JSON in sale response');
          }

          if (saleResult.status) {
            // Generate receipt
            const receiptResponse = await fetch('http://localhost/POS/api/routes.php?request=add-receipt', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                order_id: orderResult.order_id,
                total_amount: total
              })
            });

            let receiptResult;
            try {
              receiptResult = await receiptResponse.json();
            } catch (e) {
              console.error('Receipt Response:', await receiptResponse.text());
              throw new Error('Invalid JSON in receipt response');
            }

            if (receiptResult.status) {
              alert('Transaction completed successfully!');
              cartItems = []; // Clear cart after successful save
              customerName = '';
              amountPaid = 0;
            } else {
              alert('Failed to generate receipt: ' + receiptResult.message);
            }
          } else {
            alert('Failed to record sale: ' + saleResult.message);
          }
        } else {
          alert('Failed to save order information: ' + orderResult.message);
        }
      } else {
        alert('Failed to save customer information: ' + customerResult.message);
      }
    } catch (error) {
      console.error('Error saving information:', error);
      alert(error.message || 'Failed to save information');
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
    <div class="customer-actions">
      <button 
        class="save-customer-btn"
        on:click={saveCustomer}
        disabled={!customerName.trim() || !amountPaid}
      >
        Proceed To Checkout
      </button>
    </div>
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
    flex: 1;
    padding: 0.75rem;
    background: #47cb50;
    color: white;
    border: none;
    border-radius: 0.5rem;
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

  .customer-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
  }

  .save-customer-btn {
    flex: 1;
    padding: 0.75rem;
    background: #4B5563;
    color: white;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
  }

  .save-customer-btn:hover {
    background: #374151;
  }

  .save-customer-btn:disabled {
    background: #9CA3AF;
    cursor: not-allowed;
  }
</style>
