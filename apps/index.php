<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>OERu Mautic User Synchroniser</title>

        <?php wp_head(); ?>

        <!--[if lt IE 9]>
          <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <script>
        	// This is the URL where we need to make our AJAX calls.
        	// We are making it available to JavaScript as a global variable.
        	var ajaxurl = '<?php echo admin_url('admin-ajax.php')?>';
        </script>

    </head>

    <body>

		<div id="oeru-mautic">

			<h2>Synchronised Users With Mautic</h2>

            <p>User info will go here: Users in WP who aren't in Mautic, users in Mautic who aren't WP, and mappings to Segments.</p>

		</div>

        <?php wp_footer(); ?>

    </body>
</html>
