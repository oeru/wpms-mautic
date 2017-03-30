<?php

/*
 * base class for all Mautic Sync classes with utility functions
 */

abstract class MauticBase {

    // log things to the web server log
    public function logger($message) {
        if (MAUTIC_DEBUG) {
            error_log('DEBUG('.get_called_class().'::'.__FUNCTION__.'): '.$message);
            //error_log('DEBUG('.__CLASS__.'..'.__FUNCTION__.'): '.$message);
            //error_log('DEBUG('.__CLASS__.'..'.__METHOD__.'): '.$message);
            //error_log('DEBUG('.__METHOD__.'): '.$message);
        }
    }

    // construct JSON responses to AJAX queries
    public function response($a) {
        echo json_encode($a);
        die();
    }
}
