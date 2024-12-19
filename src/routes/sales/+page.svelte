<script lang="ts">

  import Header from '$lib/header.svelte';
  import { onMount, onDestroy } from 'svelte';
  import { browser } from '$app/environment';
  import * as XLSX from 'xlsx';
  import { ApiService } from '$lib/services/api';
  import { userStore } from '$lib/auth';

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;
  
  // Update the data storage variables
  let salesData = [];
  let chartData = {};  
  let dailyChartData = {};  // Add this new variable
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
  function prepareSalesPerPeriodData(chartData) {
    if (!chartData || Object.keys(chartData).length === 0) {
        return { x: [], y: [] };
    }

    const salesByPeriod = {};
    
    Object.entries(chartData).forEach(([period, products]) => {
        salesByPeriod[period] = Object.values(products).reduce((sum, value) => sum + value, 0);
    });

    const sortedPeriods = Object.keys(salesByPeriod).sort();
    
    return {
        x: sortedPeriods,
        y: sortedPeriods.map(period => salesByPeriod[period])
    };
  }

  // Function to prepare data for the sales per product chart
  function prepareSalesPerProductData(data) {
    if (!data || !data.chartData || Object.keys(data.chartData).length === 0) {
        return [];
    }

    const products = new Set();
    const periods = new Set();
    const salesByProduct = {};
    
    Object.entries(data.chartData).forEach(([period, productSales]) => {
        periods.add(period);
        Object.entries(productSales).forEach(([product, amount]) => {
            products.add(product);
            if (!salesByProduct[product]) {
                salesByProduct[product] = {};
            }
            salesByProduct[product][period] = amount;
        });
    });

    const sortedPeriods = Array.from(periods).sort();
    
    return Array.from(products).map(product => ({
        name: product,
        data: sortedPeriods.map(period => ({
            x: period,
            y: salesByProduct[product][period] || 0
        }))
    }));
  }

  // Update the prepareSalesPerDayData function
  function prepareSalesPerDayData(data) {
    if (!data || data.length === 0) {
        return { x: [], y: [] };
    }

    const salesByDay = {};
    
    data.forEach(sale => {
        const date = new Date(sale.order_date);
        const day = date.toISOString().split('T')[0]; // Format: YYYY-MM-DD
        const products = sale.product_name.split(', ');
        const quantities = sale.quantity.split(', ').map(Number);
        
        if (!salesByDay[day]) {
            salesByDay[day] = 0;
        }

        // Calculate total for this sale based on individual products
        products.forEach((_, index) => {
            salesByDay[day] += parseFloat(sale.total_amount) * (quantities[index] / quantities.reduce((a, b) => a + b, 0));
        });
    });

    const sortedDays = Object.keys(salesByDay).sort();
    
    return {
        x: sortedDays,
        y: sortedDays.map(day => salesByDay[day])
    };
  }

  // Update the prepareSalesPerProductPerDayData function
  function prepareSalesPerProductPerDayData(data) {
    if (!data || !data.dailyChartData || Object.keys(data.dailyChartData).length === 0) {
        return [];
    }

    const products = new Set();
    const days = new Set();
    const salesByProduct = {};
    
    Object.entries(data.dailyChartData).forEach(([day, productSales]) => {
        days.add(day);
        Object.entries(productSales).forEach(([product, amount]) => {
            products.add(product);
            if (!salesByProduct[product]) {
                salesByProduct[product] = {};
            }
            salesByProduct[product][day] = amount;
        });
    });

    const sortedDays = Array.from(days).sort();
    
    return Array.from(products).map(product => ({
        name: product,
        data: sortedDays.map(day => ({
            x: day,
            y: salesByProduct[product][day] || 0
        }))
    }));
  }

  // Add cleanup function for charts
  onDestroy(() => {
    if (salesPerPeriodChart) {
      salesPerPeriodChart.destroy();
      salesPerPeriodChart = null;
    }
    if (salesPerProductChart) {
      salesPerProductChart.destroy();
      salesPerProductChart = null;
    }
    if (salesPerDayChart) {
      salesPerDayChart.destroy();
      salesPerDayChart = null;
    }
    if (salesPerProductPerDayChart) {
      salesPerProductPerDayChart.destroy();
      salesPerProductPerDayChart = null;
    }
  });

  // Update the initializeCharts function
  async function initializeCharts() {
    if (browser && !ApexCharts) {  // Add check for existing ApexCharts
      try {
        ApexCharts = (await import('apexcharts')).default;
        updateCharts();
      } catch (error) {
        console.error('Failed to load ApexCharts:', error);
      }
    } else if (browser) {
      updateCharts();
    }
  }

  // Update the updateCharts function to include null checks
  function updateCharts() {
    if (!browser || !ApexCharts || !document.querySelector("#salesPerPeriodChart")) {
      return;  // Exit if elements don't exist
    }
    
    const periodData = prepareSalesPerPeriodData(chartData);
    const productData = prepareSalesPerProductData({chartData}); // Pass the entire response
    const dailyData = prepareSalesPerDayData(salesData);
    const productDailyData = prepareSalesPerProductPerDayData({dailyChartData: dailyChartData}); // Pass the stored dailyChartData

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

  // Add this function to handle Excel export
  function exportToExcel() {
    // Prepare the data for export
    const exportData = filteredSalesData.map(sale => ({
      Staff: sale.username,
      Product: sale.product_name,
      Quantity: sale.quantity,
      'Customer Name': sale.customer_name,
      'Amount Paid': `₱${parseFloat(sale.amount_paid).toFixed(2)}`,
      'Total Amount': `₱${parseFloat(sale.total_amount).toFixed(2)}`,
      Date: new Date(sale.order_date).toLocaleDateString()
    }));

    // Create worksheet
    const ws = XLSX.utils.json_to_sheet(exportData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Sales Data');

    // Generate and download file
    XLSX.writeFile(wb, `sales_export_${new Date().toISOString().split('T')[0]}.xlsx`);
  }

  // Add print function
  function printCharts() {
    const printWindow = window.open('', '_blank');
    const currentDate = new Date().toLocaleDateString();
    
    const htmlContent = `
      <!DOCTYPE html>
      <html>
        <head>
          <title>Sales Reports</title>
          <style>
            body { 
              padding: 20px; 
              font-family: Arial, sans-serif;
              max-width: 1200px;
              margin: 0 auto;
            }
            .report-header { 
              text-align: center; 
              margin-bottom: 30px;
            }
            .charts-grid { 
              display: grid;
              grid-template-columns: repeat(2, 1fr);
              gap: 20px;
              width: 100%;
            }
            .chart-container { 
              page-break-inside: avoid;
              background: white;
              padding: 15px;
              border-radius: 8px;
              box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            @media print { 
              @page { 
                size: landscape;
                margin: 1cm;
              }
              .charts-grid {
                grid-template-columns: repeat(2, 1fr);
              }
              .chart-container {
                box-shadow: none;
                border: 1px solid #eee;
              }
            }
          </style>
        </head>
        <body>
          <div class="report-header">
            <h1>Sales Reports</h1>
            <p>Generated on ${currentDate}</p>
          </div>
          <div class="charts-grid">
            <div class="chart-container">
              ${(document.querySelector("#salesPerPeriodChart") || {}).innerHTML || ''}
            </div>
            <div class="chart-container">
              ${(document.querySelector("#salesPerProductChart") || {}).innerHTML || ''}
            </div>
            <div class="chart-container">
              ${(document.querySelector("#salesPerDayChart") || {}).innerHTML || ''}
            </div>
            <div class="chart-container">
              ${(document.querySelector("#salesPerProductPerDayChart") || {}).innerHTML || ''}
            </div>
          </div>
          <script src="https://cdn.jsdelivr.net/npm/apexcharts"><\/script>
          <script>
            window.onload = function() { window.print(); };
          <\/script>
        </body>
      </html>
    `;

    printWindow.document.write(htmlContent);
    printWindow.document.close();
  }

  // Update the deleteOrder function
  async function deleteOrder(orderId: number) {
    if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await ApiService.delete('delete-order', {
        order_id: orderId,
        user_id: $userStore.userId
      });

      if (response.status) {
        // Refresh the sales data
        const refreshResponse = await ApiService.get('get-sales-data');
        if (refreshResponse.status) {
          salesData = refreshResponse.data;
          chartData = refreshResponse.chartData;
          dailyChartData = refreshResponse.dailyChartData;
          filteredSalesData = salesData;
          await initializeCharts();
          alert('Order deleted successfully');
        }
      } else {
        alert('Failed to delete order: ' + response.message);
      }
    } catch (error) {
      console.error('Error deleting order:', error);
      alert('Error deleting order. Please try again.');
    }
  }

  // Update deleteAllOrders to only delete filtered data
  async function deleteAllOrders() {
    if (filteredSalesData.length === 0) {
      alert('No data to delete');
      return;
    }

    if (!confirm('Are you sure you want to delete ALL filtered orders? This action cannot be undone!')) {
      return;
    }

    try {
      const orderIds = filteredSalesData.map(sale => sale.order_id);
      const response = await ApiService.delete('delete-filtered-orders', {
        order_ids: orderIds,
        user_id: $userStore.userId
      });

      if (response.status) {
        // Refresh the sales data
        const refreshResponse = await ApiService.get('get-sales-data');
        if (refreshResponse.status) {
          salesData = refreshResponse.data;
          chartData = refreshResponse.chartData;
          dailyChartData = refreshResponse.dailyChartData;
          filteredSalesData = salesData;
          await initializeCharts();
          alert('Selected orders deleted successfully');
        }
      } else {
        alert('Failed to delete orders: ' + response.message);
      }
    } catch (error) {
      console.error('Error deleting orders:', error);
      alert('Error deleting orders. Please try again.');
    }
  }

  // Add this function to handle archiving
  async function archiveSale(orderId: number) {
    if (!confirm('Are you sure you want to archive this sale? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await ApiService.post('archive-sales', {
        order_id: orderId,
        user_id: $userStore.userId
      });

      if (response.status) {
        // Refresh the sales data
        const refreshResponse = await ApiService.get('get-sales-data');
        if (refreshResponse.status) {
          salesData = refreshResponse.data;
          chartData = refreshResponse.chartData;
          dailyChartData = refreshResponse.dailyChartData;
          filteredSalesData = salesData;
          await initializeCharts();
          alert('Sale archived successfully');
        }
      } else {
        alert('Failed to archive sale: ' + response.message);
      }
    } catch (error) {
      console.error('Error archiving sale:', error);
      alert('Error archiving sale. Please try again.');
    }
  }

  // Add this function to handle archiving filtered data
  async function archiveFilteredSales() {
    if (filteredSalesData.length === 0) {
      alert('No data to archive');
      return;
    }

    if (!confirm('Are you sure you want to archive all filtered sales? This action cannot be undone.')) {
      return;
    }

    try {
      const orderIds = filteredSalesData.map(sale => sale.order_id);
      const response = await ApiService.post('archive-filtered-sales', {
        order_ids: orderIds,
        user_id: $userStore.userId
      });

      if (response.status) {
        // Refresh the sales data
        const refreshResponse = await ApiService.get('get-sales-data');
        if (refreshResponse.status) {
          salesData = refreshResponse.data;
          chartData = refreshResponse.chartData;
          dailyChartData = refreshResponse.dailyChartData;
          filteredSalesData = salesData;
          await initializeCharts();
          alert('Selected sales archived successfully');
        }
      } else {
        alert('Failed to archive sales: ' + response.message);
      }
    } catch (error) {
      console.error('Error archiving sales:', error);
      alert('Error archiving sales. Please try again.');
    }
  }

  // Pagination settings
  let itemsPerPage = 25;
  let currentPage = 1;
  let totalPages = 0;
  let displayedData: any[] = [];

  // Calculate total pages and update displayed data whenever filtered data changes
  $: {
    totalPages = Math.ceil(filteredSalesData.length / itemsPerPage);
    displayedData = getPageData(currentPage);
  }

  // Function to get data for a specific page
  function getPageData(page: number): any[] {
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredSalesData.length);
    return filteredSalesData.slice(startIndex, endIndex);
  }

  // Function to handle page changes
  function changePage(newPage: number) {
    if (newPage >= 1 && newPage <= totalPages) {
      currentPage = newPage;
      displayedData = getPageData(currentPage);
    }
  }

  // Reset to page 1 when filters change
  $: {
    searchQuery;
    selectedDay;
    selectedMonth;
    selectedYear;
    currentPage = 1;
  }

  // Function to get page numbers to display
  function getPageNumbers(): (number | string)[] {
    const pages: (number | string)[] = [];
    const maxVisiblePages = 5;
    
    if (totalPages <= maxVisiblePages) {
      return Array.from({ length: totalPages }, (_, i) => i + 1);
    }

    // Always show first page
    pages.push(1);
    
    if (currentPage > 3) {
      pages.push('...');
    }

    // Show pages around current page
    for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
      pages.push(i);
    }

    if (currentPage < totalPages - 2) {
      pages.push('...');
    }

    // Always show last page if there is more than one page
    if (totalPages > 1) {
      pages.push(totalPages);
    }

    return pages;
  }

  // Add helper function to check if user is admin
  $: isAdmin = ($userStore.role ?? 0) === 1;

  // Add these variables
  let showVerificationModal = false;
  let saleToDelete: number | null = null;
  let adminCredentials = {
    username: '',
    password: ''
  };

  // Add these functions
  function showAdminVerification(orderId: number) {
    saleToDelete = orderId;
    showVerificationModal = true;
  }

  function closeVerificationModal() {
    showVerificationModal = false;
    saleToDelete = null;
    adminCredentials = {
      username: '',
      password: ''
    };
  }

  async function verifyAdminAndDelete(event: Event) {
    event.preventDefault();
    
    try {
      // Verify admin credentials
      const verifyResult = await ApiService.post<{status: boolean; role?: number}>('verify-admin', {
        username: adminCredentials.username,
        password: adminCredentials.password
      });

      if (verifyResult.status && verifyResult.role === 1) {
        // If verified, proceed with deletion
        if (saleToDelete) {
          await archiveSale(saleToDelete);
        }
        closeVerificationModal();
      } else {
        alert('Invalid admin credentials');
      }
    } catch (error) {
      console.error('Verification error:', error);
      alert('Failed to verify admin credentials');
    }
  }

  onMount(async () => {
    try {
      const result = await ApiService.get<SalesData>('get-sales-data');
      if (result.status) {
        salesData = result.data;
        chartData = result.chartData;
        dailyChartData = result.dailyChartData;
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

<Header {y} {innerWidth} {innerHeight} />

<div class="content">
  <div class="sales-container">
    <h2 class="text-2xl font-bold mb-4">Sales History</h2>
    
    <!-- Only show charts and action buttons for admin -->
    {#if isAdmin}
      <div class="charts-container">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="chart-wrapper">
            <div id="salesPerPeriodChart"></div>
          </div>
          <div class="chart-wrapper">
            <div id="salesPerProductChart"></div>
          </div>
          <div class="chart-wrapper">
            <div id="salesPerDayChart"></div>
          </div>
          <div class="chart-wrapper">
            <div id="salesPerProductPerDayChart"></div>
          </div>
        </div>
      </div>

      <!-- Admin-only action buttons -->
      <div class="filter-buttons">
        <button
          on:click={exportToExcel}
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
        >
          Export to Excel
        </button>
        <button
          on:click={printCharts}
          class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
        >
          Print PDF
        </button>
        <button
          on:click={deleteAllOrders}
          class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
        >
          Delete All Orders
        </button>
      </div>
    {/if}

    <!-- Filters and table are shown to all users -->
    <div class="filters">
      <!-- Search Bar -->
      <div class="flex items-center space-x-4">
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Search by product name..."
          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
        />
      </div>
      
      <!-- Date Filters -->
      <div class="filter-buttons">
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

    <!-- Table -->
    <div class="overflow-x-auto">
      <!-- Moved Pagination controls to top -->
      {#if totalPages > 0}
        <div class="pagination-controls mb-4">
          <div class="pagination-info">
            Showing {((currentPage - 1) * itemsPerPage) + 1} to {Math.min(currentPage * itemsPerPage, filteredSalesData.length)} of {filteredSalesData.length} entries
          </div>
          
          <div class="pagination-buttons">
            <button 
              class="pagination-button"
              disabled={currentPage === 1}
              on:click={() => changePage(currentPage - 1)}
            >
              Previous
            </button>

            {#each getPageNumbers() as pageNum}
              {#if typeof pageNum === 'string'}
                <span class="pagination-ellipsis">{pageNum}</span>
              {:else}
                <button 
                  class="pagination-button {pageNum === currentPage ? 'active' : ''}"
                  on:click={() => changePage(pageNum)}
                >
                  {pageNum}
                </button>
              {/if}
            {/each}

            <button 
              class="pagination-button"
              disabled={currentPage === totalPages}
              on:click={() => changePage(currentPage + 1)}
            >
              Next
            </button>
          </div>
        </div>
      {/if}

      <table class="responsive-table">
        <thead>
          <tr>
            <th>Staff</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Customer Name</th>
            <th>Amount Paid</th>
            <th>Total Amount</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {#each displayedData as sale}
            <tr>
              <td data-label="Staff">{sale.username}</td>
              <td data-label="Product">{sale.product_name}</td>
              <td data-label="Quantity">{sale.quantity}</td>
              <td data-label="Customer">{sale.customer_name}</td>
              <td data-label="Amount Paid">₱{parseFloat(sale.amount_paid).toFixed(2)}</td>
              <td data-label="Total">₱{parseFloat(sale.total_amount).toFixed(2)}</td>
              <td data-label="Date">{new Date(sale.order_date).toLocaleDateString()}</td>
              <!-- Show delete action for all users -->
              <td class="actions" data-label="Actions">
                {#if $userStore.role === 1}
                  <!-- Admin delete button -->
                  <button
                    on:click={() => archiveSale(sale.order_id)}
                    class="text-blue-600 hover:text-blue-900 font-medium"
                  >
                    Delete
                  </button>
                {:else}
                  <!-- Staff delete button with admin verification -->
                  <button
                    on:click={() => showAdminVerification(sale.order_id)}
                    class="text-blue-600 hover:text-blue-900 font-medium"
                  >
                    Request Delete
                  </button>
                {/if}
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add this modal markup right before the closing </div> of the content div -->

{#if showVerificationModal}
  <div class="modal-overlay">
    <div class="modal-content">
      <h2 class="text-xl font-bold mb-4">Admin Verification Required</h2>
      <form on:submit|preventDefault={verifyAdminAndDelete}>
        <div class="form-group">
          <label for="adminUsername">Admin Username</label>
          <input
            type="text"
            id="adminUsername"
            bind:value={adminCredentials.username}
            required
          />
        </div>
        <div class="form-group">
          <label for="adminPassword">Admin Password</label>
          <input
            type="password"
            id="adminPassword"
            bind:value={adminCredentials.password}
            required
          />
        </div>
        <div class="button-group">
          <button type="button" class="btn-cancel" on:click={closeVerificationModal}>
            Cancel
          </button>
          <button type="submit" class="btn-verify">
            Verify & Delete
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .content {
    margin-top: 4rem;
    padding: 0;
    background-color: #fefae0;
    font-family: 'DynaPuff', cursive;
    min-height: calc(100vh - 4rem);
    display: flex;
    flex-direction: column;
  }

  .sales-container {
    background: #faedcd;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    flex: 1;
    height: calc(100vh - 4rem);
    overflow-y: auto;
  }

  .charts-container {
    padding: 1rem;
    background: #faedcd;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
  }

  .filters {
    position: sticky;
    top: 0;
    background: #faedcd;
    padding: 1rem;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
  }

  .filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
  }

  @media (max-width: 1024px) {
    .filters {
      flex-direction: column;
      padding: 0.75rem;
    }
    
    .filters > * {
      width: 100%;
      margin-bottom: 0.75rem;
    }

    .sales-container {
      padding: 0.75rem;
    }

    .filter-buttons {
      display: flex;
      overflow-x: auto;
      gap: 0.5rem;
      padding-bottom: 0.5rem;
    }

    .filter-buttons button,
    .filter-buttons select {
      flex: 0 0 auto;
      min-width: 150px;
      white-space: nowrap;
    }

    .charts-container {
      padding: 0.75rem;
      margin: 0.75rem;
    }
  }

  .chart-wrapper {
    min-height: 400px;
    background-color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  /* Add global font */
  :global(body) {
    font-family: 'DynaPuff', cursive;
  }

  .pagination-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .pagination-info {
    color: #6b7280;
  }

  .pagination-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }

  .pagination-button {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
  }

  .pagination-button:hover:not(:disabled) {
    background: #f3f4f6;
  }

  .pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-button.active {
    background: #d4a373;
    color: white;
    border-color: #d4a373;
  }

  .pagination-ellipsis {
    padding: 0.5rem;
    color: #6b7280;
  }

  @media (max-width: 768px) {
    .pagination-controls {
      flex-direction: column;
      gap: 1rem;
    }

    .pagination-buttons {
      width: 100%;
      justify-content: center;
      flex-wrap: wrap;
    }
  }

  /* Update/add these modal styles */
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
    background: #faedcd;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
  }

  .button-group {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
  }

  .btn-verify {
    background: #4CAF50;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
  }

  .btn-verify:hover {
    background: #45a049;
  }

  .btn-cancel {
    background: #f44336;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
  }

  .btn-cancel:hover {
    background: #da190b;
  }

  /* Make sure buttons are clickable */
  button {
    cursor: pointer;
  }

  /* Ensure modal is above other content */
  :global(body) {
    position: relative;
  }
</style>

<!-- Add font import in the head of the document -->
<svelte:head>
  <link href="https://fonts.googleapis.com/css2?family=DynaPuff&display=swap" rel="stylesheet">
</svelte:head>