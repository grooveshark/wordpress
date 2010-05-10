<?php
/**
 * GSAPI - Interface to the Grooveshark API used in the Wordpress plugin
 * 
 * PHP Version 5
 *
 * @author Roberto Sanchez <roberto.sanchez@escapemg.com>
 */

// These functions added for php versions that don't include json functions
function gs_json_decode($content, $arg) {
    if (!extension_loaded('json')) {
        if (!class_exists('GS_Services_JSON')) {
            require_once 'GSJSON.php';
        }
        $json = new GS_Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        return $json->decode($content);

    } else {
        // just use php's json if it is available
        return json_decode($content, $arg);
    }
}

class GSAPI
{
    protected $sessionID = '';
    protected $userID = 0;
    protected $callCount = 0;
    //private static $host = 'staging.api.grooveshark.com';
    private static $host = 'api.grooveshark.com';
    private static $instance;

    public function __construct($sessionID = '')
    {
        if (empty($sessionID)) {
            $result = self::callRemote('startSession', array());
            $this->sessionID = $result['result']['sessionID'];
        } else {
            $this->sessionID = $sessionID;
        }
    }

    private static function createMessageSig($method, $params, $secret)
    {
        ksort($params);
        $data = '';
        foreach ($params as $key => $value) {
            $data .= "$key{$value}";
        }
        $data = "$method{$data}";
        $sig = hash_hmac('md5', $data, $secret);
        return $sig;
    }

    private static function callRemote($method, $params = array()) 
    {
        $url = sprintf('http://%s/ws/2.0/?method=%s&%s&wsKey=wordpress&sig=%s&format=json', self::$host, $method, http_build_query($params), self::createMessageSig($method, $params, 'd6c59291620c6eaa5bf94da08fae0ecc'));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        $result = curl_exec($curl);
        curl_close($curl);
        return gs_json_decode($result, true);
    }

    /*
     * Gets an instance of the GSAPI object
     * 
     * @param   mixed[] Array containing either a sessionID element or an APIKey element
     * @return  GSAPI   Instance of the GSAPI object
     */
    public static function getInstance($sessionID = '')
    {
        if (!(self::$instance instanceof GSAPI)) {
            self::$instance = new GSAPI($sessionID);
        }
        return self::$instance;
    }

    public function getUserID() {return $this->userID;}
    public function getSessionID() {return $this->sessionID;}
    public function getApiCallsCount() {return $this->callCount;}

    /**
     * Authenticates the user for current API session
     *
     * @param   string  username
     * @param   string  token
     */
    public function authenticateUser($username, $token)
    {
        $result = self::callRemote('authenticateUser', array('sessionID' => $this->sessionID, 'username' => $username, 'token' => $token));
        if (!isset($result['result']) && !isset($result['result']['UserID'])) {
            return false;
        } else {
            $this->userID = $result['result']['UserID'];
            return true;
        }
    }

    /**
     * Logs the user out (just to make sure songs are not added to their account)
     *
     */
    public function logout()
    {
        self::callRemote('logout', array('sessionID' => $this->sessionID));
    }

    /**
     * Gets the logged-in user's username, returns an empty string if no username is found
     * @return  string  username (empty string on failure)
     */
    public function getUsername()
    {
        if ($this->userID != 0) {
            $result = self::callRemote('getUserInfoFromSessionID', array('sessionID' => $this->sessionID));
            if (isset($result['result']) && isset($result['result'][0]) && isset($result['result'][0]['username'])) {
                return $result['result'][0]['username'];
            } else {
                return '';
            }
        }
        return '';
    }

    /**
     * Performs an API search for songs
     *
     * @param   string  Search query
     * @param   int     Search limit
     * @return  mixed   Songs array or error
     */
    public function searchSongs($query, $limit)
    {
        $result = self::callRemote('getSongSearchResultsEx', array('query' => $query, 'limit' => $limit));
        if (isset($result['result'])) {
            return $result['result']['songs'];
        } elseif (isset($result['errors'])) {
            return array('error' => $result['errors'][0]['code']);
        } else {
            return array('error' => -4);
        }
    }

    /**
     * Gets song information
     *
     * @param   int     songID
     * @return  mixed   Song information or error
     */
    public function songAbout($songID)
    {
        $result = self::callRemote('getSongInfoEx', array('songID' => $songID));
        if (isset($result['result'])) {
            return $result['result'];
        } elseif (isset($result['errors'])) {
            return array('error' => $result['errors'][0]['code']);
        } else {
            return array('error' => -8);
        }
    }

    /**
     * Get the url to link to a song on Grooveshark
     *
     * @param   int     songID
     * @return  string  the song's url, or empty string on failure
     */
    public function getSongUrl($songID)
    {
        $result = self::callRemote('getSongURLFromSongID', array('songID' => $songID));
        if (isset($result['result'])) {
            return $result['result']['url'];
        } else {
            return '';
        }
    }

    /**
     * Gets the favorite songs of the logged-in user
     *
     * @return  mixed   Songs list or error
     */
    public function userGetFavoriteSongs()
    {
        if ($this->userID == 0) {
            return array('error' => 'User Not Logged In');
        }
        $result = self::callRemote('getUserFavoriteSongs', array('sessionID' => $this->sessionID));
        if (isset($result['result'])) {
            return $result['result'];
        } elseif (isset($result['errors'])) {
            return array('error' => $result['errors'][0]['code']);
        } else {
            return array('error' => -32);
        }
    }

    /**
     * Gets the playlists of the logged-in user
     *
     * @return  mixed   playlistis or error
     */
    public function userGetPlaylists()
    {
        if ($this->userID == 0) {
            return array('error' => 'User Not Logged In');
        }
        $result = self::callRemote('getUserPlaylists', array('sessionID' => $this->sessionID));
        if (isset($result['result'])) {
            return $result['result'];
        } elseif (isset($result['errors'])) {
            return array('error' => $result['errors'][0]['code']);
        } else {
            return array('error' => -64);
        }
    }

    /**
     * Gets playlist information
     *
     * @param   int     playlistID
     * @return  mixed   playlist information or error
     */
    public function playlistAbout($playlistID)
    {
        $result = self::callRemote('getPlaylistInfo', array('playlistID' => $playlistID));
        if (isset($result['result'])) {
            return $result['result'];
        } elseif (isset($result['errors'])) {
            return array('error' => $result['errors'][0]['code']);
        } else {
            return array('error' => -128);
        }
    }

    /**
     * Creates a playlist with songs
     *
     * @param   string  playlist name
     * @param   int[]   Array consisting of songIDs for the songs to be added to the new playlist
     * @return  int     playlistID (or 0 on failure, -1 on duplicate)
     */
    public function playlistCreate($name, $songIDs)
    {
        $result = self::callRemote('createPlaylist', array('name' => $name, 'songIDs' => $songIDs, 'sessionID' => $this->sessionID));
        if (isset($result['result']) && ($result['result']['success'] == 1)) {
            return $result['result']['playlistID'];
        } elseif (isset($result['errors'])) {
            $duplicate = false;
            foreach($result['errors'] as $error) {
                if ($error['code'] == 800) {
                    $duplicate = true;
                }
            }
            if ($duplicate) {
                return -1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * Sets the songs for a playlist
     * 
     * @param   int     playlistID for the playlist to be modified
     * @param   int[]   Array consisting of songIDs for the songs to be added to the new playlist
     * @return  bool    true on success, false on failure
     */

    public function playlistSetSongs($playlistID, $songIDs)
    {
        $result = self::callRemote('setPlaylistSongs', array('playlistID' => $playlistID, 'songIDs' => $songIDs, 'sessionID' => $this->sessionID));
        if (isset($result['result']) && ($result['result']['success'] == 1)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the songs of a playlist
     *
     * @param   int     playlistID
     * @return  mixed   list of songs or error
     */
    public function playlistGetSongs($playlistID)
    {
        $result = self::callRemote('getPlaylistSongs', array('playlistID' => $playlistID));
        if (isset($result['result'])) {
            return $result['result'];
        } elseif (isset($result['errors'])) {
            return array('errors' => $result['errors'][0]['code']);
        } else {
            return array('error' => -512);
        }
    }

    /**
     * Get the url for a playlist
     *
     * @param   int     playlistID
     * @return  string  playlist url (or empty string on failure)
     */
    public function getPlaylistUrl($playlistID)
    {
        $result = self::callRemote('getPlaylistURLFromPlaylistID', array('playlistID' => $playlistID));
        if (isset($result['result'])) {
            return $result['result'];
        } else {
            return '';
        }
    }

    /**
     * Gets the widget embed code for a song
     *
     * @param   int     songID
     * @param   int     width of widget in pixels
     * @return  string  Widget's embed code (or error string)
     */
    public function songGetWidgetEmbedCode($songID, $width, $theme = 'metal')
    {
        return self::getWidgetEmbedCode('songWidget.swf', $width, 40, array('songIDs' => $songID), 0, null, $theme);
    }

    /**
     * Gets widget embed code for an autoplay widget
     *
     * @param   int     songID
     * @return  string  widget's embed code or error string
     */
    public function songGetApWidgetEmbedCode($songID) {
        return self::getWidgetEmbedCode('songWidget.swf', 0, 0, array('songIDs' => $songID), 1, null, 'metal');
    }

    /**
     * Gets the widget embed code (hex codes do not require prefix)
     *
     * @param   int[]   $songIDs: array consisting of the songIDs of the songs for the widget
     * @param   int     $width in pixels
     * @param   int     $height in pixels
     * @param   string  $name: widget name
     * @param   string  $bt: body text color hex code
     * @param   string  $bth: body text hover color hex code
     * @param   string  $bbg: body background color hex code
     * @param   string  $bfg: body foreground color hex code
     * @param   string  $pbg: player background color hex code
     * @param   string  $pfg: player foreground color hex code
     * @param   string  $pbgh: player background hover color hex code
     * @param   string  $pfgh: player foreground hover color hex code
     * @param   string  $lbg: list background color hex code
     * @param   string  $lfg: list foreground color hex code
     * @param   string  $lbgh: list background hover color hex code
     * @param   string  $lfgh: list foreground hover color hex code
     * @param   string  $sb: scrollbar color hex code
     * @param   string  $sbh: scrollbar hover color hex code
     * @param   string  $secondaryIcon color hex code
     * @return  string  widget embed code
     */
    public function playlistGetWidgetEmbedCode($songIDs, $width, $height, $name, $bt, $bth, $bbg, $bfg, 
                                               $pbg, $pfg, $pbgh, $pfgh, $lbg, $lfg, $lbgh, $lfgh, $sb, $sbh, $secondaryIcon)
    {
        $colors = array('bt' => $bt, 'bth' => $bth, 'bbg' => $bbg, 'bfg' => $bfg, 'pbg' => $pbg, 'pfg' => $pfg, 'pbgh' => $pbgh, 'pfgh' => $pfgh, 'lbg' => $lbg, 'lfg' => $lfg, 'lbgh' => $lbgh, 'lfgh' => $lfgh, 'sb' => $sb, 'sbh' => $sbh, 'si' => $secondaryIcon);

        return self::getWidgetEmbedCode('widget.swf', $width, $height, array('songIDs' => implode(',',$songIDs)), 0, $colors);
    }

    private static function getWidgetEmbedCode($swfName, $width, $height, $ids, $ap = 0, $colors = null, $style = null)
    {
        $ids = 'songIDs=' . $ids['songIDs'];
        $colors = is_array($colors) && !empty($colors) ? '&amp;' . http_build_query($colors) : '';
        $ap = ($ap != 0) ? "&amp;p=$ap" : '';
        $style = '&style=' . $style;
        $embed = "
        <object width='$width' height='$height'>
            <param name='movie' value='http://listen.grooveshark.com/$swfName'></param>
            <param name='wmode' value='window'></param>
            <param name='allowScriptAccess' value='always'></param>
            <param name='flashvars' value='hostname=cowbell.grooveshark.com&amp;{$ids}{$ap}{$colors}{$style}'></param>
            <embed src='http://listen.grooveshark.com/$swfName' type='application/x-shockwave-flash' width='$width' height='$height' flashvars='hostname=cowbell.grooveshark.com&amp;{$ids}{$ap}{$colors}{$style}' allowScriptAccess='always' wmode='window'></embed>
        </object>";
        return $embed;
    }

}
