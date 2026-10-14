/**
 * Attendance Reports JavaScript
 * Enhanced with comprehensive role-based access control
 */

// Attendance Reports JavaScript
window.addEventListener("load", function () {
  "use strict";

  if (typeof $ === "undefined" || typeof jQuery === "undefined") {
    console.error("jQuery is not loaded!");
    return;
  }

  $(function () {
    console.log("Attendance Reports initializing...");

    var table;

    try {
      // Initialize period filter with dynamic date ranges FIRST
      initializePeriodFilter();

      // Initialize with default period (This Month)
      updateDateRangeFromPeriod("this_month");

      // Set default staff status to "active"
      $("#active_status").val("active").selectpicker("refresh");
      
      // Initialize role-based UI and staff dropdown
      initializeRoleBasedUI();
      
      // Refresh selectpickers (except staff_member which will be refreshed by updateStaffDropdown)
      $("#active_status, #period").selectpicker("refresh");

      setTimeout(function () {
        // Use standard Perfex CRM table selector (matches table class pattern)
        var tableSelector = ".table-attendance-reports";
        
        // Ensure table exists
        if ($(tableSelector).length === 0) {
          console.error("Table element " + tableSelector + " not found!");
          return;
        }

        // Destroy any existing DataTable
        if ($.fn.DataTable.isDataTable(tableSelector)) {
          $(tableSelector).DataTable().destroy();
        }

        // Initialize table
        table = initDataTable(
          tableSelector,
          admin_url + "nexreports/attendance_table",
          undefined,
          undefined,
          undefined,
          undefined,
          "attendance_reports"
        );

        // Update table headers with sums after data is loaded
        if (table) {
          table.on('draw', function() {
            updateTableHeadersWithSums(table);
          });
          
          // Also update on initial load
          table.on('init.dt', function() {
            setTimeout(function() {
              updateTableHeadersWithSums(table);
            }, 100);
          });
        }

        // Hide default DataTables search
        $(".dataTables_filter").hide();

        console.log("✅ Attendance table initialized");

        /**
         * Before DataTables sends the AJAX request, inject filter values
         */
        $("body").on("preXhr.dt", function (e, settings, data) {
          var form = $("#attendance-filters-form");

          // Period filter
          var period = form.find('[name="period"]').val();
          if (period) data.period = period;

          // From date
          var from_date = form.find('[name="from_date"]').val();
          if (from_date) data.from_date = from_date;

          // To date
          var to_date = form.find('[name="to_date"]').val();
          if (to_date) data.to_date = to_date;

          // Staff filter (multi-select) - allow for all users with accessible staff
          var staff = form.find('[name="staff_member[]"]').val();
          if (staff && staff.length > 0) {
            data.staff_member = staff;
          }

          // Staff Status Filter - allow for all users
          var active_status = form.find('[name="active_status"]').val();
          if (active_status !== null && active_status !== undefined) {
            data.active_status = active_status;
          }

          console.log("📤 Filters being sent:", data);
        });
      }, 400);
    } catch (err) {
      console.error("Error initializing Attendance Reports:", err);
    }

    /**
     * Initialize role-based UI and staff dropdown
     */
    function initializeRoleBasedUI() {
      console.log("🔍 Initializing role-based UI...");
      console.log("🔍 Role type:", typeof roleType !== 'undefined' ? roleType : 'unknown');
      console.log("🔍 Can manage filters:", typeof canManageFilters !== 'undefined' ? canManageFilters : false);
      
      // Apply role-based restrictions
      if (typeof canManageFilters !== 'undefined' && !canManageFilters) {
        disableRestrictedFilters();
      }
      
      // Initialize staff dropdown with proper data
      if (typeof staffData !== 'undefined' && staffData) {
        console.log("✅ Using new staffData structure");
        updateStaffDropdownWithRoleData("active");
      } else if (typeof allStaffMembers !== 'undefined' && allStaffMembers && allStaffMembers.length > 0) {
        console.log("✅ Using legacy allStaffMembers data");
        updateStaffDropdown("active");
      } else {
        console.log("⚠️ No staff data available, using fallback");
        handleMissingStaffData();
      }
    }

    /**
     * Disable restricted filters for users with "View Own" permission
     */
    function disableRestrictedFilters() {
      // Disable Staff Status dropdown
      $("#active_status").prop("disabled", true).selectpicker("refresh");
      
      // Disable Staff dropdown
      $("#staff_member").prop("disabled", true).selectpicker("refresh");
      
      console.log("🔒 Filters restricted for View Own user");
    }

    /**
     * Update staff dropdown using new role-based data structure
     */
    function updateStaffDropdownWithRoleData(activeStatus) {
      console.log("🔍 updateStaffDropdownWithRoleData called with status:", activeStatus);
      
      if (typeof staffData === 'undefined' || !staffData) {
        console.error("❌ staffData is not available");
        return updateStaffDropdown(activeStatus); // Fallback to legacy method
      }
      
      var $staffSelect = $("#staff_member");
      var currentSelection = $staffSelect.val() || [];
      
      // Destroy selectpicker first to avoid conflicts
      if ($staffSelect.data('selectpicker')) {
        $staffSelect.selectpicker('destroy');
      }
      
      // Clear existing options
      $staffSelect.empty();
      
      // Get staff based on selected status
      var filteredStaff = [];
      if (activeStatus === 'all' && staffData.all) {
        filteredStaff = staffData.all;
      } else if (activeStatus === 'active' && staffData.active) {
        filteredStaff = staffData.active;
      } else if (activeStatus === 'inactive' && staffData.inactive) {
        filteredStaff = staffData.inactive;
      }
      
      console.log("✅ Filtered staff for status '" + activeStatus + "':", filteredStaff.length, "members");
      
      // Add filtered staff to dropdown
      filteredStaff.forEach(function(staff) {
        var selected = currentSelection.indexOf(String(staff.staffid)) !== -1 ? 'selected' : '';
        var inactiveLabel = staff.active == 0 ? ' (Inactive)' : '';
        $staffSelect.append(
          '<option value="' + staff.staffid + '" data-active="' + staff.active + '" ' + selected + '>' +
          escapeHtml(staff.firstname + ' ' + staff.lastname + inactiveLabel) +
          '</option>'
        );
      });
      
      // Validate current selection
      var visibleIds = filteredStaff.map(function(s) { return String(s.staffid); });
      var validSelection = currentSelection.filter(function(id) {
        return visibleIds.indexOf(id) !== -1;
      });
      
      // Set valid selection
      if (validSelection.length > 0) {
        $staffSelect.val(validSelection);
      } else {
        $staffSelect.val([]);
      }
      
      // Reinitialize selectpicker
      var selectpickerOptions = {
        width: '100%',
        liveSearch: true,
        actionsBox: true,
        noneSelectedText: 'Nothing selected'
      };
      
      // Disable if user doesn't have permission
      if (typeof canManageFilters !== 'undefined' && !canManageFilters) {
        $staffSelect.prop('disabled', true);
      }
      
      $staffSelect.selectpicker(selectpickerOptions);
      $staffSelect.selectpicker('refresh');
      
      console.log("✅ Staff dropdown updated using role data for status: " + activeStatus + " (" + filteredStaff.length + " staff members)");
    }

    /**
     * Period change handler (supports custom month/year)
     */
    $("#period").on("change", function () {
      const period = $(this).val();
      
      // Custom Period selected
      if (period === "custom_period") {
        $("#custom-period-container").slideDown(200);
        $("#custom_month, #custom_year").selectpicker("refresh");
        return;
      }

      // Normal period selected
      $("#custom-period-container").slideUp(200);
      updateDateRangeFromPeriod(period);
    });

    $("#custom_month, #custom_year").on("change", function () {
      if ($("#period").val() === "custom_period") {
        updateCustomMonthYearRange();
      }
    });

    function updateCustomMonthYearRange() {
      const month = parseInt($("#custom_month").val());
      const year  = parseInt($("#custom_year").val());

      if (!month || !year) return;

      const from = new Date(year, month - 1, 1);
      const to   = new Date(year, month, 0);

      $("#from_date").val(formatDateForBackend(from));
      $("#to_date").val(formatDateForBackend(to));

      console.log("📅 Custom Period:", $("#from_date").val(), $("#to_date").val());
    }

    /**
     * Staff Status change handler - dynamically update staff dropdown
     * Only works if user has permission
     */
    $("#active_status").on("change", function () {
      if (typeof canManageFilters !== 'undefined' && !canManageFilters) {
        // Prevent change for restricted users
        return false;
      }
      
      var selectedStatus = $(this).val();
      
      // Use new role-based method if available, otherwise fallback to legacy
      if (typeof staffData !== 'undefined' && staffData) {
        updateStaffDropdownWithRoleData(selectedStatus);
      } else {
        updateStaffDropdown(selectedStatus);
      }
    });

    /**
     * Legacy staff dropdown update method (for backward compatibility)
     */
    function updateStaffDropdown(activeStatus) {
      console.log("🔍 updateStaffDropdown (legacy) called with status:", activeStatus);
      
      if (typeof allStaffMembers === 'undefined') {
        console.error("❌ allStaffMembers is undefined - check if data is passed from PHP");
        handleMissingStaffData();
        return;
      }
      
      if (!allStaffMembers || !Array.isArray(allStaffMembers) || allStaffMembers.length === 0) {
        console.error("❌ allStaffMembers is invalid:", allStaffMembers);
        handleMissingStaffData();
        return;
      }
      
      console.log("✅ allStaffMembers loaded successfully with", allStaffMembers.length, "staff members");

      var $staffSelect = $("#staff_member");
      var currentSelection = $staffSelect.val() || [];
      
      // Destroy selectpicker first to avoid conflicts
      if ($staffSelect.data('selectpicker')) {
        $staffSelect.selectpicker('destroy');
      }
      
      // Clear existing options
      $staffSelect.empty();
      
      // Filter staff based on active status
      var filteredStaff = [];
      allStaffMembers.forEach(function(staff) {
        var shouldInclude = false;
        
        if (activeStatus === 'all') {
          shouldInclude = true;
        } else if (activeStatus === 'active') {
          shouldInclude = staff.active == 1;
        } else if (activeStatus === 'inactive') {
          shouldInclude = staff.active == 0;
        }
        
        if (shouldInclude) {
          filteredStaff.push(staff);
        }
      });
      
      // Add filtered staff to dropdown
      filteredStaff.forEach(function(staff) {
        var selected = currentSelection.indexOf(String(staff.staffid)) !== -1 ? 'selected' : '';
        var inactiveLabel = staff.active == 0 ? ' (Inactive)' : '';
        $staffSelect.append(
          '<option value="' + staff.staffid + '" data-active="' + staff.active + '" ' + selected + '>' +
          escapeHtml(staff.firstname + ' ' + staff.lastname + inactiveLabel) +
          '</option>'
        );
      });
      
      // Validate current selection
      var visibleIds = filteredStaff.map(function(s) { return String(s.staffid); });
      var validSelection = currentSelection.filter(function(id) {
        return visibleIds.indexOf(id) !== -1;
      });
      
      // Set valid selection
      if (validSelection.length > 0) {
        $staffSelect.val(validSelection);
      } else {
        $staffSelect.val([]);
      }
      
      // Reinitialize selectpicker
      var selectpickerOptions = {
        width: '100%',
        liveSearch: true,
        actionsBox: true,
        noneSelectedText: 'Nothing selected'
      };
      
      // Disable if user doesn't have permission
      if (typeof canManageFilters !== 'undefined' && !canManageFilters) {
        $staffSelect.prop('disabled', true);
      }
      
      $staffSelect.selectpicker(selectpickerOptions);
      $staffSelect.selectpicker('refresh');
      
      console.log("✅ Staff dropdown updated (legacy) for status: " + activeStatus + " (" + filteredStaff.length + " staff members)");
    }

    /**
     * Fallback: Handle case where staff data is completely missing
     */
    function handleMissingStaffData() {
      console.warn("⚠️ Staff data is missing, using fallback approach");
      
      var $staffSelect = $("#staff_member");
      
      // Check if dropdown already has options (maybe populated by PHP)
      var existingOptions = $staffSelect.find('option').length;
      if (existingOptions > 0) {
        console.log("✅ Dropdown already has " + existingOptions + " options from PHP");
        // Just refresh the selectpicker
        if ($staffSelect.data('selectpicker')) {
          $staffSelect.selectpicker('refresh');
        } else {
          $staffSelect.selectpicker({
            width: '100%',
            liveSearch: true,
            actionsBox: true,
            noneSelectedText: 'Nothing selected'
          });
        }
        return;
      }
      
      // If no options, show a message
      $staffSelect.empty();
      $staffSelect.append('<option value="">No staff data available</option>');
      $staffSelect.selectpicker('refresh');
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
      var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Apply Filters Button
     */
    $("#apply-filters-btn").on("click", function (e) {
      e.preventDefault();
      
      // Log current filter state for debugging
      var form = $("#attendance-filters-form");
      var currentFilters = {
        period: form.find('[name="period"]').val(),
        from_date: form.find('[name="from_date"]').val(),
        to_date: form.find('[name="to_date"]').val(),
        active_status: form.find('[name="active_status"]').val(),
        staff_member: form.find('[name="staff_member[]"]').val()
      };
      console.log("🔄 Applying filters:", currentFilters);
      
      if (table && typeof table.ajax !== "undefined") {
        table.ajax.reload(null, false); // false = don't reset pagination to page 1
        console.log("✅ Apply filters clicked - table reloaded");
      }
    });

    /**
     * Reset Filters Button
     */
    $("#reset-filters-btn").on("click", function (e) {
      e.preventDefault();

      // Reset form
      $("#attendance-filters-form")[0].reset();

      // Reset period to "This Month"
      $("#period").val("this_month").selectpicker("refresh");
      updateDateRangeFromPeriod("this_month");

      // Reset staff status to "Active Staff" for all users
      $("#active_status").val("active").selectpicker("refresh");
      
      // Update staff dropdown based on reset status for all users
      if (typeof staffData !== 'undefined' && staffData) {
        updateStaffDropdownWithRoleData("active");
      } else if (typeof allStaffMembers !== 'undefined') {
        updateStaffDropdown("active");
      }
      
      // Clear staff selection for all users
      $("#staff_member").val([]).selectpicker("refresh");
      
      // Hide custom period container
      $("#custom-period-container").hide();
      
      // Reload table
      if (table && typeof table.ajax !== "undefined") {
        table.ajax.reload();
        console.log("✅ Filters reset - table reloaded");
      }
    });

    /**
     * Auto-refresh when returning to the page
     */
    $(window).on("focus pageshow", function (event) {
      if (table && typeof table.ajax !== "undefined") {
        table.ajax.reload(null, false);
        console.log("🔄 Table auto-refreshed on page return");
      }
    });

    /**
     * Handle click on Leave Approved badge
     */
    $(document).on("click", ".leave-approved-badge", function (e) {
      e.preventDefault();
      var staffId = $(this).data("staff-id");
      var form = $("#attendance-filters-form");
      var fromDate = form.find('[name="from_date"]').val();
      var toDate = form.find('[name="to_date"]').val();

      // Get current period dates if not set
      if (!fromDate || !toDate) {
        var period = form.find('[name="period"]').val();
        var dates = calculateDateRange(period);
        fromDate = dates.from ? formatDateForBackend(dates.from) : "";
        toDate = dates.to ? formatDateForBackend(dates.to) : "";
      }

      // Load modal content via AJAX
      $.post(
        admin_url + "nexreports/get_leave_details",
        {
          staff_id: staffId,
          from_date: fromDate,
          to_date: toDate,
        },
        function (response) {
          if (response && response.trim() !== '') {
            $("#leave-details-modal-container").html(response);
            $("#leaveDetailsModal").modal("show");
            
            // Initialize tooltips for approver images
            $("#leaveDetailsModal").on('shown.bs.modal', function () {
              if (typeof $ !== 'undefined' && $.fn.tooltip) {
                $("#leaveDetailsModal [data-toggle='tooltip']").tooltip();
              }
            });
            
            // Also initialize immediately if modal is already shown
            setTimeout(function() {
              if (typeof $ !== 'undefined' && $.fn.tooltip) {
                $("#leaveDetailsModal [data-toggle='tooltip']").tooltip();
              }
            }, 300);
          } else {
            alert_float("warning", "No data returned from server");
          }
        }
      ).fail(function (xhr, status, error) {
        console.error("Error loading leave details:", error, xhr.responseText);
        alert_float("danger", "Error loading leave details: " + error);
      });
    });

    /**
     * Handle click on OT Approved badge
     */
    $(document).on("click", ".ot-approved-badge", function (e) {
      e.preventDefault();
      var staffId = $(this).data("staff-id");
      var form = $("#attendance-filters-form");
      var fromDate = form.find('[name="from_date"]').val();
      var toDate = form.find('[name="to_date"]').val();

      // Get current period dates if not set
      if (!fromDate || !toDate) {
        var period = form.find('[name="period"]').val();
        var dates = calculateDateRange(period);
        fromDate = dates.from ? formatDateForBackend(dates.from) : "";
        toDate = dates.to ? formatDateForBackend(dates.to) : "";
      }

      // Load modal content via AJAX
      $.post(
        admin_url + "nexreports/get_ot_details",
        {
          staff_id: staffId,
          from_date: fromDate,
          to_date: toDate,
        },
        function (response) {
          if (response && response.trim() !== '') {
            $("#ot-details-modal-container").html(response);
            $("#otDetailsModal").modal("show");
          } else {
            alert_float("warning", "No data returned from server");
          }
        }
      ).fail(function (xhr, status, error) {
        console.error("Error loading OT details:", error, xhr.responseText);
        alert_float("danger", "Error loading OT details: " + error);
      });
    });

    /**
     * Handle click on Tickets badge
     */
    $(document).on("click", ".tickets-badge", function (e) {
      e.preventDefault();
      var staffId = $(this).data("staff-id");
      var form = $("#attendance-filters-form");
      var fromDate = form.find('[name="from_date"]').val();
      var toDate = form.find('[name="to_date"]').val();

      // Get current period dates if not set
      if (!fromDate || !toDate) {
        var period = form.find('[name="period"]').val();
        var dates = calculateDateRange(period);
        fromDate = dates.from ? formatDateForBackend(dates.from) : "";
        toDate = dates.to ? formatDateForBackend(dates.to) : "";
      }

      // Load modal content via AJAX
      $.post(
        admin_url + "nexreports/get_tickets_details",
        {
          staff_id: staffId,
          from_date: fromDate,
          to_date: toDate,
        },
        function (response) {
          if (response && response.trim() !== '') {
            $("#tickets-details-modal-container").html(response);
            $("#ticketsDetailsModal").modal("show");
          } else {
            alert_float("warning", "No data returned from server");
          }
        }
      ).fail(function (xhr, status, error) {
        console.error("Error loading tickets details:", error, xhr.responseText);
        alert_float("danger", "Error loading tickets details: " + error);
      });
    });
  });
});

/**
 * Initialize period filter with dynamic date subtexts
 */
function initializePeriodFilter() {
  const periods = [
    { value: "last_3_months", label: "Last 3 months" },
    { value: "last_6_months", label: "Last 6 months" },
    { value: "last_12_months", label: "Last 12 months" },
  ];

  periods.forEach((period) => {
    const dates = calculateDateRange(period.value);
    const dateText =
      formatDateForDisplay(dates.from) + " - " + formatDateForDisplay(dates.to);

    // Update option with data-subtext
    $(`#period option[value="${period.value}"]`).attr("data-subtext", dateText);
  });

  // Refresh selectpicker to show updated subtexts
  $("#period").selectpicker("refresh");
}

/**
 * Calculate date range based on period
 */
function calculateDateRange(period) {
  const today = new Date();
  const year = today.getFullYear();
  const month = today.getMonth();
  const day = today.getDate();

  let fromDate, toDate;

  switch (period) {
    case "this_month":
      fromDate = new Date(year, month, 1);
      toDate = new Date(year, month + 1, 0); // Last day of current month
      break;

    case "last_month":
      fromDate = new Date(year, month - 1, 1);
      toDate = new Date(year, month, 0); // Last day of previous month
      break;

    default:
      fromDate = null;
      toDate = null;
  }

  return {
    from: fromDate,
    to: toDate,
  };
}

/**
 * Update hidden date fields based on selected period
 */
function updateDateRangeFromPeriod(period) {
  const dates = calculateDateRange(period);

  if (dates.from && dates.to) {
    $("#from_date").val(formatDateForBackend(dates.from));
    $("#to_date").val(formatDateForBackend(dates.to));
  } else {
    $("#from_date").val("");
    $("#to_date").val("");
  }
}

/**
 * Format date for display (YYYY-MM-DD format)
 */
function formatDateForDisplay(date) {
  if (!date) return "";

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

/**
 * Format date for backend (YYYY-MM-DD format)
 */
function formatDateForBackend(date) {
  if (!date) return "";

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

/**
 * Update table headers with salary and reflectable salary sums
 */
/**
 * Update table headers with salary and reflectable salary sums
 * FIXED VERSION - Correct column indices
 */
function updateTableHeadersWithSums(table) {
  if (!table) return;
  
  // Get totals from the last AJAX response
  var json = table.ajax.json();
  if (!json || !json.totals) return;
  
  var baseSalaryTotal = json.totals.base_salary || 0;
  var reflectableSalaryTotal = json.totals.reflectable_salary || 0;
  
  // Format the totals (remove currency symbols, just show number)
  var baseSalaryFormatted = '₹' + Math.round(baseSalaryTotal).toLocaleString();
  var reflectableSalaryFormatted = '₹' + Math.round(reflectableSalaryTotal).toLocaleString();
  
  // Find the table header row
  var $table = $(table.table().node()).closest('.dataTables_wrapper').find('table');
  var $headerRow = $table.find('thead tr');
  
  if ($headerRow.length > 0) {
    // ✅ Update Salary column header (index 8 - this is the 9th column, 0-indexed)
    var $salaryHeader = $headerRow.find('th').eq(8);
    if ($salaryHeader.length > 0) {
      var originalText = $salaryHeader.data('original-text') || $salaryHeader.text().split('(')[0].trim();
      if (!$salaryHeader.data('original-text')) {
        $salaryHeader.data('original-text', originalText);
      }
      $salaryHeader.html(originalText + ' (' + baseSalaryFormatted + ')');
    }
    
    // ✅ Update Reflectable Salary column header (index 9 - this is the 10th column, 0-indexed)
    var $reflectableHeader = $headerRow.find('th').eq(9);
    if ($reflectableHeader.length > 0) {
      var originalTextRef = $reflectableHeader.data('original-text') || $reflectableHeader.text().split('(')[0].trim();
      if (!$reflectableHeader.data('original-text')) {
        $reflectableHeader.data('original-text', originalTextRef);
      }
      $reflectableHeader.html(originalTextRef + ' (' + reflectableSalaryFormatted + ')');
    }
  }
}

console.log("Attendance Reports JavaScript loaded successfully");