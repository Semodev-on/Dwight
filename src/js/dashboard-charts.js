// Function to initialize appointment chart
function initAppointmentChart(data) {
    const ctx = document.getElementById('appointmentsChart').getContext('2d');
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.counts,
                backgroundColor: [
                    '#36a2eb',  // Blue for Scheduled
                    '#4CAF50',  // Green for Completed
                    '#ff6384',  // Red for Cancelled
                    '#ffce56'   // Yellow for others
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Appointment Status Distribution',
                    font: {
                        size: 16
                    }
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Function to initialize patient trends chart
function initPatientTrendsChart(data) {
    const ctx = document.getElementById('patientsChart').getContext('2d');
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.months,
            datasets: [{
                label: 'New Patient Registrations',
                data: data.counts,
                borderColor: '#36a2eb',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Patient Registration Trends',
                    font: {
                        size: 16
                    }
                },
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Export the functions
window.DashboardCharts = {
    initAppointmentChart,
    initPatientTrendsChart
}; 