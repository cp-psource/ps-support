$(function() {
    $('#fetch_imap').on('change', function() {
        if ($(this).val() == 'enabled')
            $('.imap-settings').attr('disabled', false);
        else
            $('.imap-settings').attr('disabled', true);
    });

    var pro_sites_check = $('#pro_sites');
    psource_support_toggle_level_select(pro_sites_check);
    pro_sites_check.on('change',function() {
        psource_support_toggle_level_select($(this));
    });

    var pro_sites_check_faq = $('#pro_sites_faq');
    psource_support_toggle_level_select(pro_sites_check_faq);
    pro_sites_check_faq.on('change',function() {
        psource_support_toggle_level_select($(this));
    });

    function psource_support_toggle_level_select(element) {
        var select_name = element.attr('name') + '_levels';
        if (!element.is(':checked'))
            $('select[name="' + select_name + '"]').attr('disabled', true);
        else
            $('select[name="' + select_name + '"]').attr('disabled', false);
    }
});