<?php

// JSON backend for getting song links for posts

// Load the API file
if (!class_exists('GSAPI')) {
    require_once("GSAPI.php");
}

if (isset($_POST['songID'])) {
    $gsapi = GSAPI::getInstance();
    print $gsapi->getSongUrl($_POST['songID']);
} else {
    print '';
}
