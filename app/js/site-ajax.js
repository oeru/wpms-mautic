// see http://solislab.com/blog/5-tips-for-using-ajax-in-wordpress/
// and https://pippinsplugins.com/using-ajax-your-plugin-wordpress-admin/

jQuery(document).ready(function() {
    console.log('mautic-site', mautic_site);

    var $ = jQuery;

    $('#mautic-userstatus').text('Ready...');

    // handle (re)load of the page
    $(window).on('load', function() {
        console.log('in load');
        $('#mautic-submit').attr('disabled', false);
        $('#mautic-userstatus').html('Ready...');
    });

    // set this up to submit on 'enter'
    $('input').keypress( function (e) {
        c = e.which ? e.which : e.keyCode;
        console.log('input: ' + c);
        if (c == 13) {
            $('#mautic-sync-form').submit();
            return false;
        }
    });

    // handle the submit button being pushed
    $('#mautic-site').submit(function() {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: mautic_site.ajaxurl,
            data: {
                'action': 'mautic_submit',
                'nonce-segment' : mautic_site.segment_nonce,
                'nonce-contact' : mautic_site.contact_nonce,
            },
            success: function(data) {
                var msg = '';
                console.log('Success: data: ', data);
                if (data.hasOwnProperty('success')) {
                    // strip links out
                    msg = data.message.replace(/<a[^>]*>[^<]*<\/a>/g, '');
                    console.log('Success msg', msg);
                    $('#mautic-create-segment').attr('disabled', false);
                    $('#mautic-userstatus').html(msg);
                } else if (data.hasOwnProperty('error')) {
                    msg = data.message;
                    console.log('message:', msg);
                    $('#mautic-create-segment').attr('disabled', false);
                    $('#mautic-userstatus').html(msg);
                }
                return true;
            },
            failure: function() {
                console.log('Failure: data: ', data);
                $('#mautic-userstatus').text('Error!');
            }
        });
        // if nothing else returns this first, there was a problem...
        return false;
    });
    $('#mautic-create-segment').submit(function() {
        console.log('clicked Create Segment');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: mautic_site.ajaxurl,
            data: {
                'action': 'mautic_create_segment',
                'site_id': $('mautic-current-site').value,
                'site_name': $('mautic-current-sitename').value,
                'nonce-segment' : mautic_site.segment_nonce,
                'nonce-contact' : mautic_site.contact_nonce,
            },
            success: function(data) {
                var msg = '';
                console.log('Success: data: ', data);
                if (data.hasOwnProperty('success')) {
                    // strip links out
                    msg = data.message.replace(/<a[^>]*>[^<]*<\/a>/g, '');
                    console.log('Success msg', msg);
                    $('#mautic-create-segment').attr('disabled', false);
                    $('#mautic-userstatus').html(msg);
                } else if (data.hasOwnProperty('error')) {
                    msg = data.message;
                    console.log('message:', msg);
                    $('#mautic-create-segment').attr('disabled', false);
                    $('#mautic-userstatus').html(msg);
                }
                return true;
            },
            failure: function() {
                console.log('Failure: data: ', data);
                $('#mautic-userstatus').text('Error!');
            }
        });
        // if nothing else returns this first, there was a problem...
        return false;
    });
});
