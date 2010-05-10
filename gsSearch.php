<?php
// JSON backend for search

require_once 'GSAPI.php';

// Gets a GSAPI object for API calls
$sessionID = '';
if (!empty($_POST['sessionID'])) {
    $sessionID = $_POST['sessionID'];
}
$gsapi = GSAPI::getInstance($sessionID);
if (isset($_POST['query'])) {
    $limit = 30;
    if (!empty($_POST['limit'])) {
        $limit = $_POST['limit'];
    }
    // Get the list of songs from the API search
    $songs = $gsapi->searchSongs($_POST['query'], $_POST['limit']);
    print "<table id='save-music-choice-search'>";
    if (isset($songs['error'])) {
        // There was an error getting songs
        print "<tr><td>Error Code {$songs['error']}. Please try again.</td></tr>";
    } else {
        // Set up different styles for different wp versions
        $altClass = ($_POST['isVersion26'] == 'true') ? 'gsTr26' : 'gsTr27';
        $isSmallBox = ($_POST['isSmallBox'] == 1) ? true : false;
        $stringLimit = ($_POST['isVersion26'] == 1) ? 73 : 80;
        if (empty($songs)) {
            // No songs were found
            print "<tr class='gsTr1'><td>No Results Found by Grooveshark Plugin</td></tr>";
        } else {
            $index = 0;
            foreach ($songs as $song) {
                // Loop through all songs
                $songNameComplete = $song['SongName'] . ' by ' . $song['ArtistName'];
                if (strlen($songNameComplete) > $stringLimit) {
                    // Displays the song and artist, truncated if too long
                    $songNameComplete = substr($song['SongName'], 0, $stringLimit - 3 - strlen($song['ArtistName'])) . "&hellip; by" . $song['ArtistName'];
                }
                $songNameComplete = preg_replace("/\'/", "&lsquo;", $songNameComplete, -1);
                $songNameComplete = preg_replace("/\"/", "&quot;", $songNameComplete, -1);
                // print the row containing all of this song's data
                print (($index % 2) ? "<tr class='gsTr1'>" : "<tr class='$altClass'>") .
                      "<td class='gsTableButton'><a title='Add This Song To Your Post' class='gsAdd'"
                      . ((isset($_POST['isSidebar']) && $_POST['isSidebar'] == 1) ? "onclick='addToSelected(this)'" : '') . 
                      " name='$songNameComplete::{$song['SongID']}' style='cursor:pointer;' id='gsSong-{$song['SongID']}'></a></td>
                      <td class='gsTableButton'><a class='gsPlay'"
                      . ((isset($_POST['isSidebar']) && $_POST['isSidebar'] == 1) ? "onclick='toggleSong(this)'" : '') .  
                      " title='Play This Song' name='{$song['SongID']}' style='cursor:pointer;''></a></td>
                      <td>$songNameComplete</td>
                      </tr>";
                $index++;
            }
        }
    }
    print "</table>";
} else {
    // query not provided, return nothing
    print '';
}
?>
