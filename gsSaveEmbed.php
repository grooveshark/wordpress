<?php

// JSON backend for saving widgets to the dashboard and sidebar

// Prepare the wordpress function get_option
if (!function_exists('get_option')) {
    require_once("../../../wp-config.php");
}

if (isset($_POST['embedCode'])) {
    $gs_options = get_option('gs_options');
    $embedCode = $_POST['embedCode'];
    if (isset($_POST['dashboardOption']) && ($_POST['dashboardOption'] == 1)) {
        $gs_options['dashboardPlaylists'] = array('id' => 'groovesharkID', 'embed' => stripslashes($embedCode));
    }
    update_option('gs_options', $gs_options);
}

