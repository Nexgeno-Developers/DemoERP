(function() {
    "use strict";
    
    // Wait for jQuery to be available
    function initAttendance() {
        if (typeof jQuery === 'undefined' || typeof initDataTable === 'undefined') {
            // Retry after a short delay
            setTimeout(initAttendance, 50);
            return;
        }
        
        var $ = jQuery;
        var attendance_table;
        var AttendanceServerParams = {};
        
        $(document).ready(function() {
            // Initialize server params
            AttendanceServerParams['date_filter'] = '[name="date_filter"]';
            AttendanceServerParams['status_filter'] = '[name="status_filter"]';
            
            // Check if staff filter exists (user has view permission)
            if ($('#staff_filter').length > 0) {
                AttendanceServerParams['staff_filter'] = '[name="staff_filter"]';
            }
            
            // Initialize DataTable
            attendance_table = initDataTable(
                '.table-attendance',
                admin_url + 'kpi/attendance_table',
                undefined,
                [],
                AttendanceServerParams
            );
        });
        
        window.applyFilters = function() {
            if (typeof attendance_table !== 'undefined' && attendance_table) {
                attendance_table.ajax.reload(null, false);
            }
        };
        
        window.clearFilters = function() {
            $('#date_filter').val('');
            $('#status_filter').val('').trigger('change');
            if ($('#staff_filter').length > 0) {
                $('#staff_filter').val('').trigger('change');
            }
            if (typeof attendance_table !== 'undefined' && attendance_table) {
                attendance_table.ajax.reload(null, false);
            }
        };
    }
    
    // Use defer mechanism if available, otherwise wait for window load
    if (typeof window.deferAfterjQueryLoaded !== 'undefined') {
        window.deferAfterjQueryLoaded.push(initAttendance);
    } else if (typeof jQuery !== 'undefined' && typeof initDataTable !== 'undefined') {
        // jQuery and initDataTable are already loaded, wait a bit for CSRF setup
        setTimeout(initAttendance, 100);
    } else {
        // Wait for window load or use setTimeout
        if (document.readyState === 'complete') {
            setTimeout(initAttendance, 100);
        } else {
            window.addEventListener('load', function() {
                setTimeout(initAttendance, 100);
            });
        }
    }
})();

