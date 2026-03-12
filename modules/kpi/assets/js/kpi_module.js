(function ($) {
    "use strict";

    var table_kpi = $('table#table-kpi');
    var show_full_filters = KPI.show_full_filters;
    var logged_staff_id = KPI.current_staff_id;
    var _table_api = null;

    if (table_kpi.length) {

        function updateViewFilter() {
            var staff_id = show_full_filters ? ($('#staff_filter').val() || '') : logged_staff_id;

            if (show_full_filters && !staff_id) {
                $('#view_filter').val('monthly').prop('disabled', true);
                $('label[for="view_filter"]').addClass('text-muted');
            } else if (staff_id) {
                $('#view_filter').prop('disabled', false);
                $('label[for="view_filter"]').removeClass('text-muted');

                if (!$('#view_filter').val()) {
                    $('#view_filter').val('daily');
                }
            }
        }

        function updateActiveButton(activeButton) {
            if (show_full_filters) {
                $('#quick-date-buttons .quick-date')
                    .removeClass('active btn-primary')
                    .addClass('btn-default');

                $(activeButton)
                    .removeClass('btn-default')
                    .addClass('active btn-primary');
            }
        }

        function setTodaysDate() {
            var today = new Date().toISOString().split('T')[0];

            if (show_full_filters) {
                $('#from_date_filter').val(today);
                $('#to_date_filter').val(today);
            }
        }

        function initializeSelectPicker() {
            if (show_full_filters && $('#staff_filter').length) {
                $('#staff_filter').selectpicker('refresh');
            }
        }

        function getFilterParams() {
            var params = {};
            
            var view = $('#view_filter').val();
            if (view) params.view = view;
            
            var month = $('#month_filter').val();
            if (month) params.month = month;
            
            var staff_id = show_full_filters ? ($('#staff_filter').val() || '') : logged_staff_id;
            if (staff_id) params.staff_id = staff_id;
            
            var from_date = show_full_filters ? ($('#from_date_filter').val() || '') : '';
            if (from_date) params.from_date = from_date;
            
            var to_date = show_full_filters ? ($('#to_date_filter').val() || '') : '';
            if (to_date) params.to_date = to_date;
            
            var status_filter = show_full_filters ? ($('#status_filter').val() || 'all') : '';
            if (status_filter && status_filter !== 'all') params.status_filter = status_filter;
            
            return params;
        }

        function initializeTable() {
            if (_table_api) {
                return _table_api;
            }

            updateViewFilter();
            var view = $('#view_filter').val();

            var url = KPI.admin_url + 'kpi/table';
            
            // Use serverParams to pass filter parameters
            var KpiServerParams = {};
            var filterParams = getFilterParams();
            
            // Store filter values in hidden inputs for DataTables serverParams
            var $hiddenInputs = $('._hidden_inputs._filters._kpi_filters');
            if ($hiddenInputs.length === 0) {
                $hiddenInputs = $('<div class="_hidden_inputs _filters _kpi_filters" style="display:none;"></div>');
                $('body').append($hiddenInputs);
            }
            $hiddenInputs.empty();
            
            $.each(filterParams, function(key, value) {
                $hiddenInputs.append('<input type="hidden" name="' + key + '" value="' + value + '">');
                KpiServerParams[key] = '[name="' + key + '"]';
            });

            _table_api = initDataTable(table_kpi, url, undefined, undefined, KpiServerParams, [1, 'desc']);
            
            // Fix first column visibility after table initialization
            if (_table_api) {
                _table_api.on('draw', function() {
                    // Ensure first column is fully visible
                    setTimeout(function() {
                        var $firstCol = $('#table-kpi thead th:first-child, #table-kpi tbody td:first-child');
                        $firstCol.css({
                            'padding-left': '20px',
                            'padding-right': '15px',
                            'min-width': '200px',
                            'width': '200px',
                            'position': 'relative',
                            'left': '0',
                            'margin-left': '0',
                            'transform': 'none',
                            'visibility': 'visible',
                            'opacity': '1'
                        });
                        
                        // Ensure table wrapper doesn't cut off first column
                        $('.dataTables_wrapper').css({
                            'padding-left': '0',
                            'margin-left': '0'
                        });
                        
                        $('.table-responsive').css({
                            'padding-left': '0',
                            'margin-left': '0'
                        });
                    }, 100);
                });
            }
            
            return _table_api;
        }

        function reloadTable() {
            if (!_table_api) {
                initializeTable();
                return;
            }

            updateViewFilter();
            
            // Update hidden inputs with new filter values
            var $hiddenInputs = $('._hidden_inputs._filters._kpi_filters');
            $hiddenInputs.empty();
            
            var filterParams = getFilterParams();
            $.each(filterParams, function(key, value) {
                $hiddenInputs.append('<input type="hidden" name="' + key + '" value="' + value + '">');
            });

            // Reload table with new parameters - preserve current page and length
            _table_api.ajax.reload(null, false);
        }
        
        function reinitializeTable() {
            // Destroy and recreate table when needed (e.g., major filter changes)
            if (_table_api) {
                try {
                    _table_api.destroy();
                } catch(e) {
                    // Ignore errors if table is already destroyed
                }
                _table_api = null;
            }
            initializeTable();
        }

        function clearFilters() {
            if (show_full_filters) {
                $('#staff_filter').selectpicker('val', '');
                $('#month_filter').val('');
                $('#status_filter').val('all');

                setTodaysDate();
                updateActiveButton($('.quick-date[data-days="0"]'));
            }

            updateViewFilter();

            reloadTable();
        }

        function applyKPIFilters() {
            reloadTable();
        }

        function initializeWithToday() {
            setTodaysDate();
            updateViewFilter();
            initializeTable();
            setTimeout(initializeSelectPicker, 100);
        }

        // ---- Event Bindings ----
        initializeWithToday();

        $('.quick-date').on('click', function () {
            updateActiveButton(this);
            applyKPIFilters();
        });

        $('#clearFilters').on('click', clearFilters);

        $('#view_filter, #month_filter, #status_filter').on('change', applyKPIFilters);

        if (show_full_filters) {
            $('#staff_filter').on('changed.bs.select', function () {
                updateViewFilter();
                applyKPIFilters();
            });

            $('#from_date_filter, #to_date_filter').on('change', function () {
                updateViewFilter();
                applyKPIFilters();
            });
        }
    }

})(jQuery);
