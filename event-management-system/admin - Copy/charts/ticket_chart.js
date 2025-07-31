// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get the chart canvas
    const ctx = document.getElementById('ticketSalesChart').getContext('2d');
    
    // Create the chart
    const ticketSalesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.dates,
            datasets: [
                {
                    label: 'Tickets Sold',
                    data: salesData.tickets,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    yAxisID: 'y-tickets',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Revenue ($)',
                    data: salesData.revenue,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    yAxisID: 'y-revenue',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Ticket Sales and Revenue Over Time'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 1) {
                                label += '$' + context.parsed.y.toFixed(2);
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                'y-tickets': {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Tickets Sold'
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                },
                'y-revenue': {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
    
    // Add resize handler
    window.addEventListener('resize', function() {
        ticketSalesChart.resize();
    });
}); 