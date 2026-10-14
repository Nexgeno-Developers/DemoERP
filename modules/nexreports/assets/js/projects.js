// Wait for jQuery to be fully loaded
window.addEventListener("load", function () {
  "use strict";

  if (typeof $ === "undefined" || typeof jQuery === "undefined") {
    console.error("jQuery is not loaded!");
    return;
  }

  // Helper function to format date as YYYY-MM-DD
  function formatDate(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return year + "-" + month + "-" + day;
  }

  // Convert period selection to actual date range
  function periodToDateRange(period) {
    var today = new Date();
    var from, to;

    switch (period) {
      case "this_month":
        from = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
        to = formatDate(new Date(today.getFullYear(), today.getMonth() + 1, 0));
        break;

      case "last_month":
        from = formatDate(
          new Date(today.getFullYear(), today.getMonth() - 1, 1)
        );
        to = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
        break;

      default:
        return null;
    }

    return { from: from, to: to };
  }

  // Function to get date range text for dropdown display
  function getDateRangeText(period) {
    var dateRange = periodToDateRange(period);
    if (!dateRange) return "";

    if (dateRange.from === dateRange.to) {
      return dateRange.from;
    }
    return dateRange.from + " - " + dateRange.to;
  }

  $(function () {
    // Define table variable in the outer scope so all handlers can access it
    var table;

    // 🆕 POPULATE PERIOD DROPDOWN WITH DATE RANGES (only for last X months)
    setTimeout(function () {
      // Only show dates for "Last X months" options
      var showDatesFor = ["3", "6", "12"];

      $("#period option[value]").each(function () {
        var val = $(this).val();

        // Only add date ranges for "Last X months"
        if (val && showDatesFor.includes(val)) {
          var dateRange = getDateRangeText(val);
          if (dateRange) {
            var text = $(this).text().trim();
            $(this).attr(
              "data-content",
              "<strong>" +
                text +
                '</strong><br><small class="text-muted">' +
                dateRange +
                "</small>"
            );
          }
        }
      });
      $("#period").selectpicker("refresh");
    }, 100);

    try {
      // Refresh selectpickers
      $(".selectpicker").selectpicker("refresh");

      setTimeout(function () {
        // Wait for table to be rendered with multiple attempts
        var attempts = 0;
        var maxAttempts = 20; // Wait up to 10 seconds (20 * 500ms)
        
        function waitForTable() {
          attempts++;
          
          if ($(".table-nexreports").length === 0) {
            if (attempts < maxAttempts) {
              setTimeout(waitForTable, 500); // Wait 500ms and try again
              return;
            } else {
              console.error("Table element .table-nexreports not found after " + maxAttempts + " attempts!");
              
              // Show error message to user
              var errorHtml = '<div class="alert alert-danger">' +
                '<strong>Error:</strong> Unable to load projects table. ' +
                'Please check browser console for details and contact administrator.' +
                '</div>';
              $('.panel-table-full').html(errorHtml);
              return;
            }
          }
          
          console.log("✅ Table found after " + attempts + " attempts");
          initializeTable();
        }
        
        function initializeTable() {
          // Destroy any existing DataTable (safety)
          if ($.fn.DataTable.isDataTable(".table-nexreports")) {
            $(".table-nexreports").DataTable().destroy();
          }

          // Initialize table via Perfex helper
          table = initDataTable(
            ".table-nexreports",
            admin_url + "nexreports/table",
            undefined,
            undefined,
            undefined,
            undefined,
            "nexreports_projects"
          );

          // Hide default DataTables search
          $(".dataTables_filter").hide();
          
          console.log("✅ DataTable initialized successfully");
        }
        
        // Start waiting for table
        waitForTable();

        /**
         * ===============================
         * DYNAMIC FILTERS SECTION START
         * ===============================
         */
        function loadDynamicFilters() {
          $.ajax({
            url: admin_url + "nexreports/get_dynamic_filters",
            type: "GET",
            dataType: "json",
            success: function (filters) {
              var filterContainer = $("#filter-container");
              if (filterContainer.length === 0) {
                console.warn(
                  '⚠️ Missing <div id="filter-container" class="row mb-3"></div> above table!'
                );
                return;
              }

              filterContainer.empty();

              // Build each filter dynamically
              filters.forEach(function (filter) {
                console.log("Building filter:", filter);
                let html = "";
                let filterType = filter.type.toLowerCase();

                // If type is "input" but has options, treat it as select
                if (
                  filterType === "input" &&
                  filter.options &&
                  filter.options.trim() !== ""
                ) {
                  filterType = "select";
                }

                switch (filterType) {
                  case "select":
                  case "multiselect":
                    html = `
                                    <select id="filter_${filter.id}" 
                                            class="form-control filter-control selectpicker"
                                            data-width="100%"
                                            data-live-search="true"
                                            data-none-selected-text="All"
                                            data-size="8"
                                            ${
                                              filterType === "multiselect"
                                                ? 'multiple data-actions-box="true"'
                                                : ""
                                            }>
                                        <option value="">All</option>
                                        ${(filter.options || "")
                                          .split(",")
                                          .map(
                                            (o) =>
                                              `<option value="${o.trim()}">${o.trim()}</option>`
                                          )
                                          .join("")}
                                    </select>`;
                    break;

                  case "date_picker":
                  case "datetime_picker":
                    html = `<input type="date" id="filter_${filter.id}" class="form-control filter-control">`;
                    break;

                  default:
                    html = `<input type="text" id="filter_${filter.id}" class="form-control filter-control" placeholder="${filter.name}">`;
                }

                filterContainer.append(`
                            <div class="col-md-3 mb-3">
                                <div class="form-group">
                                    <label>${filter.name}</label>
                                    ${html}
                                </div>
                            </div>
                        `);
              });

              // IMPORTANT: Destroy and reinitialize selectpicker after DOM update
              setTimeout(function () {
                // Destroy any existing selectpicker instances
                $("#filter-container .selectpicker").each(function () {
                  if ($(this).data("selectpicker")) {
                    $(this).selectpicker("destroy");
                  }
                });

                // Reinitialize with live search enabled
                $("#filter-container .selectpicker").selectpicker({
                  liveSearch: true,
                  width: "100%",
                  size: 8,
                  style: "btn-default",
                  noneSelectedText: "All",
                  liveSearchPlaceholder: "Search...",
                  liveSearchStyle: "contains",
                  liveSearchNormalize: true,
                });

                console.log(
                  "✅ Selectpicker reinitialized for custom fields with live search"
                );
              }, 200);
            },
            error: function (xhr, status, error) {
              console.error("Failed to load dynamic filters:", error);
            },
          });
        }

        // Load them initially
        loadDynamicFilters();

        /**
         * ===============================
         * DYNAMIC FILTERS SECTION END
         * ===============================
         */

        /**
         * Before DataTables sends the AJAX request, inject all filter values
         */
        $("body").on("preXhr.dt", function (e, settings, data) {
          var form = $("#nexreports-filters-form");

          function assignIfNotEmpty(field, key) {
            var val = form.find(`[name="${field}"]`).val();
            if (val && val !== "") data[key || field] = val;
          }

          assignIfNotEmpty("project_name");
          assignIfNotEmpty("customer");
          assignIfNotEmpty("date_type");

          // 🔥 CONVERT PERIOD TO DATES
          var period = form.find('[name="period"]').val();
          if (period && period !== "") {
            var dateRange = periodToDateRange(period);
            if (dateRange) {
              data.from_date = dateRange.from;
              data.to_date = dateRange.to;
              console.log(
                "✅ Period converted:",
                period,
                "→ From:",
                dateRange.from,
                "To:",
                dateRange.to
              );
            }
          } else {
            // If no period selected, use manual date inputs
            assignIfNotEmpty("from_date");
            assignIfNotEmpty("to_date");
          }

          // Multi-selects
          ["members", "status", "tags"].forEach((name) => {
            var val = form.find(`[name="${name}[]"]`).val();
            if (val && val.length > 0) data[name] = val;
          });

          // Dynamic custom field filters
          if (!data.custom_fields) {
            data.custom_fields = {};
          }

          $(".filter-control").each(function () {
            var id = $(this).attr("id");
            var val = $(this).val();

            if (id && id.startsWith("filter_") && val && val !== "") {
              var fieldId = id.replace("filter_", "");
              data.custom_fields[fieldId] = val;
            }
          });

          console.log("📤 All filters being sent:", data);
        });
      }, 400);
    } catch (err) {
      console.error("Error initializing NexReports:", err);
    }

    /**
     * Button Controls (Apply, Reset, Export, Refresh)
     */
    $("#apply-filters-btn").on("click", function (e) {
      e.preventDefault();
      if (table && typeof table.ajax !== "undefined") {
        table.ajax.reload();
        console.log("✅ Apply filters clicked - table reloaded");
      }
    });

    $("#reset-filters-btn").on("click", function (e) {
      e.preventDefault();
      $("#nexreports-filters-form")[0].reset();
      $(".selectpicker").val("").selectpicker("refresh");
      $("#from_date, #to_date").val("");
      $(".filter-control").val("");
      if (table && typeof table.ajax !== "undefined") {
        table.ajax.reload();
        console.log("✅ Filters reset - table reloaded");
      }
    });

    $("#export-csv-btn").on("click", function (e) {
      e.preventDefault();
      var query = $("#nexreports-filters-form").serialize();
      window.location.href = admin_url + "nexreports/export?" + query;
    });

    // Refresh/Reload button - clears filters and reloads
    $(document).on(
      "click",
      ".btn-dt-reload, .buttons-reload, .dt-button-reload",
      function (e) {
        e.preventDefault();
        if (table && typeof table.ajax !== "undefined") {
          // Clear all filters first
          $("#nexreports-filters-form")[0].reset();
          $(".selectpicker").val("").selectpicker("refresh");
          $("#from_date, #to_date").val("");
          $(".filter-control").val("");

          // Then reload the table
          table.ajax.reload(null, false);
          console.log(
            "🔄 Reload button clicked - filters cleared and table refreshed"
          );

          alert_float("success", "Filters cleared and table refreshed!");
        } else {
          console.error("Table not initialized yet");
          alert_float("danger", "Table not ready yet");
        }
      }
    );

    // Auto-refresh when returning to the page
    $(window).on("focus pageshow", function (event) {
      if (table && typeof table.ajax !== "undefined") {
        table.ajax.reload(null, false);
        console.log("🔄 Table auto-refreshed on page return");
      }
    });
  }); // End $(function)
}); // End window.addEventListener('load')

// ==================================================
// FILTER TEMPLATE FUNCTIONALITY - ENHANCED DROPDOWN
// ==================================================

$(document).ready(function () {
  console.log("Filter Template functionality initializing...");

  // Initialize tooltips
  $('[data-toggle="tooltip"]').tooltip();

  // Disable text input by default
  $("#filter-template-name").prop("disabled", true);

  // Enable/disable text input based on checkbox
  $("#save-filter-template").on("change", function () {
    if ($(this).is(":checked")) {
      $("#filter-template-name").prop("disabled", false).focus();
    } else {
      $("#filter-template-name").prop("disabled", true);
    }
  });

  // When clicking the text input, automatically check the checkbox
  $("#filter-template-name").on("click", function () {
    $("#save-filter-template").prop("checked", true).trigger("change");
  });

  // Create dropdown container dynamically if it doesn't exist
  if ($("#filter-templates-dropdown").length === 0) {
    $("body").append(
      '<div id="filter-templates-dropdown" class="filter-templates-dropdown"></div>'
    );
  }

  // ==================================================
  // HELPER FUNCTIONS
  // ==================================================

  // Escape HTML
  function escapeHtml(text) {
    var map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  // Apply filters to form
  function applyFiltersToForm(filters) {
    console.log("Applying filters to form:", filters);

    // Single selects
    if (filters.project_name) $("#project_name").val(filters.project_name);
    if (filters.customer) $("#customer").val(filters.customer);
    if (filters.date_type) $("#date_type").val(filters.date_type);
    if (filters.period) $("#period").val(filters.period);

    // Multi-selects
    if (filters.members) $("#members").val(filters.members);
    if (filters.status) $("#status").val(filters.status);
    if (filters.tags) $("#tags").val(filters.tags);

    // Dates
    if (filters.from_date) $("#from_date").val(filters.from_date);
    if (filters.to_date) $("#to_date").val(filters.to_date);

    // Custom fields
    if (filters.custom_fields) {
      $.each(filters.custom_fields, function (fieldId, value) {
        $("#filter_" + fieldId).val(value);
      });
    }

    // Refresh all selectpickers
    $(".selectpicker").selectpicker("refresh");

    console.log("Filters applied successfully");
  }

  // Collect current filters
  function collectCurrentFilters() {
    var filters = {};

    // Single select filters
    var singleSelects = ["project_name", "customer", "date_type", "period"];
    singleSelects.forEach(function (name) {
      var val = $("#" + name).val();
      if (val && val !== "") {
        filters[name] = val;
      }
    });

    // Multi-select filters
    var multiSelects = ["members", "status", "tags"];
    multiSelects.forEach(function (name) {
      var val = $("#" + name).val();
      if (val && val.length > 0) {
        filters[name] = val;
      }
    });

    // Date filters
    var from_date = $("#from_date").val();
    var to_date = $("#to_date").val();
    if (from_date) filters.from_date = from_date;
    if (to_date) filters.to_date = to_date;

    // Dynamic custom field filters
    filters.custom_fields = {};
    $(".filter-control").each(function () {
      var id = $(this).attr("id");
      if (id && id.startsWith("filter_")) {
        var val = $(this).val();
        if (val && val !== "") {
          var fieldId = id.replace("filter_", "");
          filters.custom_fields[fieldId] = val;
        }
      }
    });

    // Remove custom_fields if empty
    if (Object.keys(filters.custom_fields).length === 0) {
      delete filters.custom_fields;
    }

    console.log("Collected filters:", filters);
    return filters;
  }

  // ==================================================
  // TOGGLE DROPDOWN ON BUTTON CLICK
  // ==================================================
  $("#filter-templates-btn")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      var dropdown = $("#filter-templates-dropdown");

      if (dropdown.is(":visible")) {
        dropdown.hide();
      } else {
        // Load templates
        $.ajax({
          url: admin_url + "nexreports/get_filter_templates",
          type: "GET",
          dataType: "json",
          success: function (templates) {
            console.log("Loaded templates:", templates);
            showTemplatesDropdown(templates);
          },
          error: function (xhr, status, error) {
            console.error("Error loading templates:", error);
            alert_float("danger", "Error loading filter templates");
          },
        });
      }
    });

  // ==================================================
  // DISPLAY TEMPLATES IN DROPDOWN
  // ==================================================
  function showTemplatesDropdown(templates) {
    var dropdown = $("#filter-templates-dropdown");
    var html = "";

    if (templates.length === 0) {
      html = `
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <p>No Filter Templates Saved</p>
                </div>
            `;
    } else {
      html = '<div class="filter-list">';

      templates.forEach(function (template) {
        var isDefault = template.is_default == "1";

        html += `
                    <div class="filter-item" data-template-id="${
                      template.id
                    }" data-filters='${template.filters}'>
                        <div class="filter-name">${escapeHtml(
                          template.name
                        )}</div>
                        <div class="filter-actions">
                            ${
                              isDefault
                                ? '<span class="default-badge">Default</span>'
                                : ""
                            }
                            <button class="delete-btn" 
                                    data-template-id="${template.id}"
                                    data-toggle="tooltip"
                                    title="Delete Template">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
      });

      html += "</div>";
    }

    dropdown.html(html);

    // Position dropdown below button
    positionDropdown();

    // Show dropdown
    dropdown.fadeIn(200);

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Attach event handlers
    attachDropdownEventHandlers();
  }

  // ==================================================
  // POSITION DROPDOWN BELOW BUTTON
  // ==================================================
  function positionDropdown() {
    var btn = $("#filter-templates-btn");
    var dropdown = $("#filter-templates-dropdown");

    var offset = btn.offset();
    var btnWidth = btn.outerWidth();
    var btnHeight = btn.outerHeight();
    var dropdownWidth = dropdown.outerWidth();

    dropdown.css({
      position: "absolute",
      top: offset.top + btnHeight + 5 + "px",
      left: offset.left - dropdownWidth + btnWidth + "px",
      zIndex: 9999,
    });
  }

  // ATTACH EVENT HANDLERS
  // ==================================================
  // ==================================================
  // ATTACH EVENT HANDLERS
  // ==================================================
  function attachDropdownEventHandlers() {
    // IMPROVED: Handle delete button clicks FIRST (higher priority)
    $(".delete-btn")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation(); // Prevents any other handlers from firing

        var templateId = $(this).attr("data-template-id");
        var templateName = $(this)
          .closest(".filter-item")
          .find(".filter-name")
          .text();

        console.log("Delete clicked for template:", templateId);

        showDeleteConfirmation(templateId, templateName);

        return false; // Extra safety
      });

    // IMPROVED: Load template - check BOTH target and parent elements
    $(".filter-item")
      .off("click")
      .on("click", function (e) {
        // More robust check - also check parents
        var isDeleteButton =
          $(e.target).hasClass("delete-btn") ||
          $(e.target).closest(".delete-btn").length > 0 ||
          $(e.target).parent().hasClass("delete-btn");

        if (isDeleteButton) {
          console.log("Delete button clicked, not loading template");
          return false;
        }

        var filters = JSON.parse($(this).attr("data-filters"));
        console.log("Loading template with filters:", filters);

        applyFiltersToForm(filters);

        // Hide dropdown
        $("#filter-templates-dropdown").fadeOut(200);

        alert_float("success", "Filter template loaded!");

        // Reload table
        setTimeout(function () {
          if (typeof table !== "undefined" && table.ajax) {
            table.ajax.reload();
          }
        }, 500);
      });

    // Delete button
    $(".delete-btn")
      .off("click")
      .on("click", function (e) {
        e.stopPropagation();

        var templateId = $(this).attr("data-template-id");
        var templateName = $(this)
          .closest(".filter-item")
          .find(".filter-name")
          .text();

        // Show custom confirmation modal
        showDeleteConfirmation(templateId, templateName);
      });
  }

  // ==================================================
  // DELETE CONFIRMATION MODAL
  // ==================================================
  function showDeleteConfirmation(templateId, templateName) {
    var modalHtml = `
            <div class="modal fade" id="deleteTemplateModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                            <h4 class="modal-title">
                                <i class="fa fa-exclamation-triangle text-danger"></i> 
                                Delete Template
                            </h4>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this filter template?</p>
                            <p><strong>"${escapeHtml(
                              templateName
                            )}"</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Remove existing modal
    $("#deleteTemplateModal").remove();

    // Append and show
    $("body").append(modalHtml);
    $("#deleteTemplateModal").modal("show");

    // Confirm delete
    $("#confirm-delete-btn")
      .off("click")
      .on("click", function () {
        deleteTemplate(templateId);
        $("#deleteTemplateModal").modal("hide");
      });
  }

  // ==================================================
  // DELETE TEMPLATE
  // ==================================================
  function deleteTemplate(templateId) {
    $.ajax({
      url: admin_url + "nexreports/delete_filter_template/" + templateId,
      type: "POST",
      dataType: "json",
      success: function (response) {
        if (response.success) {
          alert_float(
            "success",
            response.message || "Template deleted successfully"
          );

          // Reload dropdown
          $("#filter-templates-btn").trigger("click");
          $("#filter-templates-btn").trigger("click"); // Double click to refresh
        } else {
          alert_float(
            "danger",
            response.message || "Failed to delete template"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Error deleting template:", error);
        alert_float("danger", "Error deleting template");
      },
    });
  }

  // ==================================================
  // CLOSE DROPDOWN ON OUTSIDE CLICK
  // ==================================================
  $(document).on("click", function (e) {
    var dropdown = $("#filter-templates-dropdown");
    var btn = $("#filter-templates-btn");

    if (
      !dropdown.is(e.target) &&
      dropdown.has(e.target).length === 0 &&
      !btn.is(e.target) &&
      btn.has(e.target).length === 0
    ) {
      dropdown.fadeOut(200);
    }
  });

  // ==================================================
  // SAVE FILTER TEMPLATE (on Apply button click)
  // ==================================================
  $("#apply-filters-btn")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      console.log("Apply button clicked");

      // Check if user wants to save as template
      var saveAsTemplate = $("#save-filter-template").is(":checked");
      var templateName = $("#filter-template-name").val().trim();
      var setAsDefault = $("#set-as-default").is(":checked");

      if (saveAsTemplate) {
        if (!templateName) {
          alert_float("warning", "Please enter a template name!");
          $("#filter-template-name").focus();
          return;
        }

        // Collect current filters
        var filters = collectCurrentFilters();

        console.log("Saving filter template:", {
          name: templateName,
          filters: filters,
          setAsDefault: setAsDefault,
        });

        // Save template via AJAX
        $.ajax({
          url: admin_url + "nexreports/save_filter_template",
          type: "POST",
          dataType: "json",
          data: {
            template_name: templateName,
            filters: filters,
            set_as_default: setAsDefault ? "1" : "0",
          },
          success: function (response) {
            console.log("Save response:", response);

            if (response.success) {
              alert_float(
                "success",
                response.message || "Filter template saved successfully!"
              );

              // Reset the save form
              $("#save-filter-template").prop("checked", false);
              $("#filter-template-name").val("").prop("disabled", true);
              $("#set-as-default").prop("checked", false);

              // Reload table with filters
              if (typeof table !== "undefined" && table.ajax) {
                table.ajax.reload();
              }
            } else {
              alert_float(
                "danger",
                response.message || "Failed to save filter template"
              );
            }
          },
          error: function (xhr, status, error) {
            console.error("Error saving template:", error);
            alert_float("danger", "Error saving filter template: " + error);
          },
        });
      } else {
        // Just apply filters without saving
        console.log("Applying filters without saving template");
        if (typeof table !== "undefined" && table.ajax) {
          table.ajax.reload();
        }
      }
    });

  console.log("Filter Template functionality initialized successfully");
});
