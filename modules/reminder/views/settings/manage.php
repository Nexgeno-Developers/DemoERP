<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-3">
                <ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked">
                    <li class="<?php echo ($group == 'amc_types') ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('reminder/settings?group=amc_types'); ?>" data-group="amc_types">
                            <i class="fa fa-tags"></i> <?php echo _l('amc_types'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-md-9">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if ($group == 'amc_types') { ?>
                            <?php $this->load->view('reminder/settings/amc_types'); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<?php if ($group == 'amc_types') { ?>
<script>
(function($) {
    "use strict";
    
    window.add_amc_type = function() {
        $('#amc_type_modal_title').text('<?php echo _l('add_amc_type'); ?>');
        $('#amc_type_form')[0].reset();
        $('#amc_type_id').val('');
        $('#active').prop('checked', true);
        $('#amc_type_form').attr('action', '<?php echo admin_url('reminder/add_amc_type'); ?>');
        $('#amc_type_modal').modal('show');
    };
    
    window.edit_amc_type = function(id) {
        $.get(admin_url + 'reminder/get_amc_type/' + id, function(response) {
            var type = JSON.parse(response);
            $('#amc_type_id').val(type.id);
            $('#name').val(type.name);
            $('#description').val(type.description);
            $('#order').val(type.order);
            $('#active').prop('checked', type.active == 1);
            $('#amc_type_modal_title').text('<?php echo _l('edit_amc_type'); ?>');
            $('#amc_type_form').attr('action', admin_url + 'reminder/update_amc_type/' + id);
            $('#amc_type_modal').modal('show');
        });
    };
    
    window.delete_amc_type = function(id) {
        if (confirm('<?php echo _l('confirm_action_prompt'); ?>')) {
            $.post(admin_url + 'reminder/delete_amc_type/' + id, function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    alert_float('success', result.message);
                    location.reload();
                } else {
                    alert_float('danger', result.message);
                }
            });
        }
    };
    
    // Form submission handler
    $(document).ready(function() {
        $('#amc_type_form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert_float('success', result.message);
                        $('#amc_type_modal').modal('hide');
                        location.reload();
                    } else {
                        alert_float('danger', result.message);
                    }
                }
            });
        });
    });
})(jQuery);
</script>
<?php } ?>

