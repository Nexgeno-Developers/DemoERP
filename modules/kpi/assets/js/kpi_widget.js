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

})();
