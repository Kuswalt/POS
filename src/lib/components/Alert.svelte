<script lang="ts">
    export let message: string;
    export let type: 'success' | 'warning' | 'error' = 'warning';
    export let show: boolean;
  
    const colors = {
      success: 'bg-green-100 text-green-800 border-green-400',
      warning: 'bg-yellow-100 text-yellow-800 border-yellow-400',
      error: 'bg-red-100 text-red-800 border-red-400'
    };
  
    function handleClose() {
      show = false;
    }
  </script>
  
  {#if show}
    <div class="fixed top-4 right-4 z-50 animate-slide-in">
      <div class={`${colors[type]} border rounded-lg p-4 flex items-center shadow-lg max-w-md`}>
        <div class="flex-grow">
          {message}
        </div>
        <button 
          class="ml-4 text-gray-500 hover:text-gray-700" 
          on:click={handleClose}
        >
          ✕
        </button>
      </div>
    </div>
  {/if}
  
  <style>
    .animate-slide-in {
      animation: slideIn 0.3s ease-out;
    }
  
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>