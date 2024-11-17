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

  // Add new interface for receipt data
  interface ReceiptData {
    receipt_id: number;
    customer_name: string;
    date: string;
    items: CartItem[];
    total_amount: number;
    amount_paid: number;
    change: number;
  }

  // Add new state variables
  let showReceiptModal = false;
  let receiptData: ReceiptData | null = null;

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
              // Prepare receipt data
              receiptData = {
                receipt_id: receiptResult.receipt_id,
                customer_name: customerName,
                date: new Date().toLocaleString(),
                items: cartItems,
                total_amount: total,
                amount_paid: amountPaid,
                change: change
              };
              
              showReceiptModal = true; // Show the receipt modal
              cartItems = []; // Clear cart
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

  function closeReceiptModal() {
    showReceiptModal = false;
    receiptData = null;
  }

  function printReceipt() {
    const printWindow = window.open('', '_blank');
    if (!printWindow || !receiptData) return;

    const printContent = `
      <!DOCTYPE html>
      <html>
        <head>
          <title>Receipt #${receiptData.receipt_id}</title>
          <style>
            body {
              font-family: 'Courier New', Courier, monospace;
              padding: 20px;
              max-width: 300px;
              margin: 0 auto;
            }
            .header {
              text-align: center;
              margin-bottom: 20px;
            }
            .receipt-details {
              margin-bottom: 20px;
            }
            table {
              width: 100%;
              border-collapse: collapse;
              margin: 20px 0;
            }
            th, td {
              text-align: left;
              padding: 5px;
            }
            .summary {
              border-top: 1px dashed #000;
              margin-top: 20px;
              padding-top: 10px;
            }
            .summary p {
              margin: 5px 0;
            }
            @media print {
              body {
                width: 80mm; /* Standard receipt width */
              }
            }
          </style>
        </head>
        <body>
          <div class="header">
            <h2>Laz Bean Cafe</h2>
            <p>Receipt #${receiptData.receipt_id}</p>
          </div>
          
          <div class="receipt-details">
            <p>Date: ${receiptData.date}</p>
            <p>Customer: ${receiptData.customer_name}</p>
          </div>

          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              ${receiptData.items.map(item => `
                <tr>
                  <td>${item.name}</td>
                  <td>${item.quantity}</td>
                  <td>₱${item.price}</td>
                  <td>₱${(item.price * item.quantity).toFixed(2)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div class="summary">
            <p><strong>Total Amount:</strong> ₱${receiptData.total_amount.toFixed(2)}</p>
            <p><strong>Amount Paid:</strong> ₱${receiptData.amount_paid.toFixed(2)}</p>
            <p><strong>Change:</strong> ₱${receiptData.change.toFixed(2)}</p>
          </div>

          <div class="footer" style="text-align: center; margin-top: 30px;">
            <p>Thank you for your purchase!</p>
            <p>Please come again</p>
          </div>
        </body>
      </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load before printing
    printWindow.onload = function() {
      printWindow.print();
      // Optional: Close the window after printing
      // printWindow.close();
    };
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

{#if showReceiptModal && receiptData}
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="receipt">
        <h2>Receipt #{receiptData.receipt_id}</h2>
        <p class="receipt-date">Date: {receiptData.date}</p>
        <p class="customer-name">Customer: {receiptData.customer_name}</p>
        
        <div class="receipt-items">
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              {#each receiptData.items as item}
                <tr>
                  <td>{item.name}</td>
                  <td>{item.quantity}</td>
                  <td>₱{item.price}</td>
                  <td>₱{(item.price * item.quantity).toFixed(2)}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        <div class="receipt-summary">
          <p><strong>Total Amount:</strong> ₱{receiptData.total_amount.toFixed(2)}</p>
          <p><strong>Amount Paid:</strong> ₱{receiptData.amount_paid.toFixed(2)}</p>
          <p><strong>Change:</strong> ₱{receiptData.change.toFixed(2)}</p>
        </div>

        <button class="close-btn" on:click={closeReceiptModal}>Close</button>
        <button class="print-btn" on:click={printReceipt}>Print</button>
      </div>
    </div>
  </div>
{/if}

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

  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  .modal-content {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
  }

  .receipt {
    font-family: 'Courier New', Courier, monospace;
  }

  .receipt h2 {
    text-align: center;
    margin-bottom: 1rem;
  }

  .receipt-date, .customer-name {
    margin-bottom: 0.5rem;
  }

  .receipt-items {
    margin: 1rem 0;
  }

  .receipt-items table {
    width: 100%;
    border-collapse: collapse;
  }

  .receipt-items th, .receipt-items td {
    padding: 0.5rem;
    text-align: left;
    border-bottom: 1px solid #eee;
  }

  .receipt-summary {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px dashed #ccc;
  }

  .receipt-summary p {
    margin: 0.5rem 0;
  }

  .close-btn {
    display: block;
    width: 100%;
    padding: 0.75rem;
    background: #4B5563;
    color: white;
    border: none;
    border-radius: 0.5rem;
    margin-top: 1rem;
    cursor: pointer;
  }

  .close-btn:hover {
    background: #374151;
  }

  .print-btn {
    display: block;
    width: 100%;
    padding: 0.75rem;
    background: #4B5563;
    color: white;
    border: none;
    border-radius: 0.5rem;
    margin-top: 1rem;
    cursor: pointer;
  }

  .print-btn:hover {
    background: #374151;
  }
</style>
