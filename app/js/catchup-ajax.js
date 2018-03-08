// see http://solislab.com/blog/5-tips-for-using-ajax-in-wordpress/
// and https://pippinsplugins.com/using-ajax-your-plugin-wordpress-admin/

jQuery(document).ready(function() {
    console.log('mautic-catchup', mautic_catchup);

    var $ = jQuery;

    // handle (re)load of the page
    $(window).on('load', function() {
        console.log('in load');
        $('#mautic-catchup-submit').attr('disabled', false);
        $('#mautic-userstatus').html('Ready...');
    });

    $('#mautic-userstatus').text('Ready...');

    // set this up to submit on 'enter'
    $('input').keypress( function (e) {
        c = e.which ? e.which : e.keyCode;
        console.log('input: ' + c);
        if (c == 13) {
            $('#mautic-catchup-form').submit();
            return false;
        }
    });

    // handle the submit button being pushed
    $('#mautic-catchup-submit').click(function() {
        console.log('Catchup submitted!');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: mautic_catchup.ajaxurl,
            data: {
                'action': 'mautic_catchup',
                'mautic-catchup-nonce' : mautic_catchup.catchup_nonce,
            },
            success: function(data) {
                var msg = '';
                console.log('Success: data: ', data);
                if (data.hasOwnProperty('success')) {
                    // strip links out
                    msg = data.message.replace(/<a[^>]*>[^<]*<\/a>/g, '');
                    console.log('Success msg', msg);
                    $('#mautic-catchup').attr('disabled', false);
                    $('#mautic-userstatus').html(msg);
                } else if (data.hasOwnProperty('error')) {
                    msg = data.message;
                    console.log('message:', msg);
                    $('#mautic-catchup').attr('disabled', false);
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
