<?php

// JSON backend for getting playlist links for posts


// Load the API file
if (!class_exists('GSAPI')) {
    require_once("GSAPI.php");
}

if (!isset($_POST['username']) && !isset($_POST['token'])) {
    print "You must be logged in to create playlist links. You can log in at the plugin settings page.";
} else {
    if ((isset($_POST['sessionID'])) && (isset($_POST['songIDs']))) {
        $gsapi = GSAPI::getInstance($_POST['sessionID']);
        if ($gsapi->getUserID() == 0) {
            // user not logged in, authenticate
            $gsapi->authenticateUser($_POST['username'], $_POST['token']);
        }
        $playlistName = isset($_POST['playlistName']) ? $_POST['playlistName'] : 'Songs for ' . date('M j');
        $displayPhrase = isset($_POST['displayPhrase']) ? $_POST['displayPhrase'] : 'Grooveshark Playlist';
        $songsArray = explode('::', $_POST['songIDs']);
        if (count($songsArray) < 1) {
            //No songs provided, return nothing
            print 'There was an error getting your songs. Please try again.';
        } else {
            $playlistID = $gsapi->playlistCreate($playlistName, $songsArray);
            if ($playlistID <= 0) {
                if ($playlistID == -1) {
                    $playlistName .= ' ' . date('M j - H:i');
                    $playlistID = $gsapi->playlistCreate($playlistName, $songsArray);
                    if ($playlistID > 0) {
                        $playlistUrl = $gsapi->getPlaylistUrl($playlistID);
                        print "<a href='$playlistUrl' target='_blank'>$displayPhrase: $playlistName</a>";
                    } else {
                        print "Your playlist could not be created. Please try again.";
                    }
                } else {
                    // Playlist could not be created...
                    print "Your playlist could not be created. Please try again.";
                }
            } else {
                $playlistUrl = $gsapi->getPlaylistUrl($playlistID);
                print "<a href='$playlistUrl' target='_blank'>$displayPhrase: $playlistName</a>";
            }
        }
    } else {
        // songIDs and sessionID not provided, return nothing
        print 'There was an error creating your playlists. Please try again.';
    }
}
?>
