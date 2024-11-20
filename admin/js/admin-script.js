jQuery(document).ready(function($) {
    // Constants and state management
    const ADMIN_COOLDOWN = 2000; // 2 seconds cooldown
    let updateInProgress = false;
    let lastUpdateTime = 0;
    let selectionMode = false;

    // Status update handler
    $('.enquiry-status').on('change', function() {
        const currentTime = Date.now();
        if (updateInProgress || (currentTime - lastUpdateTime) < ADMIN_COOLDOWN) {
            alert('Please wait before making another update.');
            return false;
        }

        const status = $(this).val();
        const enquiryId = $(this).data('enquiry-id');
        const nonce = efAdminParams.security_nonce;

        if (!status || !enquiryId) {
            alert('Invalid update parameters');
            return false;
        }

        updateInProgress = true;
        lastUpdateTime = currentTime;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_enquiry_status',
                enquiry_id: parseInt(enquiryId, 10),
                status: status,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    const statusCell = $(`#status-${enquiryId}`);
                    statusCell.text(status).addClass('status-updated');
                    setTimeout(() => statusCell.removeClass('status-updated'), 1000);
                } else {
                    alert('Update failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Status update failed:', error);
                alert('Failed to update status. Please try again.');
            },
            complete: function() {
                updateInProgress = false;
            }
        });
    });

    // Bulk actions functionality
    function enterSelectionMode() {
        $('#bulk-update-form').show();
        $('.ef-check-column').show();
        $('#manage-entries').hide();
    }
    
    function exitSelectionMode() {
        $('#bulk-update-form').hide();
        $('.ef-check-column').hide();
        $('#manage-entries').show();
        $('input[name="enquiry[]"]').prop('checked', false);
    }

    // Select all functionality
    $('.select-all-checkbox').change(function() {
        $('input[name="enquiry[]"]').prop('checked', $(this).prop('checked'));
    });

    // Bulk status update
    $('#bulk-status-update').click(function() {
        const selectedEnquiries = $('input[name="enquiry[]"]:checked').map(function() {
            return this.value;
        }).get();

        const newStatus = $('#bulk-status').val();

        if (!selectedEnquiries.length) {
            alert('Please select enquiries to update');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_update_enquiry_status',
                enquiries: selectedEnquiries,
                status: newStatus,
                security: efAdminParams.security_nonce
            },
            success: function(response) {
                if (response.success) {
                    selectedEnquiries.forEach(function(id) {
                        $(`#status-${id}`).text(newStatus).addClass('status-updated');
                        setTimeout(() => $(`#status-${id}`).removeClass('status-updated'), 1000);
                    });
                    exitSelectionMode();
                } else {
                    alert('Error updating statuses: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Bulk update failed:', error);
                alert('Failed to update statuses. Please try again.');
            }
        });
    });

    // Bulk delete
    $('#delete-selected').click(function() {
        if (!confirm('Are you sure you want to delete the selected entries? This action cannot be undone.')) {
            return;
        }

        const selectedEnquiries = $('input[name="enquiry[]"]:checked').map(function() {
            return this.value;
        }).get();

        if (!selectedEnquiries.length) {
            alert('Please select enquiries to delete');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_delete_enquiries',
                enquiries: selectedEnquiries,
                security: efAdminParams.security_nonce
            },
            success: function(response) {
                if (response.success) {
                    selectedEnquiries.forEach(function(id) {
                        $('tr').has(`input[value="${id}"]`).fadeOut(400, function() {
                            $(this).remove();
                        });
                    });
                    exitSelectionMode();
                } else {
                    alert('Error deleting entries: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Bulk delete failed:', error);
                alert('Failed to delete entries. Please try again.');
            }
        });
    });

    // Initialize
    exitSelectionMode(); // Start with selection mode off
    
    // Toggle selection mode
    $('#toggle-selection-mode').click(function() {
        if (selectionMode) {
            exitSelectionMode();
        } else {
            enterSelectionMode();
        }
    });

    // Chart initialization
    function initializeStatusChart() {
        const chartCanvas = document.getElementById('statusChart');
        if (!chartCanvas) return;

        try {
            const statusData = JSON.parse(chartCanvas.dataset.statusDistribution || '[]');
            const chartData = statusData.length ? statusData : [
                { status: 'Unreplied', count: 0 },
                { status: 'Replied', count: 0 },
                { status: 'Done', count: 0 }
            ];

            const ctx = chartCanvas.getContext('2d');
            
            const chartColors = {
                'Unreplied': '#FF6384',
                'Replied': '#36A2EB',
                'Done': '#FFCE56'
            };

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartData.map(item => item.status),
                    datasets: [{
                        data: chartData.map(item => parseInt(item.count)),
                        backgroundColor: chartData.map(item => chartColors[item.status] || '#999999'),
                        borderWidth: 1,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                usePointStyle: true,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        datalabels: {
                            color: '#000000',
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total ? Math.round((value / total) * 100) : 0;
                                return `${percentage}%`;
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 20,
                            bottom: 40,
                            left: 20,
                            right: 20
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } catch (error) {
            console.error('Error initializing chart:', error);
        }
    }

    // Add this function for the trend chart
    function initializeTrendChart() {
        const trendCanvas = document.getElementById('trendChart');
        if (!trendCanvas) return;

        try {
            const trendData = JSON.parse(trendCanvas.dataset.trend || '[]');
            const ctx = trendCanvas.getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: trendData.map(item => new Date(item.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Number of Enquiries',
                        data: trendData.map(item => item.count),
                        backgroundColor: '#2271b1',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Date',
                                font: {
                                    size: 11
                                }
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Number of Enquiries',
                                font: {
                                    size: 11
                                }
                            },
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 20,
                            bottom: 20,
                            left: 10,
                            right: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing trend chart:', error);
        }
    }

    // Initialize both charts
    if (typeof Chart !== 'undefined') {
        initializeStatusChart();
        initializeTrendChart();
    }
});
