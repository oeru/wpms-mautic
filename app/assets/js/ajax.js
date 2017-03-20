// see http://solislab.com/blog/5-tips-for-using-ajax-in-wordpress/
// and https://pippinsplugins.com/using-ajax-your-plugin-wordpress-admin/

jQuery(document).ready(function() {
    console.log('mautic-sync-ajax', mautic_sync_ajax);

    var $ = jQuery, auth_info = $.trim($('#mautic-auth-info').val());

    auth_info = (auth_info == '1') ? true : false;

    console.log('auth_info = '+auth_info);

    // if auth_info is recorded, this is true
    $('#mautic-auth').prop('disabled', !auth_info);
    $('#mautic-userstatus').text('Ready...');

    $('#mautic-sync-form').validate({
        /*submitHandler: function(form) {
            //console.log('you just submitted the form in the validator');
            //$(form).ajaxSubmit();
        },*/
        validClass: "valid",
       rules: {
            'mautic-url': {
                required: true,
                url: true
            },
            'mautic-public-key': {
                required: true,
                rangelength: [50, 52]
            },
            'mautic-secret-key': {
                required: true,
                rangelength: [50, 50]
            },
            'mautic-callback-url': {
                required: true,
                url: true
            }
        },
        messages: {
            'mautic-url': {
                required: "You must enter a valid web address (URL) for your Mautic API endpoint.",
                url: "You must enter a valid web address (URL) for your Mautic API endpoint."
            },
            'mautic-public-key': {
                required: "You must submit a 52 character public key from your Mautic API settings.",
                rangelength: "Your key must be 52 characters (a combination of numbers, letters, and '_') long."
            },
            'mautic-secret-key': {
                required: "You must submit a 50 character secret key from your Mautic API settings.",
                rangelength: "Your key must be 50 characters (a combination of numbers and letters) long."
            },
            'mautic-callback-url': {
                required: "You must enter a valid web address (URL) for your callback.",
                url: "You must enter a valid web address (URL) for your callback."
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
                //'do' : 'something',
                'nonce-submit' : mautic_sync_ajax.submit_nonce,
                'url' : $.trim($('#mautic-url').val()),
                'public_key' : $.trim($('#mautic-public-key').val()),
                'secret_key' : $.trim($('#mautic-secret-key').val()),
                'callback_url' : $.trim($('#mautic-callback-url').val())
            },
            success: function(data) {
                // re-enable the submit button
                console.log('Set auth_info = true');
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
        $('#mautic-submit').attr('disabled', true);
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
                $('#mautic-userstatus').html('Authenticated...');
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

