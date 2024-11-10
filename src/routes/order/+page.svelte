<script lang="ts">
  import SideNav from '$lib/sideNav.svelte';
  import Header from '$lib/header.svelte';
  import ItemCard from '$lib/itemCard.svelte';
  import { onMount } from 'svelte';

type Product = { id: number; name: string; image: string; price: string; category: string };

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;
  let products: Product[] = [];

  onMount(async () => {
    await fetchItems();
  });

  async function fetchItems() {
    const response = await fetch('http://localhost/POS/api/routes.php?request=get-menu-items');
    products = await response.json();
  }
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