// KPI Notifications JavaScript
// Guard: only run if jQuery and admin_url are available
(function() {
    'use strict';

    // Safety check - don't run if jQuery or admin_url missing
    if (typeof $ === 'undefined' || typeof admin_url === 'undefined') {
        console.warn('KPI Notifications: jQuery or admin_url not available, skipping init.');
        return;
    }

$(document).ready(function() {
    // Auto-refresh notification count every 15 seconds (reduced from 30 for faster updates)
    setInterval(function() {
        refreshKpiNotificationCount();
    }, 15000);
    
    // Refresh immediately when dropdown is opened
    $(document).on('show.bs.dropdown', '.kpi-notifications-wrapper', function() {
        refreshKpiNotificationCount();
        refreshKpiNotificationContent();
    });
    
    // Handle request item clicks
    $(document).on('click', '.kpi-request-item[data-request-id]', function(e) {
        e.preventDefault();
        var requestId = $(this).data('request-id');
        var requestType = $(this).data('request-type');
        openRequestDetails(requestType, requestId);
    });
    
    // Handle tab switching without closing dropdown
    $(document).on('click', '.kpi-notifications .nav-tabs a', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).tab('show');
    });
    
    // Initial load
    refreshKpiNotificationCount();
});

/**
 * Refresh notification count via AJAX
 */
function refreshKpiNotificationCount() {
    $.get(admin_url + 'kpi/notifications/get_notification_count', function(response) {
        if (response.success) {
            var badge = $('.kpi-notifications-badge');
            var total = response.total;
            
            if (total > 0) {
                badge.text(total).removeClass('hide').show();
            } else {
                badge.addClass('hide').hide();
            }
            
            // Update tab badges
            updateTabBadge('ot', response.ot_count);
            updateTabBadge('pl', response.pl_count);
        }
    }, 'json').fail(function() {
        console.log('Failed to refresh KPI notification count');
    });
}

/**
 * Refresh notification content (request lists) via AJAX
 */
function refreshKpiNotificationContent() {
    // Refresh OT requests
    $.get(admin_url + 'kpi/notifications/get_requests/ot', function(html) {
        $('#kpi-ot-tab').html(html);
    }).fail(function() {
        console.log('Failed to refresh OT requests');
    });
    
    // Refresh PL requests
    $.get(admin_url + 'kpi/notifications/get_requests/pl', function(html) {
        $('#kpi-pl-tab').html(html);
    }).fail(function() {
        console.log('Failed to refresh PL requests');
    });
}

/**
 * Update individual tab badge with danger red color
 */
function updateTabBadge(type, count) {
    var tabLink = $('a[href="#kpi-' + type + '-tab"]');
    var badge = tabLink.find('.badge');
    
    if (count > 0) {
        if (badge.length === 0) {
            // Create badge if it doesn't exist - using danger red
            tabLink.append(' <span class="badge" style="background-color: #dc3545 !important; color: white !important;">' + count + '</span>');
        } else {
            // Update existing badge and ensure red color
            badge.text(count)
                 .css({
                     'background-color': '#dc3545',
                     'color': 'white'
                 })
                 .removeClass('badge-warning badge-info badge-default')
                 .addClass('badge-danger');
        }
    } else {
        // Remove badge if count is 0
        badge.remove();
    }
}

/**
 * Open PL request details using existing timesheets page
 */
function openPLDetails(id) {
    var url = admin_url + 'timesheets/requisition_detail/' + id;
    window.open(url, '_blank');
}

/**
 * Open OT request details using existing timesheets modal
 */
function openOTDetails(id) {
    // Use the existing timesheets additional timesheet modal
    var url = admin_url + 'timesheets/requisition_manage?tab=additional_timesheets&additional_timesheets_id=' + id;
    window.open(url, '_blank');
}

/**
 * Open request details in new tab (legacy function - keeping for compatibility)
 */
function openRequestDetails(type, id) {
    if (type === 'pl') {
        openPLDetails(id);
    } else if (type === 'ot') {
        openOTDetails(id);
    }
}

/**
 * Approve request (used in details pages)
 */
function approveRequest(type, id) {
    if (!confirm('Are you sure you want to approve this request?')) {
        return;
    }
    
    $.post(admin_url + 'kpi/notifications/approve_request', {
        type: type,
        id: id
    }, function(response) {
        if (response.success) {
            alert_float('success', response.message);
            setTimeout(function() {
                location.reload();
            }, 1000);
            
            // Refresh parent window notification count if it exists
            if (window.opener && window.opener.refreshKpiNotificationCount) {
                window.opener.refreshKpiNotificationCount();
            }
        } else {
            alert_float('danger', response.message);
        }
    }, 'json').fail(function() {
        alert_float('danger', 'Failed to approve request. Please try again.');
    });
}

/**
 * Show reject modal (used in details pages)
 */
function showRejectModal(type, id) {
    window.currentRequestType = type;
    window.currentRequestId = id;
    $('#rejectModal').modal('show');
}

/**
 * Confirm rejection with reason (used in details pages)
 */
function confirmReject() {
    var reason = $('#rejection_reason').val().trim();
    
    if (!reason) {
        alert_float('warning', 'Please provide a reason for rejection.');
        return;
    }
    
    $.post(admin_url + 'kpi/notifications/reject_request', {
        type: window.currentRequestType,
        id: window.currentRequestId,
        reason: reason
    }, function(response) {
        if (response.success) {
            $('#rejectModal').modal('hide');
            alert_float('success', response.message);
            setTimeout(function() {
                location.reload();
            }, 1000);
            
            // Refresh parent window notification count if it exists
            if (window.opener && window.opener.refreshKpiNotificationCount) {
                window.opener.refreshKpiNotificationCount();
            }
        } else {
            alert_float('danger', response.message);
        }
    }, 'json').fail(function() {
        alert_float('danger', 'Failed to reject request. Please try again.');
    });
}

/**
 * Handle dropdown toggle to prevent closing when clicking inside
 */
$(document).on('click', '.kpi-notifications .dropdown-menu', function(e) {
    e.stopPropagation();
});

/**
 * Handle tab navigation specifically
 */
$(document).on('click', '.kpi-notifications .nav-tabs a', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Remove active class from all tabs
    $(this).closest('.nav-tabs').find('li').removeClass('active');
    $(this).closest('.nav-tabs').find('a').removeClass('active');
    
    // Add active class to clicked tab
    $(this).parent('li').addClass('active');
    $(this).addClass('active');
    
    // Show corresponding tab content
    var target = $(this).attr('href');
    $(this).closest('.kpi-notifications').find('.tab-pane').removeClass('active');
    $(this).closest('.kpi-notifications').find(target).addClass('active');
});

/**
 * Handle notification icon click analytics (optional)
 */
$(document).on('click', '.kpi-notifications-icon', function() {
    // Track notification icon clicks for analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'click', {
            'event_category': 'KPI Notifications',
            'event_label': 'Icon Click'
        });
    }
});

/**
 * Handle tab click analytics (optional)
 */
$(document).on('click', '.kpi-notifications .nav-tabs a', function() {
    var tabType = $(this).attr('href').includes('ot') ? 'OT' : 'PL';
    
    // Track tab clicks for analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'click', {
            'event_category': 'KPI Notifications',
            'event_label': 'Tab Switch - ' + tabType
        });
    }
});

/**
 * Initialize tooltips for notification elements
 */
function initKpiNotificationTooltips() {
    $('.kpi-notifications [data-toggle="tooltip"]').tooltip();
}

/**
 * Cleanup function for when notification dropdown is hidden
 */
$(document).on('hidden.bs.dropdown', '.kpi-notifications-wrapper', function() {
    // Clean up any temporary states or timers
    clearTimeout(window.kpiNotificationRefreshTimer);
});

/**
 * Refresh when page becomes visible again (user switches back to tab)
 */
$(document).ready(function() {
    // Page visibility API for refreshing when user returns to tab
    if (typeof document.hidden !== "undefined") {
        document.addEventListener("visibilitychange", function() {
            if (!document.hidden) {
                // Page became visible, refresh notifications
                setTimeout(function() {
                    refreshKpiNotificationCount();
                }, 1000);
            }
        });
    }
    
    // Also refresh when window gains focus
    $(window).on('focus', function() {
        setTimeout(function() {
            refreshKpiNotificationCount();
        }, 500);
    });
});

/**
 * Enhanced error handling for AJAX requests
 */
$(document).ajaxError(function(event, xhr, settings) {
    if (settings.url && settings.url.includes('kpi/notifications')) {
        console.error('KPI Notifications AJAX Error:', xhr.status, xhr.statusText);
        
        // Show user-friendly error message for critical failures
        if (xhr.status === 403) {
            alert_float('danger', 'Access denied. You may not have permission to perform this action.');
        } else if (xhr.status === 500) {
            alert_float('danger', 'Server error occurred. Please contact administrator.');
        }
    }
});

/**
 * Keyboard navigation support
 */
$(document).on('keydown', '.kpi-request-item', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).click();
    }
});

/**
 * Accessibility improvements
 */
$(document).ready(function() {
    // Add ARIA labels for screen readers
    $('.kpi-notifications-icon').attr('aria-label', 'KPI Pending Requests');
    $('.kpi-request-item').attr('role', 'button').attr('tabindex', '0');
    
    // Initialize tooltips
    initKpiNotificationTooltips();
});
})(); // end IIFE safety wrapper
