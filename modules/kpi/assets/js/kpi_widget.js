// KPI Widget JS - guarded wrapper
(function() {
    'use strict';
    if (typeof $ === 'undefined' || typeof admin_url === 'undefined') {
        return;
    }

    $(document).on('click', '.hide-kpi-widget', function () {
        var widgetId = 'kpi_widget';
        $('#widget-' + widgetId).addClass('hide');
        $('#widget_option_' + widgetId).prop('checked', false);
        $.post(admin_url + 'staff/save_dashboard_widgets_visibility', {
            widgets: [{ id: widgetId, visible: 0 }]
        });
        if (typeof alert_float === 'function') {
            alert_float('success', 'KPI widget hidden!');
        }
    });

    // Ensure modal is in body (move it if needed)
    function ensureModalInBody() {
        var modal = $('#kpi_staff_list_modal');
        if (modal.length > 0) {
            var parent = modal.parent();
            if (!parent.is('body')) {
                modal.detach().appendTo('body');
            }
        }
    }

    // Global function for onclick handlers
    window.showKpiStaffListModal = function(type) {
        if (typeof $ === 'undefined' || typeof admin_url === 'undefined') {
            alert('Error: Required JavaScript libraries not loaded. Please refresh the page.');
            return;
        }

        // Ensure modal exists in body
        ensureModalInBody();
        
        var modal = $('#kpi_staff_list_modal');
        if (modal.length === 0) {
            alert('Error: Modal not found. Please refresh the page.');
            return;
        }
        
        // Reset modal content
        $('#kpi_staff_list_loading').show();
        $('#kpi_staff_list_content').hide();
        $('#kpi_staff_list_empty').hide();
        $('#kpi_staff_list_tbody').empty();
        
        // Set title
        var title = type === 'absent' ? 'Absent Staff' : 'Staff On Leave';
        $('#kpi_modal_title').text(title);
        
        // Show/hide leave dates column for on_leave
        if (type === 'on_leave') {
            $('#kpi_leave_dates_header').show();
        } else {
            $('#kpi_leave_dates_header').hide();
        }
        
        // Show modal using Bootstrap
        modal.modal('show');
        
        // Fetch staff list via AJAX
        $.ajax({
            url: admin_url + 'kpi/get_staff_list',
            type: 'GET',
            data: { type: type },
            dataType: 'json',
            success: function(response) {
                $('#kpi_staff_list_loading').hide();
                
                if (response.success && response.staff && response.staff.length > 0) {
                    var tbody = $('#kpi_staff_list_tbody');
                    tbody.empty();
                    
                    $.each(response.staff, function(index, staff) {
                        var row = '<tr>';
                        row += '<td>' + (index + 1) + '</td>';
                        row += '<td><strong>' + (staff.full_name || staff.firstname + ' ' + staff.lastname) + '</strong></td>';
                        row += '<td>' + (staff.email || '-') + '</td>';
                        
                        if (type === 'on_leave' && staff.start_time && staff.end_time) {
                            var startDate = new Date(staff.start_time);
                            var endDate = new Date(staff.end_time);
                            var startFormatted = startDate.toLocaleDateString();
                            var endFormatted = endDate.toLocaleDateString();
                            row += '<td>' + startFormatted + ' to ' + endFormatted + '</td>';
                        } else if (type === 'on_leave') {
                            row += '<td>-</td>';
                        }
                        
                        row += '</tr>';
                        tbody.append(row);
                    });
                    
                    $('#kpi_staff_list_content').show();
                } else {
                    $('#kpi_staff_list_empty').show();
                }
            },
            error: function(xhr, status, error) {
                $('#kpi_staff_list_loading').hide();
                $('#kpi_staff_list_empty').html('<p class="text-danger">Error loading staff list. Please try again.</p>').show();
            }
        });
    };

    // Also handle via event delegation as backup
    $(document).on('click', '.kpi-clickable-stat', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var type = $(this).data('type');
        if (type) {
            window.showKpiStaffListModal(type);
        }
        return false;
    });

    // Ensure modal is in body when DOM is ready
    $(document).ready(function() {
        ensureModalInBody();
    });

    // Also ensure after widgets load
    setTimeout(function() {
        ensureModalInBody();
    }, 1000);

})();
