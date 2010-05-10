/* Contains all code that is exclusive to the admin panel */

/* Code for jquery.json plugin */
(function($){var m={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'},s={'array':function(x){var a=['['],b,f,i,l=x.length,v;for(i=0;i<l;i+=1){v=x[i];f=s[typeof v];if(f){v=f(v);if(typeof v=='string'){if(b){a[a.length]=',';}a[a.length]=v;b=true;}}}a[a.length]=']';return a.join('');},'boolean':function(x){return String(x);},'null':function(x){return"null";},'number':function(x){return isFinite(x)?String(x):'null';},'object':function(x){if(x){if(x instanceof Array){return s.array(x);}var a=['{'],b,f,i,v;for(i in x){v=x[i];f=s[typeof v];if(f){v=f(v);if(typeof v=='string'){if(b){a[a.length]=',';}a.push(s.string(i),':',v);b=true;}}}a[a.length]='}';return a.join('');}return'null';},'string':function(x){if(/["\\\x00-\x1f]/.test(x)){x=x.replace(/([\x00-\x1f\\"])/g,function(a,b){var c=m[b];if(c){return c;}c=b.charCodeAt();return'\\u00'+Math.floor(c/16).toString(16)+(c%16).toString(16);});}return'"'+x+'"';}};$.parseJSON=function(v,safe){if(safe===undefined)safe=$.parseJSON.safe;if(safe&&!/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/.test(v))return undefined;return eval('('+v+')');};$.parseJSON.safe=false;})(jQuery);

function groovesharkFullSetup(gsDataStore) {
    // Adds the music placement button to the visual buttons row if that row exists in the dom
    if (!!jQuery('#content_toolbar1')[0]) {
        jQuery(jQuery('#content_toolbar1')[0].rows[0]).append("<td><a href='javascript:;' title='Place your music' class='mceButton mceButtonEnabled' id='content_grooveshark' onclick='insertGroovesharkTag(2)'><span class='gsNote'></span></a></td>");
    } else {
        // The row does not exist in the dom, add an onclick event to the button that reveals the row
        jQuery('#edButtonPreview').click(function() {
            if (jQuery('#content_grooveshark').length == 0) {
                jQuery(jQuery('#content_toolbar1')[0].rows[0]).append("<td><a href='javascript:;' title='Place your music' class='mceButton mceButtonEnabled' id='content_grooveshark' onclick='insertGroovesharkTag(2)'><span class='gsNote'></span></a></td>");
            }
        });
    }
    // Adds the music placement text button to the text buttons
    jQuery('#ed_toolbar').append("<input type='button' id='ed_grooveshark' class='ed_button' value='music' title='Place your music' onclick='insertGroovesharkTag(1);'/>");
    // Set up the jQuery context to minimize the search area for the jQuery() function
    var context = jQuery('#groovesharkdiv');
    // Change the colors preview based on the selected colors determined server-side
    changeColor(jQuery('#colors-select', context)[0]);
    gsUpdateSinglePreview(jQuery('#widgetTheme', context)[0], jQuery('#singleWidgetWidth', context)[0]);
    // Set up data store for the plugin
    var value = jQuery('#wpVersion', context).val();
    if (!!value && ((value.indexOf('2.6') != -1) || (value.indexOf('2.5') != -1))) {
        gsDataStore.data('gsDataStore').isVersion26 = true;
    }
    // More jQuery event delegation
    jQuery('#search-option, #favorites-option, #playlists-option', context).click(function() {
        gsToggleSongSelect(jQuery(this).html());
        return false;
    });
    jQuery('#gs-query', context).keydown(function(ev) {
        if (ev.which == 13) {
            jQuery('#gsSearchButton').click();
            return false;
        } else {
            return true;
        }
    });
    jQuery('#gsSearchButton', context).click(function() {
        gsSearch(this);
        return false;
    });
    jQuery('#clearSelected', context).click(function() {
        clearSelected();
        return false;
    });
    jQuery('#jsLink', context).click(function() {
        gsToggleAppearance();
        return false;
    });
    jQuery('#gsAppearanceRadio input', context).click(function() {
        changeAppearanceOption(this.value);
    });
    jQuery('#displayPhrase, #playlistsName', context).change(function() {
        changeExample(this);
    }).keydown(function(ev) {
        if (ev.which == 13) {
            changeExample(this);
            return false;
        }
        return true;
    });
    jQuery('#widgetWidth, #widgetHeight, #singleWidgetWidth', context).keydown(function(ev) {
        if (ev.which == 13) {
            checkWidgetValue(this);
            return false;
        }
        return true;
    }).change(function() {
        checkWidgetValue(this);
    });
    jQuery('#colors-select', context).change(function() {
        changeColor(this);
    }).keyup(function() {
        changeColor(this);
    });
    jQuery('#widgetTheme', context).change(function() {
        gsUpdateSinglePreview(this, jQuery('#singleWidgetWidth', context)[0]);
    }).keyup(function() {
        if (gsDataStore.widgetTheme != this.value) {
            gsDataStore.widgetTheme = this.value;
            gsUpdateSinglePreview(this, jQuery('#singleWidgetWidth', context)[0]);
        }
    });
}    

//Inserts the tag which will be replaced by embed code.
function insertGroovesharkTag(identifier) {
    if (!!switchEditors && ((!!switchEditors.go()) && identifier == 2)) {
        switchEditors.go('content','html');
    }
    if ((!!document.getElementById('gsTagStatus')) && document.getElementById('gsTagStatus').value == 0) {
        if (!!document.getElementById('content')) {
            var edCanvas = document.getElementById('content');
        }
        //IE support
        if (document.selection) {
            edCanvas.focus();
            sel = document.selection.createRange();
            if (sel.text.length > 0) {
                sel.text = sel.text + '[[grooveshark]]';
            } else {
                sel.text = '[[grooveshark]]';
            }
            edCanvas.focus();
        } else if (edCanvas.selectionStart || edCanvas.selectionStart == '0') {
            //MOZILLA/NETSCAPE support
            var startPos = edCanvas.selectionStart;
            var endPos = edCanvas.selectionEnd;
            var cursorPos = endPos;
            var scrollTop = edCanvas.scrollTop;
            if (startPos != endPos) {
                edCanvas.value = edCanvas.value.substring(0, endPos) + '[[grooveshark]]' + edCanvas.value.substring(endPos, edCanvas.value.length);
                cursorPos += 15;
            } else {
                edCanvas.value = edCanvas.value.substring(0, startPos) + '[[grooveshark]]' + edCanvas.value.substring(endPos, edCanvas.value.length);
                cursorPos = startPos + 15;
            }
            edCanvas.focus();
            edCanvas.selectionStart = cursorPos;
            edCanvas.selectionEnd = cursorPos;
            edCanvas.scrollTop = scrollTop;
        } else {
            edCanvas.value += '[[grooveshark]]';
            edCanvas.focus();
        }
        jQuery('#ed_grooveshark').attr('title', 'One tag at a time').removeAttr('onclick').unbind('click');
        jQuery('#content_grooveshark').attr('title', 'One tag at a time').removeAttr('onclick').unbind('click');
        document.getElementById('gsTagStatus').value = 1;
    }
    if (!!switchEditors && ((!!switchEditors.go()) && identifier == 2)) {
        switchEditors.go('content','tinymce');
    }
}

/* Add Music Box functions */

//Handles selecting a playlist for addition to the post.
function addToSelectedPlaylist(obj) {
    // prepare playlist info
    var playlistSongs = obj.firstChild.innerHTML;
    var playlistID = obj.name;
    var playlistSongs = jQuery.parseJSON(playlistSongs);
    var selectedTable = jQuery('#selected-songs-table');
    if ((!playlistSongs) || (playlistSongs.length == 0)) {
        // No songs available, display error message
        selectedTable.append('<tr class="temporaryError"><td></td><td>An unexpected error occurred while loading your playlist. Contact the author for support</td><td></td></tr>');
        setTimeout(function(){jQuery('.temporaryError').fadeOut('slow', function(){jQuery(this).remove();});}, 3000);
        return;
    }
    // prepare needed variables
    // Prepare widgetHeight for auto-adjust
    var widgetHeight = (+jQuery('#widgetHeight').val());
    // Prepare the new song content
    var newSongContent = [];
    var count = selectedTable[0].rows.length;
    jQuery.each(playlistSongs, function(i, song) {
        if ((!!song.songNameComplete) && (!!song.songID)) {
            count++;
            newSongContent.push("<tr class='", (count % 2 ? 'gsTr27' : 'gsTr1'), " newRow'><td class='gsTableButton'><a title='Play this song' class='gsPlay' name='", song.songID, "' style='cursor: pointer;'></a></td><td>", song.songNameComplete, "<input type='hidden' class='gsSong-", song.songID, "' name='", song.songNameComplete, "::", song.songID, "'/> <input type='hidden' name='songsInfoArray[]' class='songsInfoArrayClass' value='", song.songID, "'/></td><td class='gsTableButton'><a title='Remove This Song' class='gsRemove' style='cursor: pointer; float: right;'></a></td></tr>");
            if (widgetHeight < 1000) {
                widgetHeight += 22;
            }
        }
    });
    selectedTable.append(newSongContent.join(''));
    updateCount();
    gsUpdateMultiPreview();
    jQuery('#widgetHeight').val(widgetHeight);
}

// Handles showing all playlist songs before adding to post
function showPlaylistSongs(obj) {
    var playlistID = obj.name;
    var context = obj.parentNode.parentNode.parentNode;
    jQuery('.child-'+playlistID, context).toggle();
    jQuery(obj).attr('class', 'gsHide').attr('title', 'Hide All Songs In This Playlist');
}

function hidePlaylistSongs(obj) {
    var context = obj.parentNode.parentNode.parentNode;
    var playlistID = obj.name;
    jQuery('.child-'+playlistID, context).toggle();
    jQuery(obj).attr('class', 'gsShow').attr('title', 'Show All Songs In This Playlist');
}

//Change the example display phrase to reflect what the user typed in.
function changeExample(obj) {
    if (obj.id == 'playlistsName') {
        changeExamplePlaylist(obj);
    }
    obj.nextSibling.innerHTML = 'Example: "' + obj.value + ': song by artist"';
}

//Change the example playlist name to reflect what the user typed in.
function changeExamplePlaylist(obj) {
    obj.nextSibling.innerHTML = 'Example: "Grooveshark: ' + obj.value + '"';
}

//Toggles whether appearance is shown or hidden (presumably once a user sets the widget/link appearance, they would use that appearance for a while)
function gsToggleAppearance(){
    if(document.getElementById('gsAppearance').className == 'gsAppearanceHidden'){
      document.getElementById('gsAppearance').className = 'gsAppearanceShown';
      document.getElementById('jsLink').innerHTML = "&rarr; Appearance";
    }else{
      document.getElementById('gsAppearance').className = 'gsAppearanceHidden';
      document.getElementById('jsLink').innerHTML = "&darr; Appearance";
    }
}

//Handles appending a widget/link to the post content.
function gsAppendToContent(obj) {
    //songsArray = document.getElementsByName('songsInfoArray[]');
    var songsArray = jQuery("input.songsInfoArrayClass");
    if (songsArray.length > 0) {
        obj.value = 'Saving...';
        obj.disabled = true;
        var songIDs = [];
        var arrayLength = songsArray.length;
        for (var i = 0; i < arrayLength; i++) {
            songIDs[i] = songsArray[i].value;
        }
        var widgetWidth = document.getElementById('widgetWidth').value;
        if (widgetWidth < 150) {
            widgetWidth = 150;
        }
        if (widgetWidth > 1000) {
            widgetWidth = 1000;
        }
        var displayOptions = document.getElementsByName("displayChoice");
        var displayOption = displayOptions[1].checked ? 'widget' : 'link';
        var songEmbed = "<div id='" + (displayOption == 'widget' ? 'gsWidget' : 'gsLink') + "'>";
        
        if (songIDs.length == 1) {
            // single song
            if (displayOption == 'widget') {
                songEmbed += getSingleGSWidget(songIDs[0], widgetWidth);
            } else {
                var name = jQuery('.gsSong-' + songIDs[0] + ':first').attr('name');
                var songNameComplete = name.split('::')[0];
                var songName = songNameComplete.split(' by ')[0];
                var displayPhrase = document.getElementById('displayPhrase').value;
                jQuery.post(document.getElementById('gsBlogUrl').value + '/wp-content/plugins/grooveshark/gsGetSongLink.php', {songID: songIDs[0]}, function(returnedData) {
                    songEmbed += "<a target='_blank' href='" + returnedData + "'>" + displayPhrase + ": " + songNameComplete + "</a></div>";
                    gsAddSongEmbedToPost(songEmbed);
                    obj.value = 'Save Music';
                    obj.disabled = false;
                    gsDisplayStatusMessage('Your song link is in your post.');
                });
                return;
            }
        } else {
            if (displayOption == 'widget') {
                var dashboardOptions = document.getElementById("gsDashboardChoice");
                var dashboardOption = dashboardOptions.checked ? 1 : 0;
                var widgetWidth = document.getElementById('widgetWidth').value;
                var widgetHeight = document.getElementById('widgetHeight').value;
                if (widgetWidth < 150) {
                    widgetWidth = 150;
                }
                if (widgetHeight < 150) {
                    widgetHeight = 150;
                }
                if (widgetWidth > 1000) {
                    widgetWidth = 1000;
                }
                if (widgetHeight > 1000) {
                    widgetHeight = 1000;
                }
                document.getElementById('widgetWidth').value = widgetWidth;
                document.getElementById('widgetHeight').value = widgetHeight;
                var colorScheme = document.getElementById('colors-select').value;
                var colorArray = getBackgroundHex(colorScheme);
                var widgetEmbed = getPlaylistGSWidget(songIDs, widgetWidth, widgetHeight, colorArray[1], colorArray[0], colorArray[0], colorArray[2], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[2], colorArray[1]);
                songEmbed += widgetEmbed;
                // save dashboard options
                if (dashboardOption == 1) {
                    jQuery.post(document.getElementById('gsBlogUrl').value + '/wp-content/plugins/grooveshark/gsSaveEmbed.php', {embedCode: widgetEmbed, dashboardOption: dashboardOption});
                }
                
            } else {
                if (document.getElementById('gsToken').value == '') {
                    alert('You need to log in to provide playlist links. You can log in using the plugin Settings page.');
                } else {
                    var displayPhrase = document.getElementById('displayPhrase').value;
                    var playlistName = document.getElementById('playlistsName').value;
                    var postSongIDs = songIDs.join('::');
                    jQuery.post(document.getElementById('gsBlogUrl').value + '/wp-content/plugins/grooveshark/gsGetPlaylistLink.php', {playlistName: playlistName, songIDs: postSongIDs, displayPhrase: displayPhrase, sessionID: document.getElementById('gsSessionID').value, username: document.getElementById('gsUsername').value, token: document.getElementById('gsToken').value}, function(returnedData) {
                        songEmbed += returnedData;
                        songEmbed += '</div>';
                        gsAddSongEmbedToPost(songEmbed);
                        obj.value = 'Save Music';
                        obj.disabled = false;
                        gsDisplayStatusMessage('Your playlist link is in your post.');
                    });
                    return;
                }
            }
        }
        songEmbed += '</div>';
        gsAddSongEmbedToPost(songEmbed);

        obj.value = 'Save Music';
        obj.disabled = false;
        gsDisplayStatusMessage('Your widget is in your post.');
    } else {
        gsDisplayStatusMessage('Please select songs to save to your post.');
    }
}

function gsAddSongEmbedToPost(songEmbed) {
    var positionBeginning = document.getElementById('gsPosition').checked ? true : false;
    if ((typeof(switchEditors) == 'object') && (typeof(switchEditors.go) == 'function')) {
        switchEditors.go('content','html');
    }
    var content = jQuery('#content');
    if (content.length) {
        if (document.getElementById('gsTagStatus').value == 1) {
            content.val(gsReplaceTag(content.val(), songEmbed));
        } else {
            if (positionBeginning) {
                content.val(songEmbed + content.val());
            } else {
                content.val(content.val() + songEmbed);
            }
        }
    }
    document.getElementById('gsTagStatus').value = 0;
    if (!!document.getElementById('ed_grooveshark')) {
        document.getElementById('ed_grooveshark').disabled = false;
        document.getElementById('ed_grooveshark').title = 'Place your music';
    }
    if (!!document.getElementById('content_grooveshark')) {
        document.getElementById('content_grooveshark').onclick = function() {insertGroovesharkTag();};
        document.getElementById('content_grooveshark').title = 'Place your music';
    }
    if ((typeof(switchEditors) == 'object') && (typeof(switchEditors.go) == 'function')) {
        switchEditors.go('content','tinymce');
    }
}

function gsReplaceTag(postContent, embedCode) {
    //takes post content, looks for a [[grooveshark]] tag, and replaces with embed code 
    if (postContent.indexOf('[[grooveshark]]') != -1) {
        postContentArray = postContent.split('[[grooveshark]]');
        postContent = postContentArray[0] + embedCode + postContentArray[1];
    }
    return postContent;
}

function changeAppearanceOption(appearanceOption) {
    switch (appearanceOption) {
        case 'link':
            jQuery('#gsDisplayLink').show();
            jQuery('#gsDisplayWidget').hide();
            break;
        case 'widget':
            jQuery('#gsDisplayWidget').show();
            jQuery('#gsDisplayLink').hide();
            break;
    }
}

//Toggles whether the user is shown the search, their favorites, or their playlists.
function gsToggleSongSelect(myRow){
    var isVersion26 = jQuery('#gsDataStore').data('gsDataStore').isVersion26;
    var tabClass = isVersion26 ? 'gsTabActive26' : 'gsTabActive27';
    var tabClass2 = isVersion26 ? 'gsTabInactive26' : 'gsTabInactive27';
    var isQueryEmpty = (jQuery('#queryResult').html() == '');
    switch (myRow) {
        case 'Search':
            jQuery('#playlists-option').attr('class', tabClass2);
            jQuery('#songs-playlists').hide();
            jQuery('#favorites-option').attr('class', tabClass2);
            jQuery('#songs-favorites').hide();
            jQuery('#search-option').attr('class', tabClass);
            jQuery('#songs-search').show();
            if (!isQueryEmpty) {
                jQuery('#search-results-container').show();
            }
            break;

        case 'Favorites':
            jQuery('#playlists-option').attr('class', tabClass2);
            jQuery('#songs-playlists').hide();
            jQuery('#search-option').attr('class', tabClass2);
            jQuery('#songs-search').hide();
            jQuery('#search-results-container').hide();
            jQuery('#favorites-option').attr('class', tabClass);
            jQuery('#songs-favorites').show();
            break;

        case 'Playlists':
            jQuery('#favorites-option').attr('class', tabClass2);
            jQuery('#songs-favorites').hide();
            jQuery('#search-option').attr('class', tabClass2);
            jQuery('#songs-search').hide();
            jQuery('#search-results-container').hide();
            jQuery('#playlists-option').attr('class', tabClass);
            jQuery('#songs-playlists').show();
            break;

        default:
            break;
    }
}


//Change the base, primary, and secondary colors to show the user what colors a given color scheme uses.
function changeColor(obj) {
    if(!obj) {
        return false;
    }
    curValue = (+obj.options[obj.selectedIndex].value);
    var colorArray = getBackgroundHex(curValue);
    document.getElementById('base-color').style.backgroundColor = '#' + colorArray[0];
    document.getElementById('primary-color').style.backgroundColor = '#' + colorArray[1];
    document.getElementById('secondary-color').style.backgroundColor = '#' + colorArray[2];
    gsUpdateMultiPreview();
}

function changeSidebarColor(obj) {
    if (!obj) {
        return false;
    }
    var curValue = (+obj.options[obj.selectedIndex].value);
    var gsDataStore = jQuery('#gsDataStore').data('gsDataStore');
    if (curValue != gsDataStore.colorScheme) {
        gsDataStore.colorScheme = curValue;
        var context = jQuery(obj.parentNode.parentNode.parentNode);
        var colorArray = getBackgroundHex(curValue);
        jQuery('#widget-base-color', context)[0].style.backgroundColor = '#' + colorArray[0];
        jQuery('#widget-primary-color', context)[0].style.backgroundColor = '#' + colorArray[1];
        jQuery('#widget-secondary-color', context)[0].style.backgroundColor = '#' + colorArray[2];
    }
}

function gsUpdateSinglePreview(theme, width) {
    var songID = '203993'; // Stay on Your Toes by Del the Funky Homosapien
    var songsArray = jQuery('input.songsInfoArrayClass');
    if (songsArray.length == 1) {
        // single song available in selected song, use that for preview instead
        songID = songsArray[0].value;
    }
    var embed = getSingleGSWidget(songID, width.value, theme.value);
    document.getElementById('gsSingleWidgetPreview').innerHTML = embed;
}

function gsUpdateMultiPreview() {
    var songsArray = jQuery('input.songsInfoArrayClass');
    var songIDs = [];
    if (songsArray.length > 1) {
        var songCount = 0;
        var arrayLength = songsArray.length;
        for (var i = 0; i < arrayLength; i++) {
            if (songCount < 38) {
                // limit number of songs added to the preview
                songIDs[i] = songsArray[i].value;
            }
            songCount++;
        }
    } else {
        // Ask About Me by Girl Talk, I Don't Know by Lisa Hannigan, Me and My Friends by Matt Butcher, and Stay on Your Toes by Del the Funky Homosapien
        songIDs = ['13963', '23419725', '21526481', '203993'];
    }
    var widgetWidth = document.getElementById('widgetWidth').value;
    var widgetHeight = document.getElementById('widgetHeight').value;
    var colorArray = getBackgroundHex(document.getElementById('colors-select').value);
    var embed = getPlaylistGSWidget(songIDs, widgetWidth, widgetHeight, colorArray[1], colorArray[0], colorArray[0], colorArray[2], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[2], colorArray[1]);
    document.getElementById('gsWidgetExample').innerHTML = embed;
}

function checkWidgetValue(obj) {
    if (+obj.value < 150) {
        obj.value = 150;
    }
    if (+obj.value > 1000) {
        obj.value = 1000;
    }
    if (isNaN(+obj.value)) {
        obj.value = 200;
    }
    if (obj.id == 'singleWidgetWidth') {
        gsUpdateSinglePreview(jQuery('#widgetTheme')[0], obj);
    } else {
        gsUpdateMultiPreview();
    }
}
