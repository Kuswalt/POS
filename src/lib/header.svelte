<script>
  export let y;
  export let innerHeight;

  // Tabs for navigation
  export let tabs = [
    { name: "Order", link: "/order" },
    { name: "Inventory", link: "/inventory" },
    { name: "Sales", link: "/sales" },
    
  ];

  // State to toggle dropdowns
  let isDropdownOpen = false;
</script>

<header
  class={"fixed z-[10] top-0 w-full duration-200 px-6 flex items-center justify-between border-b border-solid " +
    (y > 0 ? " py-4 bg-green-500 border-green-700" : " py-6 bg-green-500 border-green-700")}
>
  <!-- Header Title -->
  <h1 class="font-medium text-black">
    <b class="font-dynapuff">Laz Bean Cafe</b>
  </h1>
  <!-- Navigation Tabs for larger screens -->
  <div class="sm:flex items-center gap-4 hidden">
    {#each tabs as tab}
      <a href={tab.link} class="duration-200 hover:text-green-300 text-black">
        <p class="font-afacad">{tab.name}</p>
      </a>
    {/each}
    <!-- Connect with me button -->
    <a href="/" class="relative overflow-hidden px-5 py-2 group rounded-full bg-white text-green-500">
      <div class="absolute top-0 right-full w-full h-full bg-green-400 opacity-20 group-hover:translate-x-full z-0 duration-200" />
      <h4 class="relative z-9 font-afacad">Log out</h4>
    </a>
  </div>
  <!-- Dropdown button for mobile view -->
  <div class="sm:hidden relative">
    <button on:click={() => isDropdownOpen = !isDropdownOpen} class="px-4 py-2 bg-white text-green-500 rounded flex items-center gap-2 hover:text-green-300 duration-200">
      <i class="fas fa-bars"></i>
    </button>
    {#if isDropdownOpen}
      <div class="absolute right-0 mt-2 w-48 bg-green-500 border border-gray-200 rounded shadow-lg transition-transform transform origin-top-right duration-200 ease-out scale-100">
        {#each tabs as tab}
          <a href={tab.link} class="block px-4 py-2 text-white hover:text-green-300 duration-200">
            <p class="font-afacad">{tab.name}</p>
          </a>
        {/each}
        <a href="/" class="block px-4 py-2 text-white hover:text-green-300 duration-200 font-afacad">
          Log out
        </a>
      </div>
    {/if}
  </div>
</header>
