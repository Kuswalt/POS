<script>
  export let activeMenu;
  let isPosActive = activeMenu === "pos";
  let isInventoryActive = activeMenu === "inventory";
</script>

<div class="flex flex-row h-[90vh] w-auto flex-shrink-0 pl-4 pr-2 py-2 mt-5">
  <div class="flex flex-col items-center py-2 flex-shrink-0 w-20 bg-green-500 rounded-2xl h-full">
    <a class="flex items-center justify-center h-12 w-12 text-green-700 rounded-full" aria-label="Cash Register">
      <i class="fa-solid fa-mug-saucer"></i>
    </a>
    <ul class="flex flex-col space-y-2 mt-12">
      <li>
        <a class="flex items-center" aria-label="Mug Saucer">
          <span class={"bg-green-300 shadow-lg text-white flex items-center justify-center h-12 w-12 rounded-full hover:bg-green-400 hover:cursor-pointer text-green-100 flex items-center justify-center h-12 w-12 rounded-full " + (isPosActive ? "bg-green-400" : "")}>
            <i class="fa-solid fa-mug-saucer"></i>
          </span>
        </a>
      </li>
      <li>
        <a class="flex items-center" aria-label="Inventory">
          <span class={"flex items-center justify-center text-green-100 hover:bg-green-400 hover:cursor-pointer h-12 w-12 rounded-full " + (isInventoryActive ? "bg-green-400" : "")}>
            <i class="fa-solid fa-pizza-slice"></i>
          </span>
        </a>
      </li>
      <li>
        <a class="flex items-center" aria-label="Cash Register">
          <span class="flex items-center justify-center text-green-100 hover:bg-green-400 hover:cursor-pointer h-12 w-12 rounded-full">
            <i class="fa-solid fa-burger"></i>
          </span>
        </a>
      </li>
      <li>
        <a class="flex items-center" aria-label="Cash Register">
          <span class="flex items-center justify-center text-green-100 hover:bg-green-400 hover:cursor-pointer h-12 w-12 rounded-full">
            <i class="fa-solid fa-whiskey-glass"></i>
          </span>
        </a>
      </li>
    </ul>
    <a href="/settings" target="_blank" rel="noreferrer" class="mt-auto mx-auto flex items-center justify-center text-green-200 hover:text-green-100 h-10 w-10 focus:outline-none" aria-label="Logout">
      <i class="fa-solid fa-gear"></i>
    </a>
  </div>
</div>
