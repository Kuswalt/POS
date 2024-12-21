<script lang="ts">

  import Header from '$lib/header.svelte';
  import { onMount, onDestroy } from 'svelte';
  import { browser } from '$app/environment';
  import * as XLSX from 'xlsx';
  import { ApiService } from '$lib/services/api';
  import { userStore } from '$lib/auth';
  import { encryptionService } from '$lib/services/encryption';

  let y = 0;
  let innerWidth = 0;
  let innerHeight = 0;
  
  // Add state for active tab
  let activeTab: 'sales' | 'archived' = 'sales';
  
  // Update the data storage variables
  let salesData = [];
  let archivedSalesData: any[] = [];
  let chartData = {};  
  let dailyChartData = {};
  let filteredSalesData = [];
  let filteredArchivedData: any[] = [];
  
  // Filter states
  let searchQuery = '';
  let selectedDay = '';
  let selectedMonth = '';
  let selectedYear = '';
  
  // Unique years and months for filters
  $: years = [...new Set([
    ...salesData.map(sale => new Date(sale.order_date).getFullYear()),
    ...archivedSalesData.map(sale => new Date(sale.archived_date).getFullYear())
  ])].sort((a, b) => b - a);
  $: months = [...Array(12)].map((_, i) => ({
    value: i + 1,
    label: new Date(2000, i, 1).toLocaleString('default', { month: 'long' })
  }));
  $: days = [...Array(31)].map((_, i) => i + 1);

  // Filter function
  $: {
    if ($userStore.role === 0) {
      // If role is 0 (staff), only show their own transactions
      filteredSalesData = salesData.filter(sale => 
        sale.username === $userStore.username
      );
    } else {
      // If role is 1 (admin), show all transactions
      filteredSalesData = salesData.filter(sale => 
        sale.product_name.toLowerCase().includes(searchQuery.toLowerCase()) &&
        (!selectedYear || new Date(sale.order_date).getFullYear() === parseInt(selectedYear)) &&
        (!selectedMonth || new Date(sale.order_date).getMonth() + 1 === parseInt(selectedMonth)) &&
        (!selectedDay || new Date(sale.order_date).getDate() === parseInt(selectedDay))
      );
    }
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
  async function exportToExcel() {
    try {
      // Format the data for Excel
      const excelData = filteredSalesData.map(sale => ({
        'Staff': sale.username,
        'Product': sale.product_name,
        'Quantity': sale.quantity,
        'Customer Name': sale.customer_name,
        'Amount Paid': parseFloat(sale.amount_paid).toFixed(2),
        'Total Amount': parseFloat(sale.total_amount).toFixed(2),
        'Discount Type': sale.discount_type || 'None',
        'Discount Amount': sale.discount_amount ? parseFloat(sale.discount_amount).toFixed(2) : '0.00',
        'Original Amount': sale.original_amount ? parseFloat(sale.original_amount).toFixed(2) : sale.total_amount,
        'Date': new Date(sale.order_date).toLocaleString()
      }));

      // Create workbook and worksheet
      const workbook = XLSX.utils.book_new();
      const worksheet = XLSX.utils.json_to_sheet(excelData);

      // Set column widths
      const columnWidths = [
        { wch: 15 },  // Staff
        { wch: 30 },  // Product
        { wch: 10 },  // Quantity
        { wch: 20 },  // Customer Name
        { wch: 15 },  // Amount Paid
        { wch: 15 },  // Total Amount
        { wch: 15 },  // Discount Type
        { wch: 15 },  // Discount Amount
        { wch: 15 },  // Original Amount
        { wch: 20 },  // Date
      ];
      worksheet['!cols'] = columnWidths;

      // Add the worksheet to the workbook
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Sales Data');

      // Generate filename with current date
      const date = new Date();
      const filename = `sales_report_${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}.xlsx`;

      // Save the file
      XLSX.writeFile(workbook, filename);
    } catch (error) {
      console.error('Error exporting to Excel:', error);
      alert('Failed to export data to Excel');
    }
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
  // async function deleteOrder(orderId: number) {
  //   if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
  //     return;
  //   }

  //   try {
  //     const response = await ApiService.delete('delete-order', {
  //       order_id: orderId,
  //       user_id: $userStore.userId
  //     });

  //     if (response.status) {
  //       // Refresh the sales data
  //       const refreshResponse = await ApiService.get('get-sales-data');
  //       if (refreshResponse.status) {
  //         salesData = refreshResponse.data;
  //         chartData = refreshResponse.chartData;
  //         dailyChartData = refreshResponse.dailyChartData;
  //         filteredSalesData = salesData;
  //         await initializeCharts();
  //         alert('Order deleted successfully');
  //       }
  //     } else {
  //       alert('Failed to delete order: ' + response.message);
  //     }
  //   } catch (error) {
  //     console.error('Error deleting order:', error);
  //     alert('Error deleting order. Please try again.');
  //   }
  // }

  // Update deleteAllOrders to only delete filtered data
  async function deleteAllOrders() {
    if (filteredSalesData.length === 0) {
      alert('No data to archive');
      return;
    }

    if (!confirm('Are you sure you want to archive ALL filtered orders? This action cannot be undone!')) {
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
          alert('Selected orders archived successfully');
        }
      } else {
        alert('Failed to archive orders: ' + response.message);
      }
    } catch (error) {
      console.error('Error archiving orders:', error);
      alert('Error archiving orders. Please try again.');
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

  // Add function to handle tab change
  // async function handleTabChange(tab: 'sales' | 'archived') {
  //   activeTab = tab;
  //   try {
  //     if (tab === 'archived') {
  //       const archivedResult = await ApiService.get<{
  //         status: boolean;
  //         data: Array<{
  //           archive_id: number;
  //           order_id: number;
  //           username: string;
  //           customer_id: number;
  //           order_date: string;
  //           total_amount: number;
  //           payment_status: string;
  //           archived_date: string;
  //           discount_type: string | null;
  //           discount_amount: number | null;
  //           original_amount: number | null;
  //           archived_by_user: string;
  //         }>;
  //       }>('get-archived-sales');
        
  //       if (archivedResult.status) {
  //         archivedSalesData = archivedResult.data;
  //         filteredArchivedData = archivedSalesData;
  //       }
  //     } else {
  //       const result = await ApiService.get('get-sales-data');
  //       if (result.status) {
  //         salesData = result.data;
  //         chartData = result.chartData;
  //         dailyChartData = result.dailyChartData;
  //         filteredSalesData = salesData;
  //       }
  //     }
  //   } catch (error) {
  //     console.error(`Error fetching ${tab} data:`, error);
  //   }
  // }

  // Update the onMount function to handle the filtered data for charts
  onMount(async () => {
    try {
      if (activeTab === 'sales') {
        const result = await ApiService.get('get-sales-data');
        if (result.status) {
          salesData = result.data;
          chartData = result.chartData;
          dailyChartData = result.dailyChartData;
          filteredSalesData = salesData;
        }
      }
      await fetchArchivedSales();
      await initializeCharts();
    } catch (error) {
      console.error('Error fetching data:', error);
    }
  });

  // Update the displayedData computed property
  $: displayedData = filteredSalesData.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  // First, add a watch on activeTab
  $: if (activeTab === 'sales' && salesData.length > 0) {
    // Reinitialize charts when switching back to active sales
    setTimeout(() => {
      initializeCharts();
    }, 0);
  }

  function clearCharts() {
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
  }

  // Update the switchTab function
  async function switchTab(tab: 'sales' | 'archived') {
    clearCharts();
    activeTab = tab;
    if (tab === 'sales') {
      searchQuery = '';
      selectedDay = '';
      selectedMonth = '';
      selectedYear = '';
      currentPage = 1;
      
      // Add reload functionality for sales data
      try {
        const result = await ApiService.get('get-sales-data');
        if (result.status) {
          salesData = result.data;
          chartData = result.chartData;
          dailyChartData = result.dailyChartData;
          filteredSalesData = salesData;
          
          // Initialize charts after data is loaded
          setTimeout(() => {
            initializeCharts();
          }, 0);
        }
      } catch (error) {
        console.error('Error reloading sales data:', error);
      }
    } else if (tab === 'archived') {
      fetchArchivedSales();
    }
  }

  // Update the fetchArchivedSales function
  async function fetchArchivedSales() {
    try {
      const response = await ApiService.get('get-archived-sales');
      if (Array.isArray(response)) {
        filteredArchivedData = response;
      }
    } catch (error) {
      console.error('Error:', error);
      filteredArchivedData = [];
    }
  }

  // Call this in onMount
  onMount(() => {
    if (activeTab === 'archived') {
      fetchArchivedSales();
    }
  });

  // Update the reactive statement for archived data to only filter by user role
  $: {
    if ($userStore.role === 0) {
      // Staff can only see their own archived transactions
      filteredArchivedData = archivedSalesData.filter(sale => 
        sale.username === $userStore.username
      );
    } else {
      // Admin can see all archived transactions
      filteredArchivedData = archivedSalesData;
    }
  }

  // Make sure the pagination updates when filters change
  $: {
    if (activeTab === 'archived') {
      totalPages = Math.ceil(filteredArchivedData.length / itemsPerPage);
      displayedData = filteredArchivedData.slice(
        (currentPage - 1) * itemsPerPage,
        currentPage * itemsPerPage
      );
    }
  }

  // Reset pagination when changing archive filters
  $: {
    selectedYear;
    selectedMonth;
    selectedDay;
    if (activeTab === 'archived') {
      currentPage = 1;
    }
  }

  // Add reset function for archive filters
  function resetArchiveFilters() {
    selectedYear = '';
    selectedMonth = '';
    selectedDay = '';
  }

  // Add these functions to handle
  async function restoreArchivedSale(archiveId: number) {
    if (!confirm('Are you sure you want to restore this archived sale?')) {
      return;
    }

    try {
      const response = await ApiService.post('restore-archived-sale', {
        archive_id: archiveId,
        user_id: $userStore.userId
      });

      if (response.status) {
        await fetchArchivedSales(); // Refresh archived sales data
        const salesResult = await ApiService.get('get-sales-data');
        if (salesResult.status) {
          salesData = salesResult.data;
          chartData = salesResult.chartData;
          dailyChartData = salesResult.dailyChartData;
          filteredSalesData = salesData;
          await initializeCharts();
        }
        alert('Sale restored successfully');
      } else {
        alert('Failed to restore sale: ' + response.message);
      }
    } catch (error) {
      console.error('Error restoring sale:', error);
      alert('Error restoring sale. Please try again.');
    }
  }

  async function deleteArchivedSale(archiveId: number) {
    if (!confirm('Are you sure you want to permanently delete this archived sale? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await ApiService.delete('delete-archived-sale', {
        archive_id: archiveId,
        user_id: $userStore.userId
      });

      if (response.status) {
        await fetchArchivedSales(); // Refresh the archived sales list
        alert('Sale deleted successfully');
      } else {
        alert('Failed to delete sale: ' + response.message);
      }
    } catch (error) {
      console.error('Error deleting sale:', error);
      alert('Error deleting sale. Please try again.');
    }
  }
</script>

<Header {y} {innerWidth} {innerHeight} />

<div class="content">
  <div class="sales-container">
    <!-- Charts section -->
    {#if activeTab === 'sales' && $userStore.role === 1}
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
    {/if}

    <h2 class="text-2xl font-bold mb-4">Sales History</h2>
    
    <!-- Add tab navigation -->
    {#if $userStore.role === 1}
      <div class="tabs-container">
        <button 
          class="tab-button {activeTab === 'sales' ? 'active' : ''}" 
          on:click={() => switchTab('sales')}
        >
          Active Sales
        </button>
        <button 
          class="tab-button {activeTab === 'archived' ? 'active' : ''}" 
          on:click={() => switchTab('archived')}
        >
          Archived Sales
        </button>
      </div>
    {/if}

    <!-- Filters section -->
    {#if $userStore.role === 1}
      <div class="filters">
        {#if activeTab === 'sales'}
          <input
            type="text"
            placeholder="Search by product name..."
            bind:value={searchQuery}
            class="search-input"
          />
          
          <select bind:value={selectedYear} class="filter-select">
            <option value="">All Years</option>
            {#each years as year}
              <option value={year}>{year}</option>
            {/each}
          </select>

          <select bind:value={selectedMonth} class="filter-select">
            <option value="">All Months</option>
            {#each months as month}
              <option value={month.value}>{month.label}</option>
            {/each}
          </select>

          <select bind:value={selectedDay} class="filter-select">
            <option value="">All Days</option>
            {#each days as day}
              <option value={day}>{day}</option>
            {/each}
          </select>

          <button on:click={resetFilters} class="reset-button">
            Reset Filters
          </button>
        {/if}
      </div>
    {/if}

    <!-- Add the action buttons container -->
    {#if activeTab === 'sales' && $userStore.role === 1}
      <div class="action-buttons flex gap-4 mb-4">
        <button 
          on:click={exportToExcel}
          class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"
        >
          <i class="fas fa-file-excel"></i>
          Export to Excel
        </button>
        
        <button 
          on:click={printCharts}
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"
        >
          <i class="fas fa-print"></i>
          Print Charts
        </button>

        {#if filteredSalesData.length > 0}
          <button 
            on:click={deleteAllOrders}
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"
          >
            <i class="fas fa-archive"></i>
            Archive All Filtered
          </button>
        {/if}
      </div>
    {/if}

    <!-- Table section -->
    <div class="overflow-x-auto">
      {#if activeTab === 'sales'}
        <!-- Pagination controls above table -->
        <div class="pagination-controls">
          <div class="pagination-info">
            Showing {Math.min((currentPage - 1) * itemsPerPage + 1, filteredSalesData.length)} 
            to {Math.min(currentPage * itemsPerPage, filteredSalesData.length)} 
            of {filteredSalesData.length} entries
          </div>
          
          <div class="pagination-buttons">
            <button 
              class="pagination-button" 
              on:click={() => changePage(1)}
              disabled={currentPage === 1}
            >
              First
            </button>
            
            <button 
              class="pagination-button"
              on:click={() => changePage(currentPage - 1)}
              disabled={currentPage === 1}
            >
              Previous
            </button>
            
            {#each getPageNumbers() as pageNum}
              {#if pageNum === '...'}
                <span class="pagination-ellipsis">...</span>
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
              on:click={() => changePage(currentPage + 1)}
              disabled={currentPage === totalPages}
            >
              Next
            </button>
            
            <button 
              class="pagination-button"
              on:click={() => changePage(totalPages)}
              disabled={currentPage === totalPages}
            >
              Last
            </button>
          </div>
        </div>

        <!-- Existing sales table -->
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Staff</th>
              <th>Product</th>
              <th>Quantity</th>
              <th>Customer Name</th>
              <th>Amount Paid</th>
              <th>Total Amount</th>
              <th>Discount Type</th>
              <th>Discount Amount</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each displayedData as sale}
              <tr>
                <td data-label="Order ID">{sale.order_id}</td>
                <td data-label="Staff">{sale.username}</td>
                <td data-label="Product">{sale.product_name}</td>
                <td data-label="Quantity">{sale.quantity}</td>
                <td data-label="Customer">{sale.customer_name}</td>
                <td data-label="Amount Paid">₱{parseFloat(sale.amount_paid).toFixed(2)}</td>
                <td data-label="Total">₱{parseFloat(sale.total_amount).toFixed(2)}</td>
                <td data-label="Discount Type">
                  {#if sale.discount_type && sale.discount_type !== 'null'}
                    <span class="discount-badge {sale.discount_type.toLowerCase()}">
                      {sale.discount_type}
                    </span>
                  {:else}
                    -
                  {/if}
                </td>
                <td data-label="Discount Amount">
                  {#if sale.discount_amount && parseFloat(sale.discount_amount) > 0}
                    <span class="discount-amount">
                      -₱{parseFloat(sale.discount_amount).toFixed(2)}
                    </span>
                  {:else}
                    -
                  {/if}
                </td>
                <td data-label="Date">{new Date(sale.order_date).toLocaleDateString()}</td>
                <td class="actions" data-label="Actions">
                  {#if $userStore.role === 1}
                    <!-- Admin delete button -->
                    <button
                      on:click={() => archiveSale(sale.order_id)}
                      class="text-blue-600 hover:text-blue-900 font-medium"
                    >
                      Archive
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
      {:else}
        <!-- Archived sales table -->
        <div class="table-container">
          <table class="responsive-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Staff</th>
                <th>Total Amount</th>
                <th>Payment Status</th>
                <th>Order Date</th>
                <th>Archived Date</th>
                <th>Archived By</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {#if filteredArchivedData && filteredArchivedData.length > 0}
                {#each filteredArchivedData as sale}
                  <tr>
                    <td>{sale.order_id}</td>
                    <td>{sale.username}</td>
                    <td>₱{parseFloat(sale.total_amount).toFixed(2)}</td>
                    <td>{sale.payment_status}</td>
                    <td>{new Date(sale.order_date).toLocaleString()}</td>
                    <td>{new Date(sale.archived_date).toLocaleString()}</td>
                    <td>{sale.archived_by_user}</td>
                    <td class="actions">
                      {#if $userStore.role === 1}
                        <button
                          on:click={() => restoreArchivedSale(sale.archive_id)}
                          class="text-green-600 hover:text-green-900 font-medium mr-2"
                        >
                          Restore
                        </button>
                        <button
                          on:click={() => deleteArchivedSale(sale.archive_id)}
                          class="text-red-600 hover:text-red-900 font-medium"
                        >
                          Delete
                        </button>
                      {/if}
                    </td>
                  </tr>
                {/each}
              {:else}
                <tr>
                  <td colspan="8" class="text-center">No archived sales data available</td>
                </tr>
              {/if}
            </tbody>
          </table>
        </div>
      {/if}
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

    .pagination-info {
      text-align: center;
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
    background: #d4a373;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
  }

  .btn-verify:hover {
    background: #ccd5ae;
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

  .discount-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
  }

  .discount-badge.senior {
    background-color: #93c5fd;
    color: #1e40af;
  }

  .discount-badge.pwd {
    background-color: #fde68a;
    color: #92400e;
  }

  .discount-amount {
    color: #dc2626;
    font-weight: 500;
  }

  @media (max-width: 768px) {
    td[data-label="Discount Type"],
    td[data-label="Discount Amount"] {
      text-align: right;
    }

    .discount-badge {
      width: 100%;
      text-align: center;
    }
  }

  /* Add these styles for tab navigation */
  .tabs-container {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #faedcd;
    border-radius: 0.5rem 0.5rem 0 0;
    margin-bottom: 1rem;
  }

  .tab-button {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    background: #d4a373;
    color: white;
    font-weight: 500;
    transition: all 0.2s;
    opacity: 0.7;
  }

  .tab-button:hover {
    opacity: 0.9;
  }

  .tab-button.active {
    opacity: 1;
    background: #d4a373;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  @media (max-width: 768px) {
    .tabs-container {
      padding: 0.5rem;
    }

    .tab-button {
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
    }
  }

  /* Update action buttons styles */
  .action-buttons {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #faedcd;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .action-buttons button {
    transition: all 0.2s;
    flex: 1;
    min-width: max-content;
    justify-content: center;
  }

  .action-buttons button:hover {
    transform: translateY(-1px);
  }

  @media (max-width: 768px) {
    .action-buttons {
      flex-direction: column;
      padding: 0.75rem;
    }

    .action-buttons button {
      width: 100%;
      min-width: 0;
    }
  }

  .table-container {
    margin: 1rem 0;
    overflow-x: auto;
  }

  .responsive-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .responsive-table th,
  .responsive-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
  }

  .responsive-table th {
    background: #d4a373;
    color: white;
    font-weight: 600;
  }

  .discount-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .discount-badge.senior {
    background-color: #e9edc9;
    color: #d4a373;
  }

  .discount-badge.pwd {
    background-color: #d4a373;
    color: #fefae0;
  }

  .discount-amount {
    color: #dc2626;
    font-weight: 500;
  }
   
          .responsive-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
          }
        
          .responsive-table th,
          .responsive-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
          }
        
          .responsive-table th {
            background: #d4a373;
            color: white;
            font-weight: 600;
          }
        
          @media (max-width: 768px) {
            .responsive-table th,
            .responsive-table td {
              display: block;
              width: 100%;
              box-sizing: border-box;
            }
        
            .responsive-table td {
              text-align: right;
              padding-left: 50%;
              position: relative;
            }
        
            .responsive-table td::before {
              content: attr(data-label);
              position: absolute;
              left: 0;
              width: 50%;
              padding-left: 1rem;
              font-weight: bold;
              text-align: left;
              background: #f5f5f5;
            }
          }
</style>

<!-- Add font import in the head of the document -->
<svelte:head>
  <link href="https://fonts.googleapis.com/css2?family=DynaPuff&display=swap" rel="stylesheet">
</svelte:head>