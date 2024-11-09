<script>
  import SideNav from '$lib/sideNav.svelte';
  import Header from '$lib/header.svelte';
  import ItemCard from '$lib/itemCard.svelte';

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;

  let products = [
    { name: 'Beef Burger', image: 'beef-burger.jpg', price: '₱45.00' },
    { name: 'Sandwich', image: 'sandwich.jpg', price: '₱32.00' },
    { name: 'Sawarama', image: 'sawarama.jpg', price: '₱30.00' },
    { name: 'Croissant', image: 'croissant.jpg', price: '₱16.00' },
    { name: 'Cinnamon Roll', image: 'cinnamon-roll.jpg', price: '₱20.00' },
    { name: 'Choco Glaze Donut with Peanut', image: 'choco-glaze-donut-peanut.jpg', price: '₱10.00' },
    { name: 'Choco Glazed Donut', image: 'choco-glazed-donut.jpg', price: '₱10.00' },
    { name: 'Red Glazed Donut', image: 'red-glazed-donut.jpg', price: '₱10.00' },
    { name: 'Blueberry Muffin', image: 'blueberry-muffin.jpg', price: '₱15.00' },
    { name: 'Apple Pie', image: 'apple-pie.jpg', price: '₱25.00' },
    { name: 'Chocolate Cake', image: 'chocolate-cake.jpg', price: '₱50.00' },
    { name: 'Vanilla Ice Cream', image: 'vanilla-ice-cream.jpg', price: '₱12.00' }
  ];
</script>

<Header {y} {innerHeight} />
<div class="container">
  <div class="content">
    <SideNav activeMenu="pos" />
    <div class="main">
      <div class="grid grid-cols-4 gap-4 pb-3">
        {#each products as product}
          <ItemCard {product} />
        {/each}
      </div>
    </div>
  </div>
</div>

<style>
  .content {
    display: flex;
    flex-grow: 1;
    width: 111%;
    overflow: hidden;
    margin-top: 4rem;
  }
  .main {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    padding: 2rem;
    width: 100%;
    height: calc(100vh - 4rem);
    overflow-y: auto;
  }
  .grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    width: 100%;
  }
</style>