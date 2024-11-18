<?php
if (!defined('ABSPATH')) {
    exit;
}

function display_enquiry_orders_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $enquiries_table = $wpdb->prefix . 'ef_enquiries';

    // Get pagination parameters
    $per_page = isset($_GET['per_page']) ? ($_GET['per_page'] === 'all' ? 'all' : max(1, intval($_GET['per_page']))) : 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

    // Get total number of enquiries
    $total_enquiries = $wpdb->get_var("SELECT COUNT(*) FROM $enquiries_table");

    // Calculate total pages
    $total_pages = $per_page === 'all' ? 1 : ceil($total_enquiries / $per_page);

    // Adjust query based on per_page setting
    if ($per_page === 'all') {
        $orders = $wpdb->get_results("SELECT * FROM $enquiries_table ORDER BY id DESC");
    } else {
        $offset = ($current_page - 1) * $per_page;
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $enquiries_table ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
    }

    echo '<div class="wrap">';
    echo '<h1>Enquiries</h1>';

    // Form to change number of enquiries per page
    echo '<form method="get" style="display: inline-block; margin-right: 10px;">';
    echo '<input type="hidden" name="page" value="ef-enquiries">';
    echo '<label for="per_page">Entries per page: </label>';
    echo '<select name="per_page" id="per_page" onchange="this.form.submit()">';
    $options = [10, 20, 50, 'all'];
    foreach ($options as $option) {
        $label = $option === 'all' ? 'All' : $option;
        $selected = $per_page == $option ? 'selected' : '';
        echo "<option value=\"$option\" $selected>$label</option>";
    }
    echo '</select>';
    echo '</form>';

    // Manage Entries button
    echo '<button id="manage-entries" class="button">Manage Entries</button>';

    // Bulk update form (initially hidden)
    echo '<form id="bulk-update-form" style="display:none; margin-top: 10px;">';
    echo '<select id="bulk-status" name="bulk_status">';
    foreach (['Unreplied', 'Replied', 'Done'] as $status) {
        echo '<option value="' . esc_attr($status) . '">' . esc_html($status) . '</option>';
    }
    echo '</select>';
    echo '<button type="submit" class="button">Update Selected</button>';
    echo '<button type="button" id="delete-selected" class="button">Delete Selected</button>';
    echo '<button type="button" id="cancel-selection" class="button">Cancel</button>';
    echo '</form>';

    if ($orders) {
        echo '<form id="enquiries-form">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th class="manage-column column-cb check-column ef-check-column"><input type="checkbox" id="cb-select-all-1"></th><th>ID</th><th>Subject</th><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        foreach ($orders as $order) {
            echo '<tr>';
            echo '<th scope="row" class="check-column ef-check-column"><input type="checkbox" name="enquiry[]" value="' . esc_attr($order->id) . '"></th>';
            echo '<td>' . esc_html($order->id) . '</td>';
            echo '<td>' . esc_html($order->subject) . '</td>';
            echo '<td>' . esc_html($order->name) . '</td>';
            echo '<td>' . esc_html($order->email) . '</td>';
            echo '<td>' . esc_html($order->phone) . '</td>';
            echo '<td>' . esc_html($order->company) . '</td>';
            echo '<td>' . esc_html($order->created_at) . '</td>';
            echo '<td>';
            echo '<select class="enquiry-status" data-enquiry-id="' . esc_attr($order->id) . '">';
            $statuses = array('Unreplied', 'Replied', 'Done');
            foreach ($statuses as $status) {
                echo '<option value="' . esc_attr($status) . '"' . selected($order->status, $status, false) . '>' . esc_html($status) . '</option>';
            }
            echo '</select>';
            echo '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=enquiry-details&enquiry_id=' . $order->id) . '" class="button">View Details</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</form>';

        // Pagination (only show if not displaying all entries)
        if ($per_page !== 'all' && $total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page,
                'add_args' => array('page' => 'ef-enquiries', 'per_page' => $per_page)
            );
            echo paginate_links($pagination_args);
            echo '</div></div>';
        }
    } else {
        echo '<p>No enquiries found.</p>';
    }

    echo '</div>';

    // JavaScript for managing entries
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
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

        $('#manage-entries').click(function() {
            enterSelectionMode();
        });

        $('#cancel-selection').click(function() {
            exitSelectionMode();
        });

        $('#bulk-update-form').submit(function(e) {
            e.preventDefault();
            var selectedEnquiries = $('input[name="enquiry[]"]:checked').map(function() {
                return this.value;
            }).get();
            var newStatus = $('#bulk-status').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_update_enquiry_status',
                    enquiries: selectedEnquiries,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        selectedEnquiries.forEach(function(id) {
                            $('select[data-enquiry-id="' + id + '"]').val(newStatus);
                        });
                        exitSelectionMode();
                    } else {
                        alert('Error updating statuses');
                    }
                }
            });
        });

        $('#delete-selected').click(function() {
            if (confirm('Are you sure you want to delete the selected entries? This action cannot be undone.')) {
                var selectedEnquiries = $('input[name="enquiry[]"]:checked').map(function() {
                    return this.value;
                }).get();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bulk_delete_enquiries',
                        enquiries: selectedEnquiries
                    },
                    success: function(response) {
                        if (response.success) {
                            selectedEnquiries.forEach(function(id) {
                                $('tr').has('input[value="' + id + '"]').remove();
                            });
                            exitSelectionMode();
                            alert('Selected entries deleted successfully');
                        } else {
                            alert('Error deleting entries');
                        }
                    }
                });
            }
        });

        // Ensure selectors are hidden on page load
        exitSelectionMode();
    });
    </script>
    <?php
}
