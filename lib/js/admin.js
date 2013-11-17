jQuery(document).ready(function($){
    var doing_ajax = false;
    $(document).on('change', 'input#tgm-exchange-campaign-monitor-api-key', function(e){
        var data = {
            action:   'tgm_exchange_campaign_monitor_update_clients',
            api_key:  $('#tgm-exchange-campaign-monitor-api-key').val()
        };

        if ( ! doing_ajax ) {
            doing_ajax = true;
            $('.tgm-exchange-campaign-monitor-client-output .tgm-exchange-loading').css('display', 'inline');
            $.post(ajaxurl, data, function(res){
                $('.tgm-exchange-campaign-monitor-client-output').html(res);
                $('.tgm-exchange-campaign-monitor-client-output .tgm-exchange-loading').hide();
                $('.tgm-exchange-campaign-monitor-list-output .tgm-exchange-loading').css('display', 'inline');

                // Process the next ajax request to retrieve the lists for the client that was returned.
                var list_data = {
                    action:    'tgm_exchange_campaign_monitor_update_lists',
                    api_key:   $('#tgm-exchange-campaign-monitor-api-key').val(),
                    client_id: $('#tgm-exchange-campaign-monitor-clients').val()
                };

                $.post(ajaxurl, list_data, function(res){
                    $('.tgm-exchange-campaign-monitor-list-output').html(res);
                    $('.tgm-exchange-loading').hide();
                    doing_ajax = false;
                });
            });
        }
    });
    $(document).on('change', 'select#tgm-exchange-campaign-monitor-clients', function(e){
        var data = {
            action:    'tgm_exchange_campaign_monitor_update_lists',
            api_key:   $('#tgm-exchange-campaign-monitor-api-key').val(),
            client_id: $('#tgm-exchange-campaign-monitor-clients').val()
        };

        if ( ! doing_ajax ) {
            doing_ajax = true;
            $('.tgm-exchange-campaign-monitor-list-output .tgm-exchange-loading').css('display', 'inline');
            $.post(ajaxurl, data, function(res){
                $('.tgm-exchange-campaign-monitor-list-output').html(res);
                $('.tgm-exchange-loading').hide();
                doing_ajax = false;
            });
        }
    });
});