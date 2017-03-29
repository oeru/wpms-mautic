// see http://solislab.com/blog/5-tips-for-using-ajax-in-wordpress/
// and https://pippinsplugins.com/using-ajax-your-plugin-wordpress-admin/

jQuery(document).ready(function() {
    console.log('mautic-sync-ajax', mautic_sync_ajax);

    var $ = jQuery;

    $('#mautic-userstatus').text('Ready...');

    $('#mautic-sync-form').validate({
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
            $('#mautic-sync-form').submit();
            return false;
        }
    });

    // handle the submit button being pushed
    $('#mautic-sync-form').submit(function() {
        // disable the submit button until it returns.
        $('#mautic-submit').attr('disabled', true);
        $('#mautic-userstatus').html('Processing...');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: mautic_sync_ajax.ajaxurl,
            data: {
                'action': 'mautic_submit',
                'nonce-submit' : mautic_sync_ajax.submit_nonce,
                'url' : $.trim($('#mautic-url').val()),
                'user' : $.trim($('#mautic-user').val()),
                'password' : $.trim($('#mautic-password').val())
            },
            success: function(data) {
                // re-enable the submit button
                $('#mautic-submit').attr('disabled', false);
                $('#mautic-auth').attr('disabled', false);
                $('#mautic-userstatus').html('Saved...');
                console.log('Success: data: ', data);
                return true;
            },
            failure: function() {
                console.log('Failure: data: ', data);
                $('#mautic-auth').attr('disabled', true);
                $('#mautic-userstatus').text('Error!');
            }
        });
        // if nothing else returns this first, there was a problem...
        return false;
    });

    // process the authentication test
    $('#mautic-auth').click(function() {
        $('#mautic-submit').attr('disabled', false);
        $('#mautic-userstatus').html('Authenticating...');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: mautic_sync_ajax.ajaxurl,
            data: {
                'action': 'mautic_auth',
                //'do' : 'something',
                'nonce-auth' : mautic_sync_ajax.auth_nonce
            },
            success: function(data) {
                $('#mautic-submit').attr('disabled', false);
                $('#mautic-userstatus').html('Successfully authenticated!');
                console.log('Success: data: ', data);
                return true;
            },
            failure: function() {
                console.log('Failure: data: ', data);
                $('#mautic-submit').attr('disabled', false);
                $('#mautic-userstatus').text('Authentication Failed.');
            }
        });
        return false;
    });
});
