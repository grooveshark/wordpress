<?php
/*
Plugin Name: Grooveshark for Wordpress
Plugin URI: http://www.grooveshark.com/wordpress
Description: Search for <a href="http://www.grooveshark.com">Grooveshark</a> songs and add links to a song or song widgets to your blog posts. 
Author: Roberto Sanchez and Vishal Agarwala
Version: 1.4.1
Author URI: http://www.grooveshark.com
*/

/*
Copyright 2010 Escape Media Group (email: roberto.sanchez@escapemg.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'GSAPI.php';
function gs_json_encode($content) {
    if (!extension_loaded('json')) {
        if (!class_exists('GS_Services_JSON')) {
            require_once('GSJSON.php');
        }
        $json = new GS_Services_JSON;
        return $json->encode($content);
    } else {
        return json_encode($content);
    }
}


// Checks to see if the options for this plugin exists. If not, the options are added
if (get_option('gs_options') == FALSE) {
    add_option('gs_options',array(
        'token' => '', // auth token used for API login
        'userID' => 0, // GS userID used for favorites/playlists
        'username' => '', // GS username used for API login
        'numberOfSongs' => 30, // restrict results returned for search
        'displayPhrase' => 'Grooveshark Song Link', // Display phrase precedes song/playlist name as a cue to readers
        'widgetWidth' => 250, // width of the GS widget used to embed songs in posts
        'widgetHeight' => 176, // height of the GS widget
        'colorScheme' => 'default', // color scheme used for the GS widget
        'sidebarPlaylists' => array('id' => '', 'embed' => ''), // Save the playlist id and embed code for the sidebar playlist
        'userPlaylists' => array(),
        'dashboardPlaylists' => array(), // Save playlists to display on the dashboard
        'musicComments' => 0, // Toggle the option to enable music comments
        'commentDisplayOption' => 'widget', // Display music in comments as widget/link
        'commentWidgetWidth' => 200, // Width of music widgets in comments
        'commentWidgetHeight' => 0, // Height of music widgets in comments (0 for autoadjust)
        'commentDisplayPhrase' => 'Grooveshark Song Link', // Display phrase for music comment links
        'commentPlaylistName' => 'Blog Playlist', // Names of playlists saved using music comments
        'commentColorScheme' => 0, // Color scheme of music comment playlists
        'commentSongLimit' => 1, // Limit the number of songs that can be added to comment widgets (0 for no limit, also limit only applies to widget)
        'javascriptPos' => 'head',
        'gsRssOption' => 0,
        'sidebarRss' => array()));
}


// Sets up a sessionID for use with the rest of the script when making API calls
if (isset($_POST['sessionID']) and $_POST['sessionID'] != 0) {
    GSAPI::getInstance($_POST['sessionID']);
} else {
    GSAPI::getInstance();
}

$gs_options = get_option('gs_options');
if (empty($gs_options['javascriptPos'])) {
    // This is an update, reset a few essential options to ensure a smooth transition
    $gs_options['commentDisplayOption'] = 'widget';
    $gs_options['includePlaylist'] = 1;
    $gs_options['displayPhrase'] = 'Grooveshark Song Link';
    $gs_options['javascriptPos'] = 'head';
    $gs_options['gsRssOption'] = 0;

    update_option('gs_options', $gs_options);
}

add_action('admin_menu','addGroovesharkBox');

function addGroovesharkBox() 
{
    // Adds the GS "Add Music" box to the post edit and page edit pages
    if( function_exists('add_meta_box')) {
        add_meta_box('groovesharkdiv','Add Music','groovesharkBox','post','advanced','high');
        add_meta_box('groovesharkdiv','Add Music','groovesharkBox','page','advanced','high');
    } else {
        add_action('dbx_post_advanced','oldGroovesharkBox');
        add_action('dbx_page_advanced','oldGroovesharkBox');
    }
}

// The code for the "Add Music" box below the content editing text area.
function groovesharkBox() 
{
    // Get a GSAPI object for API calls in the groovesharkBox() function
    $gsapi = GSAPI::getInstance();
    $sessionID = $gsapi->getSessionID();
    $siteurl = get_option('siteurl'); // used to provide links to js/images
    $version = get_bloginfo('version'); // used to load fixes specific to certain WP versions
    $isVersion26 = stripos($version, '2.6') !== false;
    $isVersion25 = stripos($version, '2.5') !== false;
    // The basic code to display the postbox. The ending tags for div are at the end of the groovesharkBox() function
    print "
            <input type='hidden' name='isSmallBox' id='isSmallBox' value='0' />
            <input type='hidden' name='songIDs' id='songIDs' value='0' />
            <input type='hidden' name='gsTagStatus' id='gsTagStatus' value='0' />
            <input type='hidden' name='gsSessionID' value='$sessionID' id='gsSessionID' />
            <input type='hidden' name='gsBlogUrl' value='$siteurl' id='gsBlogUrl' />
            <input type='hidden' name='wpVersion' value='$version' id='wpVersion' />
            <input type='hidden' name='gstabledata' id='gstabledata' />
            <input type='hidden' id='gsDataStore' />
            
                
    
           <div id='jsPlayerReplace'></div>
		   <!--[if IE 7]>
		   <div id='IE7'>
		   <![endif]-->
           <div id='gsInfo'>
           <p>Add music to your posts. Go to the <a href='$siteurl/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a> for more music options.</p>
           <div id='apContainer'></div>
           </div>";
    // Retrieves the saved options for this plugin
    $gs_options = get_option('gs_options');
    $username = $gs_options['username'];
    $token = $gs_options['token'];
    print "<link type='text/css' rel='stylesheet' href='" . get_bloginfo('wpurl') . "/wp-content/plugins/grooveshark/css/grooveshark.css' />\n
           <input type='hidden' name='gsUsername' id='gsUsername' value='$username' />\n
           <input type='hidden' name='gsToken' id='gsToken' value='$token' />\n
           <!--[if IE]><link type='text/css' rel='stylesheet' href='" . get_bloginfo('wpurl') . "/wp-content/plugins/grooveshark/css/grooveshark-ie.css' /><![endif]-->\n
           <script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.0/jquery.min.js'></script>
           <script type='text/javascript' src='$siteurl/wp-content/plugins/grooveshark/js/grooveshark.full.js'></script>
           <script type='text/javascript' src='$siteurl/wp-content/plugins/grooveshark/js/grooveshark.js'></script>";
    // Sets up the tabs for "search," "favorites," and "playlists."
    $tabClass = 'gsTabActive27';
    $tabClass2 = 'gsTabInactive27';
    $tabContainerClass = 'gsTabContainer27';
    $songClass = 'gsSongBox27';
    $versionClass = 'gs27';
    if ($isVersion26 || $isVersion25) {
        $tabContainerClass = 'gsTabContainer26';
        $tabClass = 'gsTabActive26';
        $tabClass2 = 'gsTabInactive26';
        $songClass = 'gsSongBox26';
        $versionClass = 'gs26';
    }
    print "<div id='gsSongSelection'>
                <ul class='$tabContainerClass'>
                    <li><a id='search-option' class='$tabClass' href='#'>Search</a></li>
                    <li><a id='favorites-option' class='$tabClass2' href='#'>Favorites</a></li>
                    <li><a id='playlists-option' class='$tabClass2' href='#'>Playlists</a></li>
                    <div class='clear' style='height:0'></div>
                </ul>
            <div class='clear' style='height:0'></div>";
    $userID = $gs_options['userID'];
    $token = $gs_options['token'];
    //Check if the user has registered (0 for no, 1 for yes and logged in)
    $userCheck = 0;
    if ((($userID != '') && ($userID != 0))) {
        $userCheck = 1;
    }
    // The keywords search div
    print "
	<div id='songs-search' class='$songClass' style='display: block;'>
            <div id='searchInputWrapper'>
                    <div id='searchInput'>
                        <input tabindex='100' id='gs-query' type='text' name='gs-query' value='Search For A Song' class='empty' />
                        <input type='hidden' name='gsLimit' value='{$gs_options['numberOfSongs']}' id='gsLimit' />
                    </div>
            </div>
            <div id='searchButton'>
                <input tabindex='101' type='button' name='editPage-1' id='gsSearchButton' value='Search' class='button gsMainButton'/>
            </div>
            <div class='clear' style='height:0;'></div>
	</div>
	<div class='clear' style='height:0'></div>
	<div id='search-results-container' class='$versionClass' style='display:none;'>
            <div id='search-results-header'>
                <h4 id='queryResult'></h4>
            </div>
            <div id='search-results-wrapper'>
                <table id='save-music-choice-search' style='display:none'></table>
            </div>
	</div>
	    ";
    // The favorites div (hidden by default)
    print "<div id='songs-favorites' class='$songClass' style='display: none;'>";
    if ($userID == 0) {
        // User most be logged in to access their favorites
        print "<p>Search for your favorite songs on Grooveshark. To use this feature, you must provide your Grooveshark login information in the Settings page, then refresh this page.</p>";
    } else {
        print "<table id='save-music-choice-favorites'>";
        $username = $gs_options['username'];
        $result = $gsapi->authenticateUser($username, $token);
        $songsArray = $gsapi->userGetFavoriteSongs(); // Gets the user's favorite songs
        if (isset($songsArray['error'])) {
            // There was a problem getting the user's favorite songs
            print "<tr><td colspan='3'>Error Code " . $songsArray['error'] . ". If this problem persists, you can e-mail roberto.sanchez@escapemg.com for support.";
        } else {
            // Get all favorite songs as rows in the table
            foreach ($songsArray as $id => $songInfo) {
                // Get necessary song information
                $songName = $songInfo['SongName'];
                $artistName = $songInfo['ArtistName'];
                $songID = $songInfo['SongID'];
                // Set a limit to how long song strings should be depending on WP versions (where boxes have smaller widths)
                // Should come up with a dynamic width system but this is good enough for most users
                $stringLimit = ($isVersion26 || $isVersion25) ? 71 : 78;
                // Sets up the name that is displayed in song list
                $songNameComplete = (strlen("$songName by $artistName") > $stringLimit) 
                                    ? substr($songName, 0, $stringLimit - 3 - strlen($artistName)) . "&hellip; by $artistName" 
                                    : "$songName by $artistName";
                // Replaces all single and double quotes with the html character entities
                $songNameComplete = preg_replace("/\'/", "&lsquo;", $songNameComplete, -1);
                $songNameComplete = preg_replace("/\"/", "&quot;", $songNameComplete, -1);
                $songName = preg_replace("/\'/", "&lsquo;", $songName, -1);
                $songName = preg_replace("/\"/", "&quot;", $songName, -1);
                $artistName = preg_replace("/\"/", "&quot;", $artistName, -1);
                $artistName = preg_replace("/\'/", "&lsquo;", $artistName, -1);
                // Sets up alternating row colors depending on WP version
                if ($id % 2) {
                    $rowClass = 'gsTr1';
                } else {
                    $rowClass = ($isVersion26 || $isVersion25) ? 'gsTr26' : 'gsTr27';
                }
                print "<tr class='$rowClass'>
                           <td class='gsTableButton'><a title='Add This Song To Your Post' class='gsAdd gsSong-$songID' name='$songNameComplete::$songID' style='cursor: pointer' id='gsSong-$songID'></a></td>
                           <td class='gsTableButton'><a title='Play This Song' class='gsPlay' name='$songID' style='cursor: pointer'></a></td>
                           <td>$songNameComplete</td>
                       </tr>";
            }
        }
        print "</table>";
    }
    print "</div>"; // End favorites div
    //The playlists div (hidden by default)
    // Get the user's saved playlists and display them here
    print "<div id='songs-playlists' class='$songClass' style='display: none;'>";
    if ($userID == 0) {
        // User must be logged in to access their playlists
        print "<p>Search for your playlists on Grooveshark. To use this feature, you must provide your Grooveshark login information in the Settings page, then refresh this page.</p>";
    } else {
        // NOTE: User should already be logged in from favorites div, so call to authenticateUser necessary
        $gs_options = gsUpdateUserPlaylists($gs_options, $gsapi);
        $userPlaylists = $gs_options['userPlaylists'];
        
        print "<table id='save-music-choice-playlists'>";

        if (!empty($userPlaylists)) {
            $colorId = 0;
            foreach ($userPlaylists as $playlistID => $playlistData) {
                // print a table row containing current playlist's data
                // Prepare style information
                if ($colorId % 2) {
                    $rowClass = 'gsTr1';
                } else {
                    $rowClass = ($isVersion26 || $isVersion25) ? 'gsTr26' : 'gsTr27';
                }
                $colorId++;
                // First, remove the entry in the array that does not correspond to a song
                $playlistInfo = $playlistData['playlistInfo'];
                unset($playlistData['playlistInfo']);
                // prepare the songs list
                $songString = array();
                foreach ($playlistData as $songID => $songData) {
                    $songNameComplete = $songData['songName'] . ' by ' . $songData['artistName'];
                    if (strlen($songNameComplete) > 78) {
                        // Cap string length at 78
                        $songNameComplete = substr($songData['songName'], 0, 75 - strlen($songData['artistName'])) . '&hellip; by ' . $songData['artistName'];
                    }
                    $songString[] = array('songID' => $songID, 'songName' => $songData['songName'], 'artistName' => $songData['artistName'], 'songNameComplete' => $songNameComplete);
                }
                $jsonSongString = gs_json_encode($songString);
                print "<tr class='$rowClass'>
                           <td class='gsTableButton'><a title='Add This Playlist To Your Post' class='gsAdd gsPlaylistAdd' name='$playlistID' style='cursor: pointer'><span style='display:none'>$jsonSongString</span></a></td>
                           <td class='gsTableButton'><a title='Show All Songs In This Playlist' class='gsShow' name='$playlistID' style='cursor: pointer'></a></td>
                           <td>{$playlistInfo['name']} ({$playlistInfo['numSongs']})</td>
                      </tr>";
                $alt = 0;
                foreach ($songString as $song) {
                    if ($alt % 2) {
                        $trClass = 'gsTr1';
                    } else {
                        $trClass = 'gsTr27';
                    }
                    $alt++;
                    print "<tr class='child-$playlistID playlistRevealedSong $trClass' style='display:none;'>
                                <td class='gsTableButton'>
                                    <a class='gsAdd gsSong-{$song['songID']}' style='cursor:pointer;' name='{$song['songNameComplete']}::{$song['songID']}' title='Add This Song To Your Post' id='gsSong-{$song['songID']}'></a>
                                </td>
                                <td class='gsTableButton'>
                                    <a class='gsPlay' style='cursor:pointer;' name='{$song['songID']}' title='Play This Song'></a>
                                </td>
                                <td>{$song['songNameComplete']}</td>
                            </tr>";
                }
            }
        } else {
            print "<tr><td>No playlists were found. When you create playlists on Grooveshark, they will show up here. If you do have playlists on Grooveshark, reload this page.</td></tr>";
        }
        print "</table>";
    }
    print "</div>"; // End playlist div
    //The selected songs div: dynamically updated with the songs the user wants to add to their post
    print "
    <div id='selected-song' class='$songClass'>
	<div id='selected-songs-header'>
		<a title='Remove All Your Selected Songs' href='#' id='clearSelected'>Clear All</a>
		<h4 id='selectedCount'>Selected Songs (0):</h4>
	</div>
	<table id='selected-songs-table'></table>
    </div>
    </div>"; // Ends selected songs div and the song selection (search, favorites, playlists, selected) div
    //The appearance div: customizes options for displaying the widget 
    $widgetWidth = (!isset($gs_options['widgetWidth'])) ? 250 : $gs_options['widgetWidth'];
    $widgetHeight = (!isset($gs_options['widgetHeight'])) ? 400 : $gs_options['widgetHeight'];
    $displayPhrase = ((!isset($gs_options['displayPhrase'])) || ($gs_options['displayPhrase'] == '')) ? 'Grooveshark' : $gs_options['displayPhrase'];
    print "
<a title='Toggle Showing Appearance Options' id='jsLink' href='#'>&darr; Appearance</a>
<div id='gsAppearance' class='gsAppearanceHidden'>
    <h2 id='gsAppearanceHead'>Customize the Appearance of Your Music</h2>
    <ul class='gsAppearanceOptions'>
        <li>
            <span class='key'>Display Music As:</span>
            <span class='value' id='gsAppearanceRadio'>
                <input tabindex='103' type='radio' name='displayChoice' value='link' />&nbsp; Link<br/>
                <input tabindex='103' type='radio' name='displayChoice' value='widget' checked />&nbsp; Widget
            </span>
        </li>
        <li>
            <span class='key'>Position Music At:</span>
            <span class='value'>
                <input id='gsPosition' tabindex='104' type='radio' name='positionChoice' value='beginning' />&nbsp; Beginning of Post<br/>
                <input tabindex='104' type='radio' name='positionChoice' value='end' checked />&nbsp; End of Post
            </span>
        </li>
    </ul>
    <ul id='gsDisplayLink' style='display:none;' class='gsAppearanceOptions'>
        <li>
            <span class='key'><label for='playlistsName'>Playlist Name:</label></span>
            <span class='value'>
                <input tabindex='105' type='text' name='playlistsName' id='playlistsName' value='Grooveshark Playlist' /><span id='displayPhrasePlaylistExample'>Example: \"$displayPhrase: Grooveshark Playlist\"</span>
            </span>
        </li>

        <li>
            <span class='key'><label for='displayPhrase'>Link Display Phrase:</label></span>
            <span class='value'>
                <input tabindex='106' type='text' name='displayPhrase' id='displayPhrase' value='$displayPhrase' /><span id='displayPhraseExample'>Example: \"$displayPhrase: song by artist\"</span>			
            </span>
        </li>
    </ul>
    <div id='gsDisplayWidget'>
        <ul class='gsAppearanceOptions'>
            <li>
                <span class='key'>Add to Dashboard:</span>
                <span class='value'>
                    <input tabindex='105' type='radio' name='dashboardChoice' value='yes' id='gsDashboardChoice' />&nbsp; Yes (will replace current Grooveshark Dashboard)<br />
                    <input tabindex='105' type='radio' name='dashboardChoice' value='no' checked />&nbsp; No
                </span>
            </li>
        </ul>
        <h2 id='singleAppearance'>Appearance of Single-Song Widgets</h2>
        <ul class='gsAppearanceOptions'>
            <li>
                <span class='key'><label for='widgetTheme'>Widget Theme:</label></span>
                <span class='value'>
                    <select tabindex='107' type='text' id='widgetTheme' name='widgetTheme'>
                        <option value='metal' selected='selected'>Metal</option>
                        <option value='wood'>Wood</option>
                        <option value='grass'>Grass</option>
                        <option value='water'>Water</option>
                    </select>
                    <span>See Preview Below</span>
                </span>
            </li>
            <li>
                <span class='key'><label for='singleWidgetWidth'>Widget Width:</label></span>
                <span class='value'>
                    <input tabindex='108' type='text' name='singleWidgetWidth' id='singleWidgetWidth' value='250'/><span>Range: 150px to 1000px</span>
                    <div class='clear'></div>
                    <br />
                    <div class='gsWidgetPreviewContainer' id='gsSingleWidgetPreview'>
                    </div>
                </span>
            </li>
        </ul>
        <h2 id='playlistAppearance'>Appearance of Multi-Song Widgets</h2>
        <ul class='gsAppearanceOptions'>
            <li>
                <span class='key'><label for='widgetWidth'>Widget Width:</label></span>
                <span class='value'>
                    <input tabindex='107' type='text' name='widgetWidth' id='widgetWidth' value='250'/><span>Range: 150px to 1000px</span>
                </span>
            </li>	
            <li>
                <span class='key'><label for='widgetHeight'>Widget Height:</label></span>
                <span class='value'>
                    <input tabindex='108' type='text' name='widgetHeight' id='widgetHeight' value='176'/><span>Range: 150px to 1000px</span>
                </span>
            </li>
            <li>
                <span class='key'><label>Color Scheme:</label></span>
                <span class='value'>
                    <select tabindex='109' type='text' id='colors-select' name='colorsSelect'>
    ";
    // Customize the color scheme of the widget
    $colorScheme = $gs_options['colorScheme']; //use this to save the user's colorscheme preferences
    $colorsArray = array("Default","Walking on the Sun","Neon Disaster","Golf Course","Creamcicle at the Beach Party","Toy Boat","Wine and Chocolate Covered Strawberries","Japanese Kite","Eggs and Catsup","Shark Bait","Sesame Street","Robot Food","Asian Haircut","Goth Girl","I Woke Up And My House Was Gone","Too Drive To Drunk","She Said She Was 18","Lemon Party","Hipster Sneakers","Blue Moon I Saw You Standing Alone","Monkey Trouble In Paradise");
    foreach ($colorsArray as $id => $colorOption) {
        print "<option value='$id' ";
        if ($i == $colorScheme) {
            print "selected ";
        }
        print ">$colorOption</option>";
    }
print "
                    </select>
                    <span>See Preview Below (Preview has 37-song limit)</span>
                    <div class='clear'></div>
                    <br/>
                    <div class='gsColorBlockContainer'>
                        Base
                        <div style='background-color: #777777' id='base-color' class='gsColorBlock'></div>
                    </div>
                    <div class='gsColorBlockContainer'>
                        Primary
                        <div style='background-color: rgb(255,255,255)' id='primary-color' class='gsColorBlock'></div>
                    </div>
                    <div class='gsColorBlockContainer'>
                        Secondary
                        <div style='background-color: rgb(102,102,102)' id='secondary-color' class='gsColorBlock'></div>
                    </div>
                </span>
            </li>
        </ul>
        <div class='clear'></div>
        <div id='gsWidgetExample'></div>
    </div>
    <div class='clear'></div>
</div>
";
//Closes the Grooveshark box div: gives two display options and the save button
print "
       <table id='gsSave'>
       <tr>
       <td>
       <input tabindex='110' type='button' class='button-primary button' value='Save Music' id='gs-save-post' name='save' onclick='gsAppendToContent(this)'/>
       <span id='gsCommentStatusMessage' style='display:none; background-color:#ffcccc; color:#001111; font-size:1.15em; margin-left:10px;'></span>
       </td>
       </tr>
       </table>
       <!--[if IE 7]>
       </div>
       <![endif]-->";
}

function oldGroovesharkBox() 
{
    print "<div class='dbx-b-ox-wrapper'>
         <fieldset id='groovesharkdiv' class='dbx-box'>
         <div class='dbx-h-andle-wrapper'>
         <h3 class='dbx-handle'>
         Add Music
         </h3>
         </div>
         <div class='dbx-c-ontent-wrapper'>
         <div class='dbx-content'>";
    groovesharkBox();
    print "</div>
           </div>
           </fieldset>
           </div>";
}

function gsUpdateUserPlaylists($gs_options, $gsapi) {
    // updates the saved user playlists
    $userPlaylists = $gs_options['userPlaylists'];
    $apiPlaylists = $gsapi->userGetPlaylists();
    foreach ($apiPlaylists as $apiPlaylistData) {
        $apiPlaylistID = $apiPlaylistData['PlaylistID'];
        $playlistName = $apiPlaylistData['Name'];
        $apiModifiedTime = $apiPlaylistData['TSModified'];
        $apiTimeData = date_parse($apiModifiedTime);
        $apiTimestamp = mktime($apiTimeData['hour'], $apiTimeData['minute'], $apiTimeData['second'], $apiTimeData['month'], $apiTimeData['day'], $apiTimeData['year']);
        $modifiedTime = $apiTimestamp;
        if (!empty($userPlaylists[$apiPlaylistID])) {
            // update modified time
            $modifiedTime = $userPlaylists[$apiPlaylistID]['playlistInfo']['modifiedTime'];
        }
        if ((empty($userPlaylists[$apiPlaylistID])) || ((int)$apiTimestamp > (int)$modifiedTime)) {
            // new playlist or modified playlist
            $userPlaylists[$apiPlaylistID] = array();
            $numberOfSongs = 0;
            $playlistSongs = $gsapi->playlistGetSongs($apiPlaylistID);
            if (empty($playlistSongs['error'])) {
                // Add the songs
                foreach ($playlistSongs as $song) {
                    if (!empty($song['SongID']) && !empty($song['SongName']) && !empty($song['ArtistName'])) {
                        $numberOfSongs++;
                        $userPlaylists[$apiPlaylistID][$song['SongID']] = array('songName' => $song['SongName'], 'artistName' => $song['ArtistName']);
                    }
                }
            }
            if (!empty($userPlaylists[$apiPlaylistID])) {
                $userPlaylists[$apiPlaylistID]['playlistInfo'] = array('name' => $playlistName, 'numSongs' => $numberOfSongs, 'modifiedTime' => $apiTimestamp);
            } else {
                unset($userPlaylists[$apiPlaylistID]);
            }
        }
    }
    $gs_options['userPlaylists'] = $userPlaylists;
    update_option('gs_options', $gs_options);
    return $gs_options;
}

function add_gs_options_page() 
{
    add_options_page('Grooveshark Options', 'Grooveshark', 'edit_dashboard', basename(__FILE__), 'grooveshark_options_page');
    //add_plugins_page('Grooveshark Options', 'Grooveshark', 'admin', 'Grooveshark', 'grooveshark_options_page');
}

// Registers the action to add the options page for the plugin.
add_action('admin_menu', 'add_gs_options_page');

// Code for Sidebar Widget
function groovesharkSidebarContent($args) {
    $gs_options = get_option('gs_options'); // Embed code is saved in the gs_options array
    if (!empty($gs_options['sidebarPlaylists'])) {
        print $args['before_widget'] . $args['before_title'] . 'Grooveshark Sidebar' . $args['after_title'] . $gs_options['sidebarPlaylists']['embed'] . $args['after_widget'];
    }
}

function groovesharkDashboardContent($args) {
    $gs_options = get_option('gs_options');
    if (!empty($gs_options['dashboardPlaylists'])) {
        print $gs_options['dashboardPlaylists']['embed'];
    }
}

function groovesharkRssContent($args) {
    $gs_options = get_option('gs_options');
    $wpurl = get_bloginfo('wpurl');
    if (!empty($gs_options['sidebarRss']) && ($gs_options['gsRssOption'] === 1)) {
        include('gsRss.php');
    }
}

// Widget code
function groovesharkSidebarInit() {
    $gs_options = get_option('gs_options');
    wp_register_sidebar_widget('groovesharkSidebar', 'Grooveshark Sidebar', 'groovesharkSidebarContent', array('description' => 'Add music to your Wordpress Sidebar using a Grooveshark Widget'));
    //register_widget_control('groovesharkSidebar', 'groovesharkSidebarOptions', 600);
    wp_register_widget_control('groovesharkSidebar', 'Grooveshark Sidebar', 'groovesharkSidebarOptions', array('width' => 600));
}

function groovesharkDashboardInit() {
    $gs_options = get_option('gs_options');
    if (!empty($gs_options['dashboardPlaylists'])) {
        if (function_exists('wp_add_dashboard_widget')) {
            wp_add_dashboard_widget('groovesharkDashboard', 'Grooveshark Dashboard', 'groovesharkDashboardContent');
        }
    }

}

function groovesharkRssInit() {
    $gs_options = get_option('gs_options');
    if ($gs_options['gsRssOption'] == 1) {
        wp_register_sidebar_widget('groovesharkRss', 'Grooveshark RSS', 'groovesharkRssContent', array('description' => 'Display an RSS feed to your favorite and recent songs on Grooveshark and link to them on your Wordpress Sidebar'));
        //register_widget_control('groovesharkRss', 'groovesharkRssOptions', 400);
        wp_register_widget_control('groovesharkRss', 'Grooveshark RSS', 'groovesharkRssOptions', array('width' => 400));
    }
}

function groovesharkRssOptions() {
    $gs_options = get_option('gs_options');
    $didSave = 0;
    print "<input type='hidden' id='groovesharkSidebarRssBox' value=''/>";
    if (isset($_POST['groovesharkRss-submit'])) {
        // Update the saved options
        $didSave = 1;
        if (isset($_POST['gsFavoritesFeed'])) {
            $gs_options['sidebarRss']['favorites']['title'] = ($_POST['gsFavoritesTitle'] != '') ? $_POST['gsFavoritesTitle'] : 'My Favorite Songs 2';
            $gs_options['sidebarRss']['favorites']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gs_options['username']) . "/recent_favorite_songs.rss";
        } else {
            $gs_options['sidebarRss']['favorites'] = array();
        }
        if (isset($_POST['gsRecentFeed'])) {
            $gs_options['sidebarRss']['recent']['title'] = ($_POST['gsRecentTitle'] != '') ? $_POST['gsRecentTitle'] : 'My Recent Songs';
            $gs_options['sidebarRss']['recent']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gs_options['username']) . "/recent_listens.rss";
        } else {
            $gs_options['sidebarRss']['recent'] = array();
        }
        $gs_options['sidebarRss']['count'] = $_POST['gsNumberOfItems'];
        $gs_options['sidebarRss']['displayContent'] = isset($_POST['gsDisplayContent']) ? 1 : 0;
        update_option('gs_options', $gs_options);
    }
    // Have the configuration options here
    print "<h3>Grooveshark RSS Widget</h3>";
    if ($gs_options['userID'] == 0) {
        print "<h4>You must save your login information to display your Grooveshark RSS feeds in the <a href='" . get_option('siteurl') . "/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a>, then refresh this page.</h4>";
    } else {
        if ($didSave) {
            print "<h4>Your RSS settings have been saved.</h4>";
        } else {
            print "<h4>Choose how you want your Grooveshark RSS feeds to appear on your sidebar</h4>";
        }
        print "<input name='groovesharkRss-submit' type='hidden' value='1' />
               <ul>
                   <li class='gsTr26'><label><input type='checkbox' name='gsFavoritesFeed' " . (empty($gs_options['sidebarRss']['favorites']) ? '' : " checked='checked'") . "/>&nbsp; Enable Favorites Feed?</label></li>
                   <li><label>Title for Favorites Feed: <input type='text' name='gsFavoritesTitle' value='" . (empty($gs_options['sidebarRss']['favorites']) ? '' : $gs_options['sidebarRss']['favorites']['title']) ."'/></label></li>
                   <li class='gsTr26'><label><input type='checkbox' name='gsRecentFeed' " . (empty($gs_options['sidebarRss']['recent']) ? '' : " checked='checked'") . "/>&nbsp; Enable Recent Songs Feed?</label></li>
                   <li><label>Title for Recent Songs Feed: <input type='text' name='gsRecentTitle' value='" . (empty($gs_options['sidebarRss']['recent']) ? '' : $gs_options['sidebarRss']['recent']['title']) . "'/></label></li>
                   <li class='gsTr26'><label>How many items would you like to display: <select name='gsNumberOfItems' type='text'>";
        for ($i = 0; $i <= 20; $i++) {
            print "<option value='$i' " . ($gs_options['sidebarRss']['count'] == $i ? "selected='selected'" : '') . ">$i</option>";
        }
        print "</select></label></li>
               <li><label><input type='checkbox' name='gsDisplayContent' " . ($gs_options['sidebarRss']['displayContent'] ? "checked='checked'" : '' ) . "/>&nbsp; Display Item Content?</label></li>
               </ul>";
    }
}

function gsGetColors($colorScheme) {
    switch ($colorScheme) {
        case 1:
            $color1 = 'CCA20C';
            $color2 = '4D221C'; 
            $color3 = 'CC7C0C';
            break;
        case 2:
            $color1 = '87FF00';
            $color2 = '0088FF'; 
            $color3 = 'FF0054'; 
            break;
        case 3:
            $color1 = 'FFED90';
            $color2 = '359668';
            $color3 = 'A8D46F';
            break;
        case 4:
            $color1 = 'F0E4CC';
            $color2 = 'F38630';
            $color3 = 'A7DBD8';
            break;
        case 5:
            $color1 = 'FFFFFF';
            $color2 = '377D9F';
            $color3 = 'F6D61F';
            break;
        case 6:
            $color1 = '450512';
            $color2 = 'D9183D';
            $color3 = '8A0721';
            break;
        case 7:
            $color1 = 'B4D5DA';
            $color2 = '813B45';
            $color3 = 'B1BABF';
            break;
        case 8:
            $color1 = 'E8DA5E';
            $color2 = 'FF4746';
            $color3 = 'FFFFFF';
            break;
        case 9:
            $color1 = '993937';
            $color2 = '5AA3A0';
            $color3 = 'B81207';
            break;
        case 10:
            $color1 = 'FFFFFF';
            $color2 = '009609';
            $color3 = 'E9FF24';
            break;
        case 11:
            $color1 = 'FFFFFF';
            $color2 = '7A7A7A';
            $color3 = 'D6D6D6';
            break;
        case 12:
            $color1 = 'FFFFFF';
            $color2 = 'D70860';
            $color3 = '9A9A9A';
            break;
        case 13:
            $color1 = '000000';
            $color2 = 'FFFFFF';
            $color3 = '620BB3';
            break;
        case 14:
            $color1 = '4B3120';
            $color2 = 'A6984D';
            $color3 = '716627';
            break;
        case 15:
            $color1 = 'F1CE09';
            $color2 = '000000';
            $color3 = 'FFFFFF';
            break;
        case 16:
            $color1 = 'FFBDBD';
            $color2 = 'DD1122';
            $color3 = 'FFA3A3';
            break;
        case 17:
            $color1 = 'E0DA4A';
            $color2 = 'FFFFFF';
            $color3 = 'F9FF34';
            break;
        case 18:
            $color1 = '579DD6';
            $color2 = 'CD231F';
            $color3 = '74BF43';
            break;
        case 19:
            $color1 = 'B2C2E6';
            $color2 = '012C5F';
            $color3 = 'FBF5D3';
            break;
        case 20:
            $color1 = '60362A';
            $color2 = 'E8C28E';
            $color3 = '482E24';
            break;
        default:
            $color1 = '000000';
            $color2 = 'FFFFFF';
            $color3 = '666666';
            break;
    }
    return array($color1, $color2, $color3);
}
    


function groovesharkSidebarOptions() {
    $gsapi = GSAPI::getInstance();
    $gs_options = get_option('gs_options');
    $didSave = 0;
    $wpurl = get_bloginfo('wpurl');
    $siteurl = get_option('siteurl'); // used to provide links to js/images

    print "<link type='text/css' rel='stylesheet' href='$wpurl/wp-content/plugins/grooveshark/css/grooveshark.css'></link>\n
           <script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.0/jquery.min.js'></script>\n
           <script type='text/javascript' src='$wpurl/wp-content/plugins/grooveshark/js/grooveshark.sidebar.js'></script>";
    
    print "<input type='hidden' id='groovesharkSidebarOptionsBox' value='' />
           <input type='hidden' id='gsBlogUrl' value='$siteurl' />
           <input type='hidden' id='gsSessionID' value='" . $gsapi->getSessionID() . "' />";
           
    if (isset($_POST['groovesharkWidget-submit'])) {
        // Update the saved options
        if (isset($_POST['gsClearSidebar']) || !isset($_POST['songsInfoArray'])) {
            $gs_options['sidebarPlaylists'] = array();
            $didSave = 2;
        } else {
            $playlistID = 0;
            if (count($_POST['songsInfoArray']) == 1) {
                $embedCode = $gsapi->songGetWidgetEmbedCode($_POST['songsInfoArray'][0], $_POST['sidebarWidgetWidth'], $_POST['widgetTheme']);
                // single song widget
            } else {
                $colorScheme = (int)$_POST['colorsSelect'];
                $colors = gsGetColors($colorScheme);
                $color1 = $colors[0];
                $color2 = $colors[1];
                $color3 = $colors[2];
                $embedCode = $gsapi->playlistGetWidgetEmbedCode($_POST['songsInfoArray'], $_POST['sidebarWidgetWidth'], $_POST['sidebarWidgetHeight'], 'Sidebar Widget', $color2, $color1, $color1, $color3, $color2, $color1, $color3, $color2, $color2, $color1, $color3, $color2, $color2, $color3, $color2);
            }
            $gs_options['sidebarPlaylists'] = array('id' => $playlistID, 'embed' => $embedCode);
            $didSave = 1;
        }
        update_option('gs_options', $gs_options);
    }
    print "<h3 class='groovesharkHeader'>Grooveshark Sidebar</h3>";
    if ($didSave == 1) {
        print "<p>Your music has been saved.</p>";
    } elseif ($didSave == 2) {
        print "<p>Your sidebar has been cleared.</p>";
    } else {
        print "<p>Add music to your Wordpress Sidebar</p>";
    }
    print "<input type='hidden' name='myHiddenData' id='myHiddenData' />
           <div id='apContainer'></div>";
    print "<div id='gsSongSelection'>
               <ul class='gsTabContainer27'>
                    <li><a id='search-option' class='gsTabActive26' onclick='gsToggleSongSelect(this)' href='javascript:;'>Search</a></li>
                    <li><a id='favorites-option' class='gsTabInactive27' onclick='gsToggleSongSelect(this)' href='javascript:;'>Favorites</a></li>
                    <li><a id='playlists-option' class='gsTabInactive27' onclick='gsToggleSongSelect(this)' href='javascript:;'>Playlists</a></li>
                    <div class='clear' style='height:0'></div>
                </ul>
	<div id='songs-search' class='gsSongBox26' style='display: block;'>
            <div id='searchInputWrapper'>
                    <div id='searchInput'>
                        <input tabindex='100' id='gs-query' type='text' name='gs-query' value='Search For A Song' class='empty' onfocus='if (this.className == \"empty\") {this.className = \"\"; this.value = \"\";}' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {gsEnterSearch(this); return false;} else {gsUpdateValue(this); return true;}'/>
                        <input type='hidden' name='gsLimit' value='{$gs_options['numberOfSongs']}' id='gsLimit' />
                    </div>
            </div>
            <div id='searchButton'>
                <input tabindex='101' type='button' name='editPage-1' id='gsSearchButton' value='Search' class='button gsMainButton' onclick='gsSearch(this)'/>
            </div>
            <div class='clear' style='height:0;'></div>
	</div>
	<div class='clear' style='height:0'></div>
	<div id='search-results-container' class='gs26' style='display:none;'>
            <div id='search-results-header'>
                <h4 id='queryResult'></h4>
            </div>
            <div id='search-results-wrapper'>
                <table id='save-music-choice-search' style='display:none'></table>
            </div>
	</div>
                ";
        // The favorites div (hidden by default)
    print "<div id='songs-favorites' class='gsSongBox26' style='display: none;'>";
    if (($gs_options['username'] . $gs_options['token']) == '') {
        // User most be logged in to access their favorites
        print "<p>Search your favorite songs on Grooveshark. To use this feature, you must provide your login information in the <a href='" . get_option('siteurl') . "/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a>, then refresh this page.</p>";

    } else {
        print "<table id='save-music-choice-favorites'>";
        $username = $gs_options['username'];
        $result = $gsapi->authenticateUser($username, $gs_options['token']);
        $songsArray = $gsapi->userGetFavoriteSongs(); // Gets the user's favorite songs
        if (isset($songsArray['error'])) {
            // There was a problem getting the user's favorite songs
            print "<tr><td colspan='3'>Error Code " . $songsArray['error'] . ". If this problem persists, you can e-mail roberto.sanchez@escapemg.com for support.";
        } else {
            // Get all favorite songs as rows in the table
            foreach ($songsArray as $id => $songInfo) {
                // Get necessary song information
                $songName = $songInfo['SongName'];
                $artistName = $songInfo['ArtistName'];
                $songID = $songInfo['SongID'];
                // Set a limit to how long song strings should be depending on WP versions (where boxes have smaller widths)
                // Should come up with a dynamic width system but this is good enough for most users
                // Sets up the name that is displayed in song list
                $songNameComplete = (strlen("$songName by $artistName") > 78) 
                                    ? substr($songName, 0, 75 - strlen($artistName)) . "&hellip; by $artistName" 
                                    : "$songName by $artistName";
                // Replaces all single and double quotes with the html character entities
                $songNameComplete = preg_replace("/\'/", "&lsquo;", $songNameComplete, -1);
                $songNameComplete = preg_replace("/\"/", "&quot;", $songNameComplete, -1);
                $songName = preg_replace("/\'/", "&lsquo;", $songName, -1);
                $songName = preg_replace("/\"/", "&quot;", $songName, -1);
                $artistName = preg_replace("/\"/", "&quot;", $artistName, -1);
                $artistName = preg_replace("/\'/", "&lsquo;", $artistName, -1);
                // Sets up alternating row colors depending on WP version
                if ($id % 2) {
                    $rowClass = 'gsTr1';
                } else {
                    $rowClass = 'gsTr26';
                }
                print "<tr class='$rowClass'>
                           <td class='gsTableButton'><a title='Add This Song To Your Post' class='gsAdd gsSong-$songID' onclick='addToSelected(this)' name='$songNameComplete::$songID' style='cursor: pointer' id='gsSong-$songID'></a></td>
                           <td class='gsTableButton'><a title='Play This Song' class='gsPlay' onclick='toggleSong(this)' name='$songID' style='cursor: pointer'></a></td>
                           <td>$songNameComplete</td>
                       </tr>";
            }
        }
        print "</table>";
    }
    print "</div>"; // End favorites div
    print "<div id='songs-playlists' class='gsSongBox26' style='display:none;'>";
    
    $playlistsTotal = 0;
    if (($gs_options['username'] . $gs_options['token']) !== '') {
        $gs_options = gsUpdateUserPlaylists($gs_options, $gsapi);
        $userPlaylists = $gs_options['userPlaylists'];
        // If the user has saved playlists
        $colorId = 0;
        print "<table id='save-music-choice-playlists'>";
        foreach ($userPlaylists as $playlistID => $playlistData) {
            // print a table row containing current playlist's data
            // Prepare style information
            if ($colorId % 2) {
                $rowClass = 'gsTr1';
            } else {
                $rowClass = 'gsTr26';
            }
            $colorId++;
            // First, remove the entry in the array that does not correspond to a song
            $playlistInfo = $playlistData['playlistInfo'];
            unset($playlistData['playlistInfo']);
            // prepare the songs list
            $songString = array();
            foreach ($playlistData as $songID => $songData) {
                $songNameComplete = $songData['songName'] . ' by ' . $songData['artistName'];
                if (strlen($songNameComplete) > 78) {
                    // Cap string length at 78
                    $songNameComplete = substr($songData['songName'], 0, 75 - strlen($songData['artistName'])) . '&hellip; by ' . $songData['artistName'];
                }
                $songString[] = array('songID' => $songID, 'songName' => $songData['songName'], 'artistName' => $songData['artistName'], 'songNameComplete' => $songNameComplete);
            }
            $jsonSongString = gs_json_encode($songString);
            // Inline events used, since event delegation refuses to work
            print "<tr class='$rowClass'>
                       <td class='gsTableButton'><a title='Add This Playlist To Your Post' class='gsAdd gsPlaylistAdd' onclick='addToSelectedPlaylist(this);' name='$playlistID' style='cursor: pointer'><span style='display:none'>$jsonSongString</span></a></td>
                       <td class='gsTableButton'><a title='Show All Songs In This Playlist' class='gsShow' name='$playlistID' onclick='showPlaylistSongs(this);' style='cursor: pointer'></a></td>
                       <td>{$playlistInfo['name']} ({$playlistInfo['numSongs']})</td>
                  </tr>";
            $alt = 0;
            foreach ($songString as $song) {
                if ($alt % 2) {
                    $trClass = 'gsTr1';
                } else {
                    $trClass = 'gsTr26';
                }
                $alt++;
                print "<tr class='child-$playlistID playlistRevealedSong $trClass' style='display:none;'>
                            <td class='gsTableButton'>
                                <a class='gsAdd gsSong-{$song['songID']}' onclick='addToSelected(this)' style='cursor:pointer;' name='{$song['songNameComplete']}::{$song['songID']}' title='Add This Song To Your Post' id='gsSong-{$song['songID']}'></a>
                            </td>
                            <td class='gsTableButton'>
                                <a class='gsPlay' style='cursor:pointer;' name='{$song['songID']}' title='Play This Song' onclick='toggleSong(this)'></a>
                            </td>
                            <td>{$song['songNameComplete']}</td>
                        </tr>";
            }
        }
        print "</table>";
    } else {
        // No playlists, notify user on how to save playlists
        print "<p>Search your playlists on Grooveshark. To use this feature, you must provide your login information in the <a href='" . get_option('siteurl') . "/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a>, then refresh this page.</p>";
    }
    print "</div>";

    print "<div id='selected-song' class='gsSongBox26'>
        <div id='selected-songs-header'>
                <a title='Remove All Your Selected Songs' href='javascript:;' id='clearSelected' onclick='clearSelected(this)'>Clear All</a>
                <h4 id='selectedCount'>Selected Songs (0):</h4>
        </div>
        <table id='selected-songs-table'></table>
    </div>
    </div>
    <input name='groovesharkWidget-submit' type='hidden' value='1' />
    <h3 class='groovesharkHeader'>Appearance Options</h3>
    <input type='hidden' id='sidebarDataStore' value='-1'>
    <ul class='gsAppearanceOptions'>
    <li><span class='key'><label for='gsClearSidebar'>Clear Sidebar:</label></span><span class='value'><input type='checkbox' name='gsClearSidebar' id='gsClearSidebar' /><span style='font-size:12px'>Check this box and Save to clear the Grooveshark Sidebar.</span></span></li>
    <li><span class='key'><label for='sidebarWidgetWidth'>Widget Width (px):</label></span><span class='value'><input tabindex='900' type='text' name='sidebarWidgetWidth' id='gsSidebarWidgetWidth' value='200' onchange='gsUpdateSidebarWidth(this)'/></span></li>
    <li><span class='key'><label for='sidebarWidgetHeight'>Widget Height (px):</label></span><span class='value'><input tabindex='901' type='text' name='sidebarWidgetHeight' id='gsSidebarWidgetHeight' value='176' onchange='gsUpdateSidebarHeight(this)'/><span>For Multiple Songs</span></span></li>
    <li>
        <span class='key'><label for='widgetTheme'>Widget Theme:</label></span>
        <span class='value'>
            <select tabindex='902' type='text' id='widgetTheme' name='widgetTheme' onchange='changeSidebarTheme(this.form.widgetTheme)' onkeyup='changeSidebarTheme(this.form.widgetTheme)'>
                <option value='metal' selected='selected'>Metal</option>
                <option value='wood'>Wood</option>
                <option value='grass'>Grass</option>
                <option value='water'>Water</option>
            </select>
            <span>For Single Songs</span>
            <br />
            <div class='gsWidgetPreviewContainer' id='gsSingleWidgetPreview'>
            <object width='200' height='40'>
                <param value='http://listen.grooveshark.com/songWidget.swf' name='movie'></param>
                <param value='window' name='wmode'></param>
                <param value='always' name='allowScriptAccess'></param>
                <param value='hostname=cowbell.grooveshark.com&amp;songID=203993&amp;style=metal' name='flashvars'></param>
                <embed width='200' height='40' wmode='window' allowscriptaccess='always' flashvars='hostname=cowbell.grooveshark.com&amp;songID=203993&amp;style=metal' type='application/x-shockwave-flash' src='http://listen.grooveshark.com/songWidget.swf'></embed>
            </object>
            </div>
        </span>
    </li>
    <li>
        <span class='key'><label for='colorsSelect'>Color Scheme:</label></span>
        <span class='value'>
            <select tabindex='903' type='text' onchange='changeSidebarColor(this.form.colorsSelect)' onkeyup='changeSidebarColor(this.form.colorsSelect)' name='colorsSelect' id='colors-select'>";
    // Customize the color scheme of the widget
    $colorsArray = array("Default","Walking on the Sun","Neon Disaster","Golf Course","Creamcicle at the Beach Party","Toy Boat","Wine and Chocolate Covered Strawberries","Japanese Kite","Eggs and Catsup","Shark Bait","Sesame Street","Robot Food","Asian Haircut","Goth Girl","I Woke Up And My House Was Gone","Too Drive To Drunk","She Said She Was 18","Lemon Party","Hipster Sneakers","Blue Moon I Saw You Standing Alone","Monkey Trouble In Paradise");
    foreach ($colorsArray as $id => $colorOption) {
        print "<option value='$id'>$colorOption</option>";
    }
    print "</select>
           <span>For Multiple Songs</span>
           <div class='clear'></div>
           <br />
           
           <div class='gsColorBlockContainer'>
               Base
               <div style='background-color: #777777' id='widget-base-color' class='gsColorBlock'></div>
           </div>
           <div class='gsColorBlockContainer'>
               Primary
               <div style='background-color: #FFFFFF' id='widget-primary-color' class='gsColorBlock'></div>
           </div>
           <div class='gsColorBlockContainer'>
               Secondary
               <div style='background-color: rgb(102, 102, 102)' id='widget-secondary-color' class='gsColorBlock'></div>
            </div>
            <br />
            <div class='gsWidgetPreviewContainer' id='gsWidgetExample' style='margin:90px 0 0 0 !important;'>
            <object width='200' height='176'>
                <param value='http://listen.grooveshark.com/widget.swf' name='movie'></param>
                <param value='window' name='wmode'></param>
                <param value='always' name='allowScriptAccess'></param>
                <param value='hostname=cowbell.grooveshark.com&amp;songIDs=13963,23419725,21526481,203993&amp;bt=FFFFFF&amp;bth=000000&amp;bbg=000000&amp;bfg=666666&amp;pbg=FFFFFF&amp;pfg=000000&amp;pbgh=666666&amp;pfgh=FFFFFF&amp;lbg=FFFFFF&amp;lfg=000000&amp;lbgh=666666&amp;lfgh=FFFFFF&amp;sb=FFFFFF&amp;sbh=666666&amp;si=FFFFFF' name='flashvars'></param>
                <embed width='200' height='176' wmode='window' allowscriptaccess='always' flashvars='hostname=cowbell.grooveshark.com&amp;songIDs=13963,23419725,21526481,203993&amp;bt=FFFFFF&amp;bth=000000&amp;bbg=000000&amp;bfg=666666&amp;pbg=FFFFFF&amp;pfg=000000&amp;pbgh=666666&amp;pfgh=FFFFFF&amp;lbg=FFFFFF&amp;lfg=000000&amp;lbgh=666666&amp;lfgh=FFFFFF&amp;sb=FFFFFF&amp;sbh=666666&amp;si=FFFFFF' type='application/x-shockwave-flash' src='http://listen.grooveshark.com/widget.swf'></embed>
            </object>
            </div>
            </span>
            </li></ul>
            <div style='clear:both'></div>";
    if ($didSave == 1) {
        print "<p>Your music has been saved.</p>";
    } elseif ($didSave == 2) {
        print "<p>Your sidebar has been cleared.</p>";
    }
}

add_action('plugins_loaded', 'groovesharkSidebarInit');
add_action('plugins_loaded', 'groovesharkRssInit');
add_action('wp_dashboard_setup', 'groovesharkDashboardInit');


/* 
//Comment Related code
// Registers the filter to add music to the comment, and the action to show the search box
// Remind users that to enable this option, their template must display comment_form. Also, for modification in the comments.php file or in themes:
// Add <?php do_action(’comment_form’, $post->ID); ?> just above </form> ending tag in comments.php. Save the file.
*/
add_action('comment_form','groovesharkCommentBox');
add_filter('preprocess_comment','gs_appendToComment'); 

function groovesharkCommentBox() {
    $gs_options = get_option('gs_options');
    if ($gs_options['musicComments'] == 1) {
        $wpurl = get_bloginfo('wpurl');
        print "<link type='text/css' rel='stylesheet' href='$wpurl/wp-content/plugins/grooveshark/css/grooveshark.comment.css'></link>\n
               <script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.0/jquery.min.js'></script>\n
               <script type='text/javascript' src='$wpurl/wp-content/plugins/grooveshark/js/grooveshark.js'></script>";
        groovesharkSmallBox();
    }
}

function groovesharkSmallBox() {
    // Retrieves saved options for the plugin
    $gs_options = get_option('gs_options');
    // Get a GSAPI object for API calls
    $gsapi = GSAPI::getInstance();
    $sessionID = $gsapi->getSessionID();
    $siteurl = get_option('siteurl'); // used to provide links to js/images
    $version = get_bloginfo('version'); // used to load fixes specific to certain WP versions
    $isVersion26 = stripos($version, '2.6') !== false;
    $isVersion25 = stripos($version, '2.5') !== false;
    $tabClass = 'gsTabActive27';
    $tabClass2 = 'gsTabInactive27';
    $tabContainerClass = 'gsTabContainer27';
    $songClass = 'gsSongBox27';
    $versionClass = 'gs27';
    if ($isVersion26 || $isVersion25) {
        $tabContainerClass = 'gsTabContainer26';
        $tabClass = 'gsTabActive26';
        $tabClass2 = 'gsTabInactive26';
        $songClass = 'gsSongBox26';
        $versionClass = 'gs26';
    }
    $commentSongLimit = $gs_options['commentSongLimit'];
    $limitMessage = '';
    $displayOption = $gs_options['commentDisplayOption'];
    if (($commentSongLimit != 0) && ($displayOption == 'widget')) {
        $limitMessage = "Allowed A Maximum Of $commentSongLimit";
    }
// Hidden variables for the javascript
print "<input type='hidden' name='sessionID' value='$sessionID' />
       <input type='hidden' id='isSmallBox' name='isSmallBox' value='1' />
       <input type='hidden' name='widgetHeight' id='widgetHeight' value='176' />
       <input type='hidden' name='gsSessionID' value='$sessionID' id='gsSessionID' />
       <input type='hidden' name='gsLimit' value='{$gs_options['numberOfSongs']}' id='gsLimit' />
       <input type='hidden' name='gsBlogUrl' value='$siteurl' id='gsBlogUrl' />
       <input type='hidden' name='wpVersion' value='$version' id='wpVersion' />
       <input type='hidden' id='gsDataStore'/>
       <input type='hidden' id='gsCommentDisplayOption' value='$displayOption' />
       <input type='hidden' id='gsCommentSongLimit' value='$commentSongLimit' />
       <input type='hidden' id='gsCommentWidth' value='{$gs_options['commentWidgetWidth']}' />
       <input type='hidden' id='gsCommentHeight' value='{$gs_options['commentWidgetHeight']}' />
       <input type='hidden' id='gsCommentPlaylistName' value='{$gs_options['commentPlaylistName']}' />
       <input type='hidden' id='gsCommentColorScheme' value='{$gs_options['commentColorScheme']}' />
       <input type='hidden' id='gsCommentDisplayPhrase' value='{$gs_options['commentDisplayPhrase']}' />
       <input type='hidden' name='songIDs' id='songIDs' value='0' />
       <div id='apContainer'></div>";
// Code to display the comment box
    // The basic code to display the postbox. The ending tags for div are at the end of the groovesharkBox() function
    print "
<div id='gsCommentBox'>
    <!--[if IE 7]>
    <div id='IE7'>
    <![endif]-->
    <h3>Add Music To Your Comment</h3>

    <div id='gsSongSelection'>
        <div id='songs-search' class='$songClass' style='display: block;'>
            <div id='searchInputWrapper'>
                <div id='searchInput'>
                    <input tabindex='100' id='gs-query' type='text' name='gs-query' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {document.getElementById(\"gsSearchButton\").click(); return false;} else return true;' value='Search For A Song' class='empty' />

                </div>
            </div>
            <div id='searchButton'>
                <input tabindex='101' type='button' name='editPage' id='gsSearchButton' value='Search' class='button gsSmallButton' onclick=\"gsSearch(this)\" />
            </div>
            <div class='clear' style='height:0;' /></div>
        </div>
        <div class='clear' style='height:0'></div>
        <div id='search-results-container' class='$versionClass' style='display:none;'>
            <div id='search-results-header'>
                <h4 id='queryResult'></h4>
            </div>
            <div id='search-results-wrapper'>
                <table id='save-music-choice-search' style='display:none'></table>
            </div>
        </div>

        <div id='selected-song' class='$songClass'>
            <div id='selected-songs-header'>
                <a href=\"javascript:;\" onmousedown=\"clearSelected();\" id='clearSelected'>Clear All</a>
                <h4 id='selectedCount'>Selected Songs (0): $limitMessage</h4>
            </div>
            <table id='selected-songs-table'></table>
        </div>
    </div>
    <table id='gsSave'>
        <tr>
            <td>
                <input tabindex='110' type='button' class='button-primary button gsAppendToComment' value='Save Music' title='Append Music To Your Comment' id='save-post' name='save' onclick='gsAppendToComment(this)'/>
                <span id='gsCommentStatusMessage' style='display:none; background-color:#ffcccc; color:#001111; font-size:1.15em; margin-left:10px;'></span>
            </td>
        </tr>
    </table>
    <!--[if IE 7]>
    </div>
    <![endif]-->
</div>
";
}

function gs_appendToComment($data) {
    $gsContent = '';
    //Processing Code, does nothing now, but may do something in the future
    $data['comment_content'] .= $gsContent;
    return $data;
}

function postHasValue($key, $value) {
    if (!isset($_POST[$key])) {
        return false;
    }
    return $_POST[$key] == $value;
}

// The function to display the options page.
function grooveshark_options_page() {
    $gsapi = GSAPI::getInstance();
    $sessionID = $gsapi->getSessionID();
    $errorCodes = array();
    $gs_options = get_option('gs_options');
    $settingsSaved = 0;
    // If the user wants to update the options...
    if (((postHasValue('status', 'update')) || (postHasValue('Submit', 'Enter')))) {
        $updateOptions = array();
        /* If a username and password was entered, checks to see if they are valid via the 
        session.loginViaAuthToken method. If they are valid, the userID and token are retrieved and saved. */
        if ((isset($_POST['gs-username']) && $_POST['gs-username'] != '') && (isset($_POST['gs-password']) && $_POST['gs-password'] != '')) {
            $userID = 0;
            $username = $_POST['gs-username'];
            $password = $_POST['gs-password'];
            $token = md5($username . md5($password));
            $result = $gsapi->authenticateUser($username, $token);
            if ($result === true) {
                $userID = $gsapi->getUserID();
            } else {
                $errorCodes[] = 'Could not authenticate login information';
                $token = '';
                $username = '';
            }
            $updateOptions += array('userID' => $userID, 'token' => $token, 'username' => $username);
        }
        // Sets the number of songs the user wants to search for. If no number was enter, it just saved the default (30).
        $numberOfSongs = 30;
        if (isset($_POST['numberOfSongs'])) {
            $numberOfSongs = $_POST['numberOfSongs'];
        }
        $updateOptions += array('numberOfSongs' => $numberOfSongs);
        // Sets the display option for comment music
        if (isset($_POST['commentDisplayOption'])) {
            $updateOptions += array('commentDisplayOption' => $_POST['commentDisplayOption']);
        }
        // Set the widget width for comment widgets
        if (isset($_POST['commentWidgetWidth'])) {
            $commentWidgetWidth = $_POST['commentWidgetWidth'];
            if ($commentWidgetWidth < 150) {
                $commentWidgetWidth = 150;
            }
            if ($commentWidgetWidth > 1000) {
                $commentWidgetWidth = 1000;
            }
            $updateOptions += array('commentWidgetWidth' => $commentWidgetWidth);
        }
        if (isset($_POST['commentWidgetHeight'])) {
            $commentWidgetHeight = $_POST['commentWidgetHeight'];
            if (($commentWidgetHeight < 150) && ($commentWidgetHeight != 0)) {
                $commentWidgetHeight = 150;
            }
            if ($commentWidgetHeight > 1000) {
                $commentWidgetHeight = 1000;
            }
            $updateOptions += array('commentWidgetHeight' => $commentWidgetHeight);
        }
        if (isset($_POST['commentSongLimit'])) {
            $updateOptions += array('commentSongLimit' => $_POST['commentSongLimit']);
        }
        if (isset($_POST['commentDisplayPhrase'])) {
            $updateOptions += array('commentDisplayPhrase' => $_POST['commentDisplayPhrase']);
        }
        if (isset($_POST['commentPlaylistName'])) {
            $updateOptions += array('commentPlaylistName' => $_POST['commentPlaylistName']);
        }
        if (isset($_POST['commentColorScheme'])) {
            $commentColorScheme = $_POST['commentColorScheme'];
            if ($commentColorScheme < 0) {
                $commentColorScheme = 0;
            }
            if ($commentColorScheme > 22) {
                $commentColorScheme = 22;
            }
            $updateOptions += array('commentColorScheme' => $commentColorScheme);
        }
        $gs_options = array_merge($gs_options,$updateOptions);
        // Updates the options and lets the user know the settings were saved.
        update_option('gs_options',$gs_options);
        $settingsSaved = 1;
    }
    if (!postHasValue('Submit', 'Enter')) {
        print "<div class='updated'>";
        if (postHasValue('Status', 'Reset')) {
            // user wants to reset login information, destroy saved token and set userID, username, and user playlists to empty
            $updateArray = array('userID' => 0, 'token' => '', 'username' => '', 'userPlaylists' => array());
            $gs_options = array_merge($gs_options, $updateArray);
            update_option('gs_options', $gs_options);
            print "<p>Login information has been reset.</p>";
        }
        if (postHasValue('gsRssOption', 'Enable')) {
            // user wants to enable Grooveshark RSS
            $gs_options['gsRssOption'] = 1;
            print "<p>Grooveshark RSS Enabled</p>";
        } elseif (postHasValue('gsRssOption', 'Disable')) {
            // user wants to disable Grooveshark RSS
            $gs_options['gsRssOption'] = 0;
            print "<p>Grooveshark RSS Disabled</p>";
        }
        if (postHasValue('sidebarOptions', 'Clear')) {
            $gs_options['sidebarPlaylists'] = array();
            print "<p>Grooveshark Sidebar Cleared</p>";
        }
        if (postHasValue('dashboardOptions', 'Clear')) {
            $gs_options['dashboardPlaylists'] = array();
            print "<p>Grooveshark Dashboard Cleared</p>";
        }
        if (postHasValue('musicComments', 'Enable')) {
            $gs_options['musicComments'] = 1;
            print "<p>Music Comments Enabled</p>";
        } elseif (postHasValue('musicComments', 'Disable')) {
            $gs_options['musicComments'] = 0;
            print "<p>Music Comments Disabled</p>";
        }
        if ($settingsSaved) {
            print "<p>Settings Saved</p>";
        }
        
        print "</div>";
        update_option('gs_options',$gs_options);
    }
    // Prints all the inputs for the options page. Here, the login information, login reset option, search option, and number of songs can be set.
    print "
    <form method=\"post\" action=\"\">
        <div class=\"wrap\">
            <h2>Grooveshark Plugin Options</h2>
            <input type=\"hidden\" name=\"status\" value=\"update\">
            <input type='hidden' name='sessionID' value='$sessionID'>
            <fieldset>";
    print "<table class='form-table'>";
    if (count($errorCodes) > 0) {
        foreach($errorCodes as $code) {
            print "<tr><td colspan='2'><b>Error Code $code. If this problem persists, you can e-mail roberto.sanchez@escapemg.com for support.</b></td></tr>";
        }
    }
    $userID = $gs_options['userID'];
    /* If the login failed, the user is notified. If no login information was saved, 
    then the user is reminded that they can enter their Grooveshark login information. */
    if ($userID == 0) {
        if ((($userID == 0) && ((isset($username) && $username != '') && (isset($password) && $password != '')))) {
            print "<tr><td colspan='2'><b>There was an error with your login information. Please try again.</b></td></tr>";
        } else {
            print "<tr><td colspan='2'>If you have a <a target='_blank' href='http://www.grooveshark.com'>Grooveshark</a> account, you can input your username and password information to access songs from your favorites list.</td></tr>";
        }
        // Displays the form to enter the login information.
        print "<tr><th><label for='gs-username'>Username: </label></th> <td><input type=\"text\" name=\"gs-username\" id='gs-username'> </td></tr>
           <tr><th><label for='gs-password'>Password: </label></th> <td><input type=\"password\" name=\"gs-password\" id='gs-password'></td></tr>";
    } else {
        // Displays the form to reset the login information. Also displays an option to allow the user to choose whether
        // plugin-created playlists are attached to their Grooveshark account.
        print "<tr align=\"top\">
           <th scope=\"row\"><label for='resetSong'>Reset your login information:</label></th>
           <td class='submit'><input type='submit' name='Submit' value='Enter' style='display: none;' /><input type='submit' name='Status' id='resetSong' value='Reset' />&nbsp; Your login information has been saved. Click this button to reset your login information.</td></tr>";
    }
    $sidebarOption = $gs_options['sidebarPlaylists'];
    if (!empty($sidebarOption)) {
        // Display option to clear sidebar
        print "<tr align='top'>
               <th scope='row'><label for='sidebarOptions'><input type='submit' name='Submit' value='Enter' style='display: none;' />
               Clear Sidebar:
               </label></th>
               <td class='submit'><input type='submit' name='sidebarOptions' id='sidebarOptions' value='Clear' />&nbsp; Click this button to clear the Grooveshark Sidebar Widget.</td>";
    }
    $dashboardOption = $gs_options['dashboardPlaylists'];
    if (!empty($dashboardOption)) {
        // Display option to clear dashboard
        print "<tr align='top'>
               <th scope='row'><label for='dashboardOptions'><input type='submit' name='Submit' value='Enter' style='display: none;' />
               Clear Dashboard:
               </label></th>
               <td class='submit'><input type='submit' name='dashboardOptions' id='dashboardOptions' value='Clear' />&nbsp; Click this button to clear the Grooveshark Dashboard Widget.</td>";
    }
    $musicComments = $gs_options['musicComments'];
    print "<tr align='top'>
           <th scope='row'><label for='musicComments'><input type='submit' name='Submit' value='Enter' style='display: none;' />
           Allow Music Comments:
           </label></th>";
    if ($musicComments) {
        print "<td class='submit'><input type='submit' name='musicComments' id='musicComments' value='Disable' />&nbsp; Click this button to disable music in readers' comments.</td>";
    } else {
        print "<td class='submit'><input type='submit' name='musicComments' id='musicComments' value='Enable' />&nbsp; Click this button to allow your blog readers to add music to their comments.</td>";
    }
    print "</tr>";
    if ($musicComments) {
        $commentDisplayOption = $gs_options['commentDisplayOption'];
        $commentWidget = '';
        $commentLink = '';
        if ($commentDisplayOption == 'widget') {
            $commentWidget = 'checked';
        } else {
            $commentLink = 'checked';
        }
        $commentWidgetWidth = $gs_options['commentWidgetWidth'];
        $commentWidgetHeight = $gs_options['commentWidgetHeight'];
        $commentSongLimit = $gs_options['commentSongLimit'];
        $commentDisplayPhrase = $gs_options['commentDisplayPhrase'];
        $commentPlaylistName = $gs_options['commentPlaylistName'];
        $commentColorScheme = $gs_options['commentColorScheme'];
        $colorsArray = array("Default","Walking on the Sun","Neon Disaster","Golf Course","Creamcicle at the Beach Party","Toy Boat","Wine and Chocolate Covered Strawberries","Japanese Kite","Eggs and Catsup","Shark Bait","Sesame Street","Robot Food","Asian Haircut","Goth Girl","I Woke Up And My House Was Gone","Too Drive To Drunk","She Said She Was 18","Lemon Party","Hipster Sneakers","Blue Moon I Saw You Standing Alone","Monkey Trouble In Paradise");
        print "<tr align='top'><th scope='row'><label for='commentDisplayOption'>Display Comment Music As:</label></th>
                   <td><label>Widget &nbsp;<input type='radio' name='commentDisplayOption' value='widget' $commentWidget />&nbsp;</label><label> Link &nbsp;<input type='radio' name='commentDisplayOption' value='link' $commentLink /></label> &nbsp; Specify whether you want music in comments to be displayed as a link to Grooveshark or as a widget.</td>
               </tr>";

        if ($commentDisplayOption == 'widget') {
               print "<tr align='top'><th scope='row'><label for='commentWidgetWidth'>Width for Comment Widgets:</label></th>
                   <td><input type='text' name='commentWidgetWidth' value='$commentWidgetWidth' id='commentWidgetWidth'>&nbsp; Specify the width in pixels of widgets embeded in user comments.</td>
               </tr> 
               <tr align='top'><th scope='row'><label for='commentWidgetHeight'>Height for Comment Widgets:</label></th>
                   <td><input type='text' name='commentWidgetHeight' value='$commentWidgetHeight' id='commentWidgetHeight'>&nbsp; Specify the height in pixels of widgets embeded in user comments <b>(set to 0 for auto-adjustment)</b>.</td>
               </tr>
              <tr align='top'><th scope='row'><label for='commentSongLimit'>Comment Song Limit:</label></th>
                   <td><input type='text' name='commentSongLimit' value='$commentSongLimit' id='commentSongLimit'>&nbsp; Specify a limit on how many songs a user may embed as a widget in comments <b>(set to 0 for no limit)</b>.</td>
               </tr>
               <tr align='top'><th scope='row'><label for='commentColorScheme'>Color Scheme for Comment Widgets:</label></th>
                   <td><select type='text' id='commentColorScheme' name='commentColorScheme'>";
                   foreach ($colorsArray as $id => $colorOption) {
                       print "<option value='$id' ";
                       if ($id == $commentColorScheme) {
                           print "selected='selected' ";
                       }
                       print ">$colorOption</option>";
                   }
                   print "&nbsp; Specify the color scheme of widgets embeded in user comments.</td>
                </tr>";
        } else {
            print "<tr align='top'><th scope='row'><label for='commentDisplayPhrase'>Display Phrase for Comment Music Links:</label></th>
                   <td><input type='text' name='commentDisplayPhrase' value='$commentDisplayPhrase' id='commentDisplayPhrase'>&nbsp; Used in song links. Example: <b>$commentDisplayPhrase</b>: $commentPlaylistName</td>
               </tr>
               <tr align='top'><th scope='row'><label for='commentPlaylistName'>Comment Playlist Names:</label></th>
                   <td><input type='text' name='commentPlaylistName' value='$commentPlaylistName' id='commentPlaylistName'>&nbsp; Used in songs links. Example: $commentDisplayPhrase: <b>$commentPlaylistName</b></td>
               </tr>";
        }

    }
    // Finished displaying the form for search options, and displays the form to enter how many songs to search for.
    print "<tr align=\"top\"> <th scope=\"row\"><label for='numberOfSongs'>Number of Results:</label></th>";
    $numberOfSongs = $gs_options['numberOfSongs'];
    print "<td><input type=\"text\" name=\"numberOfSongs\" value=\"$numberOfSongs\" id='numberOfSongs'>&nbsp; Specify how many songs or playlists you want the
           search to return.<input type='submit' name='Submit' value='Enter' style='display: none;' /></td></tr>";
    /*
    // Finished displaying the form for number of results, and displays the form for javascript insertion
    print "<tr align='top'><th scope='row'><label for='javascriptInsertLocation'>Insert Javascript in:</label></th>";
    $javascriptPos = $gs_options['javascriptPos'];
    $jsHead = $javascriptPos == 'head' ? 'checked' : '';
    $jsFoot = $javascriptPos == 'foot' ? 'checked' : '';
    print "<td><label>Header &nbsp;<input type='radio' name='javascriptPosOption' value='head' $jsHead />&nbsp;</label><label> Footer &nbsp;<input type='radio' name='javascriptPosOption' value='foot' $jsFoot /></label> &nbsp; Specify where you want the plugin to insert plugin javascript. Select Footer for faster load time.</td></tr>";
    */
    // Finished displaying the form for javascript insertion, and displays the form for rss
    print "<tr align='top'><th scope='row'><label for='gsRssOption'>Enable Grooveshark RSS:</label></th>";
    $gsRssOption = $gs_options['gsRssOption'];
    if ($gsRssOption) {
        print "<td class='submit'><input type='submit' name='gsRssOption' id='gsRssOption' value='Disable' /> &nbsp; Click this button to disable the Grooveshark RSS feature.</td>";
    } else {
        print "<td class='submit'><input type='submit' name='gsRssOption' id='gsRssOption' value='Enable' /> &nbsp; Grooveshark RSS is disabled by default due to incompatibilities with certain wordpress installations. Click to enable.</td>";
    }
    print "</tr>";
    // Finished displaying all forms, and then ends.
    print "</table><p class='submit'><input type=\"submit\" name=\"Submit\" value=\"Update Options\"></p>
          </div>
          </form>";
}
?>
