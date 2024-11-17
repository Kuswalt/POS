<script lang="ts">

  import Header from '$lib/header.svelte';
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;
  
  // Empty array for now - will be populated later with actual data
  let salesData = [];
  let filteredSalesData = [];
  
  // Filter states
  let searchQuery = '';
  let selectedDay = '';
  let selectedMonth = '';
  let selectedYear = '';
  
  // Unique years and months for filters
  $: years = [...new Set(salesData.map(sale => new Date(sale.order_date).getFullYear()))].sort((a, b) => b - a);
  $: months = [...Array(12)].map((_, i) => ({
    value: i + 1,
    label: new Date(2000, i, 1).toLocaleString('default', { month: 'long' })
  }));
  $: days = [...Array(31)].map((_, i) => i + 1);

  // Filter function
  $: {
    filteredSalesData = salesData.filter(sale => {
      const saleDate = new Date(sale.order_date);
      const matchesSearch = sale.product_name.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesYear = !selectedYear || saleDate.getFullYear() === parseInt(selectedYear);
      const matchesMonth = !selectedMonth || saleDate.getMonth() + 1 === parseInt(selectedMonth);
      const matchesDay = !selectedDay || saleDate.getDate() === parseInt(selectedDay);
      
      return matchesSearch && matchesYear && matchesMonth && matchesDay;
    });
  }

  // Reset filters
  function resetFilters() {
    searchQuery = '';
    selectedDay = '';
    selectedMonth = '';
    selectedYear = '';
  }

  let ApexCharts;
  
  // Add these chart variable declarations
  let salesPerPeriodChart;
  let salesPerProductChart;
  let salesPerDayChart;
  let salesPerProductPerDayChart;
  
  // Function to prepare data for the sales per period chart
  function prepareSalesPerPeriodData(data) {
    const salesByPeriod = {};
    
    data.forEach(sale => {
      const date = new Date(sale.order_date);
      const period = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
      
      if (!salesByPeriod[period]) {
        salesByPeriod[period] = 0;
      }
      salesByPeriod[period] += parseFloat(sale.total_amount);
    });

    const sortedPeriods = Object.keys(salesByPeriod).sort();
    
    return {
      x: sortedPeriods,
      y: sortedPeriods.map(period => salesByPeriod[period])
    };
  }

  // Function to prepare data for the sales per product chart
  function prepareSalesPerProductData(data) {
    const salesByProduct = {};
    const periods = new Set();
    
    data.forEach(sale => {
      const product = sale.product_name;
      const date = new Date(sale.order_date);
      const period = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
      
      if (!salesByProduct[product]) {
        salesByProduct[product] = {};
      }
      if (!salesByProduct[product][period]) {
        salesByProduct[product][period] = 0;
      }
      salesByProduct[product][period] += parseFloat(sale.total_amount);
      periods.add(period);
    });

    const sortedPeriods = Array.from(periods).sort();
    
    return Object.entries(salesByProduct).map(([product, data]) => ({
      name: product,
      data: sortedPeriods.map(period => ({
        x: period,
        y: data[period] || 0
      }))
    }));
  }

  // Add new data preparation functions for daily data
  function prepareSalesPerDayData(data) {
    const salesByDay = {};
    
    data.forEach(sale => {
      const date = new Date(sale.order_date);
      const day = date.toISOString().split('T')[0]; // Format: YYYY-MM-DD
      
      if (!salesByDay[day]) {
        salesByDay[day] = 0;
      }
      salesByDay[day] += parseFloat(sale.total_amount);
    });

    const sortedDays = Object.keys(salesByDay).sort();
    
    return {
      x: sortedDays,
      y: sortedDays.map(day => salesByDay[day])
    };
  }

  function prepareSalesPerProductPerDayData(data) {
    const salesByProduct = {};
    const days = new Set();
    
    data.forEach(sale => {
      const product = sale.product_name;
      const date = new Date(sale.order_date);
      const day = date.toISOString().split('T')[0]; // Format: YYYY-MM-DD
      
      if (!salesByProduct[product]) {
        salesByProduct[product] = {};
      }
      if (!salesByProduct[product][day]) {
        salesByProduct[product][day] = 0;
      }
      salesByProduct[product][day] += parseFloat(sale.total_amount);
      days.add(day);
    });

    const sortedDays = Array.from(days).sort();
    
    return Object.entries(salesByProduct).map(([product, data]) => ({
      name: product,
      data: sortedDays.map(day => ({
        x: day,
        y: data[day] || 0
      }))
    }));
  }

  async function initializeCharts() {
    if (browser) {
      ApexCharts = (await import('apexcharts')).default;
      updateCharts();
    }
  }

  // Initialize/update charts
  function updateCharts() {
    if (!browser || !ApexCharts) return;

    const periodData = prepareSalesPerPeriodData(salesData);
    const productData = prepareSalesPerProductData(salesData);

    // Total sales per period chart
    const periodOptions = {
      series: [{
        name: 'Total Sales',
        data: periodData.y
      }],
      chart: {
        type: 'line',
        height: 350,
        toolbar: {
          show: true
        }
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      xaxis: {
        categories: periodData.x,
        title: {
          text: 'Period'
        }
      },
      yaxis: {
        title: {
          text: 'Sales Amount (₱)'
        },
        labels: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      },
      title: {
        text: 'Total Sales per Month',
        align: 'center'
      },
      grid: {
        borderColor: '#e0e0e0',
        row: {
          colors: ['#f5f5f5', 'transparent'],
          opacity: 0.5
        }
      },
      markers: {
        size: 6
      },
      tooltip: {
        y: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      }
    };

    // Sales per product chart
    const productOptions = {
      series: productData,
      chart: {
        type: 'line',
        height: 350,
        toolbar: {
          show: true
        }
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      xaxis: {
        type: 'category',
        title: {
          text: 'Period'
        }
      },
      yaxis: {
        title: {
          text: 'Sales Amount (₱)'
        },
        labels: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      },
      title: {
        text: 'Sales per Product per Month',
        align: 'center'
      },
      grid: {
        borderColor: '#e0e0e0',
        row: {
          colors: ['#f5f5f5', 'transparent'],
          opacity: 0.5
        }
      },
      markers: {
        size: 6
      },
      tooltip: {
        y: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'center'
      },
      colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0']
    };

    // Add daily charts
    const dailyData = prepareSalesPerDayData(salesData);
    const productDailyData = prepareSalesPerProductPerDayData(salesData);

    const dailyOptions = {
      series: [{
        name: 'Total Sales',
        data: dailyData.y
      }],
      chart: {
        type: 'line',
        height: 350,
        toolbar: {
          show: true
        }
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      xaxis: {
        categories: dailyData.x,
        title: {
          text: 'Date'
        }
      },
      yaxis: {
        title: {
          text: 'Sales Amount (₱)'
        },
        labels: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      },
      title: {
        text: 'Total Sales per Day',
        align: 'center'
      },
      grid: {
        borderColor: '#e0e0e0',
        row: {
          colors: ['#f5f5f5', 'transparent'],
          opacity: 0.5
        }
      },
      markers: {
        size: 6
      },
      tooltip: {
        y: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      }
    };

    const productDailyOptions = {
      series: productDailyData,
      chart: {
        type: 'line',
        height: 350,
        toolbar: {
          show: true
        }
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      xaxis: {
        type: 'category',
        title: {
          text: 'Date'
        }
      },
      yaxis: {
        title: {
          text: 'Sales Amount (₱)'
        },
        labels: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      },
      title: {
        text: 'Sales per Product per Day',
        align: 'center'
      },
      grid: {
        borderColor: '#e0e0e0',
        row: {
          colors: ['#f5f5f5', 'transparent'],
          opacity: 0.5
        }
      },
      markers: {
        size: 6
      },
      tooltip: {
        y: {
          formatter: (value) => `₱${value.toFixed(2)}`
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'center'
      },
      colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0']
    };

    // Destroy existing charts if they exist
    if (salesPerPeriodChart) {
      salesPerPeriodChart.destroy();
    }
    if (salesPerProductChart) {
      salesPerProductChart.destroy();
    }
    if (salesPerDayChart) {
      salesPerDayChart.destroy();
    }
    if (salesPerProductPerDayChart) {
      salesPerProductPerDayChart.destroy();
    }

    // Create new charts
    salesPerPeriodChart = new ApexCharts(document.querySelector("#salesPerPeriodChart"), periodOptions);
    salesPerProductChart = new ApexCharts(document.querySelector("#salesPerProductChart"), productOptions);
    salesPerDayChart = new ApexCharts(document.querySelector("#salesPerDayChart"), dailyOptions);
    salesPerProductPerDayChart = new ApexCharts(document.querySelector("#salesPerProductPerDayChart"), productDailyOptions);

    salesPerPeriodChart.render();
    salesPerProductChart.render();
    salesPerDayChart.render();
    salesPerProductPerDayChart.render();
  }

  onMount(async () => {
    try {
      const response = await fetch('http://localhost/POS/api/routes.php?request=get-sales-data');
      const result = await response.json();
      
      if (result.status) {
        salesData = result.data;
        filteredSalesData = salesData;
        await initializeCharts();
      } else {
        console.error('Failed to fetch sales data:', result.message);
      }
    } catch (error) {
      console.error('Error fetching sales data:', error);
    }
  });
</script>

<Header {y} {innerHeight} />

<div class="content">
  <div class="sales-container">
    <h2 class="text-2xl font-bold mb-4">Sales History</h2>
    
    <!-- Charts Section -->
    <div class="charts-container grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="chart-wrapper bg-white p-4 rounded-lg shadow">
        <div id="salesPerPeriodChart"></div>
      </div>
      <div class="chart-wrapper bg-white p-4 rounded-lg shadow">
        <div id="salesPerProductChart"></div>
      </div>
      <div class="chart-wrapper bg-white p-4 rounded-lg shadow">
        <div id="salesPerDayChart"></div>
      </div>
      <div class="chart-wrapper bg-white p-4 rounded-lg shadow">
        <div id="salesPerProductPerDayChart"></div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filters mb-4 space-y-4">
      <!-- Search Bar -->
      <div class="flex items-center space-x-4">
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Search by product name..."
          class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
        />
      </div>
      
      <!-- Date Filters -->
      <div class="flex flex-wrap gap-4">
        <select
          bind:value={selectedYear}
          class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
        >
          <option value="">All Years</option>
          {#each years as year}
            <option value={year}>{year}</option>
          {/each}
        </select>

        <select
          bind:value={selectedMonth}
          class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
        >
          <option value="">All Months</option>
          {#each months as month}
            <option value={month.value}>{month.label}</option>
          {/each}
        </select>

        <select
          bind:value={selectedDay}
          class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
        >
          <option value="">All Days</option>
          {#each days as day}
            <option value={day}>{day}</option>
          {/each}
        </select>

        <button
          on:click={resetFilters}
          class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors"
        >
          Reset Filters
        </button>
      </div>
    </div>

    <!-- Results Count -->
    <div class="mb-4 text-gray-600">
      Showing {filteredSalesData.length} of {salesData.length} records
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          {#if filteredSalesData.length === 0}
            <tr>
              <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                No sales data available
              </td>
            </tr>
          {:else}
            {#each filteredSalesData as sale}
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">{sale.username}</td>
                <td class="px-6 py-4 whitespace-nowrap">{sale.product_name}</td>
                <td class="px-6 py-4 whitespace-nowrap">{sale.quantity}</td>
                <td class="px-6 py-4 whitespace-nowrap">{sale.customer_name}</td>
                <td class="px-6 py-4 whitespace-nowrap">₱{parseFloat(sale.amount_paid).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap">₱{parseFloat(sale.total_amount).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap">{new Date(sale.order_date).toLocaleDateString()}</td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
  .content {
    margin-top: 4rem;
    padding: 2rem;
  }

  .sales-container {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  /* Make table header sticky */
  thead {
    position: sticky;
    top: 0;
    z-index: 1;
  }

  /* Add some responsive styles */
  @media (max-width: 1024px) {
    .overflow-x-auto {
      max-width: 100vw;
    }
    
    .filters {
      flex-direction: column;
    }
    
    .filters > * {
      width: 100%;
    }
  }

  .chart-wrapper {
    min-height: 400px;
  }
</style>