// see http://solislab.com/blog/5-tips-for-using-ajax-in-wordpress/
// and https://pippinsplugins.com/using-ajax-your-plugin-wordpress-admin/

jQuery(document).ready(function() {
    console.log('mautic-auth-form', mautic_auth);

    var $ = jQuery;

    $('#mautic-userstatus').text('Ready...');

    $('#mautic-auth-form').validate({
        validClass: "valid",
        rules: {
            'mautic-url': {
                required: true,
                url: true
            },
            'mautic-user': {
                required: true
            },
            'mautic-password': {
                required: true
            }
        },
        messages: {
            'mautic-url': {
                required: "You must enter a valid web address (URL) for your Mautic API endpoint."
            },
            'mautic-user': {
                required: "You must enter the username of a user able to access your Mautic API. (case sensitive)"
            },
            'mautic-password': {
                required: "You must enter the password for the Mautic API user. (case sensitive)"
            }
        }
    });

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
            $('#mautic-auth-form').submit();
            return false;
        }
    });

    // handle the submit button being pushed
    $('#mautic-auth-form').submit(function() {
        // disable the submit button until it returns.
        $('#mautic-submit').attr('disabled', true);
        $('#mautic-userstatus').html('Processing...');
        console.log('url: ', mautic_auth.ajaxurl);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: mautic_auth.ajaxurl,
            data: {
                'action': 'mautic_submit',
                'nonce-submit' : mautic_auth.submit_nonce,
                'url' : $.trim($('#mautic-url').val()),
                'user' : $.trim($('#mautic-user').val()),
                'password' : $.trim($('#mautic-password').val())
            },
            success: function(data) {
                var msg = '';
                console.log('Success: data: ', data);
                if (data.hasOwnProperty('success')) {
                    // strip links out
                    msg = data.message.replace(/<a[^>]*>[^<]*<\/a>/g, '');
                    console.log('Success msg', msg);
                    $('#mautic-submit').attr('disabled', false);
                    $('#mautic-userstatus').html(msg);
                } else if (data.hasOwnProperty('error')) {
                    msg = data.message;
                    console.log('message:', msg);
                    $('#mautic-submit').attr('disabled', false);
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
