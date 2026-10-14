(function() {
    "use strict";
    
    var attendance_table;
    var AttendanceServerParams = {};
    
    $(function() {
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
})();

