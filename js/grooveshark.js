function debug(e) {
    if (window.console && console.log) {
        // Firebug
        return console.log(e);
    }
    if (window.opera && opera.postError) {
        // Dragonfly
        return opera.postError(e);
    }
    if (window.console && console.debug) {
        // Webkit
        return console.debug(e);
    }
}

/*
 * Main Document Ready Function
 */
jQuery(function() {
    // Resize the search button for small box used in comments
    if (jQuery('#gsSearchButton').hasClass('gsSmallButton')) {
        jQuery('#gsSearchButton').width(60);
    }
    var gsDataStore = jQuery('#gsDataStore');
    gsDataStore.data('gsDataStore', {isVersion26: false, lastPlayed: false, theme: 'metal', colorScheme: '0'});
    // Add an onfocus event to the song search input to remove grayed-out text
    jQuery('#gs-query').focus(function() {
        if (jQuery(this).hasClass('empty')) {
            jQuery(this).removeClass('empty').val('');
        }
    });
    // Event delegation for clicks
    jQuery('#save-music-choice-favorites, #save-music-choice-playlists, #selected-songs-table, #search-results-wrapper').click(function(ev) {
        if (ev.target.className.indexOf('gsAdd') != -1) {
            if (ev.target.className.indexOf('gsPlaylistAdd') != -1) {
                addToSelectedPlaylist(ev.target);
            } else {
                addToSelected(ev.target.name);
            }
            return false;
        }
        if (ev.target.className.indexOf('gsRemove') != -1) {
            removeFromSelected(ev.target);
            return false;
        }
        if (ev.target.className.indexOf('gsShow') != -1) {
            // Only works one way (hide)
            showPlaylistSongs(ev.target);
            return false;
        }
        if (ev.target.className.indexOf('gsHide') != -1) {
            hidePlaylistSongs(ev.target);
            return false;
        }
        if (ev.target.className.indexOf('gsPlay') != -1) {
            toggleSong(ev.target);
            return false;
        }
        if (ev.target.className.indexOf('gsPause') != -1) {
            toggleSong(ev.target);
            return false;
        }
    });
    /* The following code for dragging and dropping table rows was adapted from TableDND, created by Denis Howlet
     * You can find the original version at: http://www.isocra.com/
     */
    var songsTable = jQuery('#selected-songs-table');
    songsTable.data('dnd', {table: songsTable[0], oldY: 0, allRows: []});
    songsTable.data('dnd').isVersion26 = ((document.getElementById('wpVersion').value.indexOf('2.6') != -1) || (document.getElementById('wpVersion').value.indexOf('2.5') != -1)) ? true : false;
    jQuery(songsTable[0]).mousemove(function(ev) {
        currentData = jQuery(this).data('dnd');
        if (currentData.dragObject) {
            var y = ev.pageY - currentData.mouseOffset;
            if (y != currentData.oldY) {
                var movingDown = y > currentData.oldY;
                currentData.oldY = y;
                var currentRow = jQuery(ev.target).closest('tr')[0];
                if (currentRow && (currentRow.parentNode.parentNode.id = 'selected-songs-table')) {
                    if (currentData.dragObject != currentRow) {
                        if (movingDown) {
                            var nextRow = currentData.dragObject.nextSibling;
                            while (!!nextRow && (nextRow.previousSibling != currentRow)) {
                                if (!jQuery(nextRow).hasClass('iteratedRow')) {
                                    currentData.allRows.push(nextRow);
                                    jQuery(nextRow).addClass('iteratedRow');
                                }
                                nextRow = nextRow.nextSibling;
                            }
                            jQuery(currentRow).after(currentData.dragObject);
                        } else {
                            var prevRow = currentData.dragObject.previousSibling;
                            while (!!prevRow && (prevRow.nextSibling != currentRow)) {
                                if (!jQuery(prevRow).hasClass('iteratedRow')) {
                                    currentData.allRows.push(prevRow);
                                    jQuery(prevRow).addClass('iteratedRow');
                                }
                                prevRow = prevRow.previousSibling;
                            }
                            jQuery(currentRow).before(currentData.dragObject);
                        }
                    }
                }
            }
            return false;
        }
    });
    jQuery(document).mouseup(function() {
        var currentData = jQuery('#selected-songs-table').data('dnd');
        if (currentData.dragObject && currentData.allRows.length) {
            var rowClass = currentData.isVersion26 ? 'gsTr26' : 'gsTr27';
            var allRows = currentData.allRows;
            for (var i = 0; i < allRows.length; i++) {
                allRows[i].className = (allRows[i].rowIndex % 2) ? 'gsTr1' : rowClass;
            }
            currentData.dragObject = null;
            currentData.mouseOffset = null;
            currentData.oldY = 0;
            currentData.allRows = [];
        }
    });
    jQuery(songsTable[0]).mousedown(function(ev) {
        if ((ev.target.className.indexOf('gsPlay') != -1) || (ev.target.className.indexOf('gsRemove') != -1)) {
            return false;
        }
        var currentData = jQuery(this).data('dnd');
        var row = jQuery(ev.target).closest('tr')[0];
        row.className = currentData.isVersion26 ? 'gsTrDragged26' : 'gsTrDragged27';
        currentData.dragObject = row;
        currentData.mouseOffset = ev.pageY;
        currentData.allRows.push(row);
        return false;
    });
    // The rest of this function does not need to be setup for the small box
    if ((jQuery('#isSmallBox').val() == 0) && (typeof(groovesharkFullSetup) != 'undefined')) {
        groovesharkFullSetup(gsDataStore);
    }
});

/*
 * Main Grooveshark Add Music Box functions
 */

//Handles the user's searches.
function gsSearch(obj) {
    obj.value = '...';
    obj.disabled = true;
    var query = document.getElementById('gs-query').value;
    var random = Math.floor(Math.random()*10000);
    if (query != '') {
        // load the table containing the search results
        jQuery('#search-results-wrapper').load(document.getElementById('gsBlogUrl').value + "/wp-content/plugins/grooveshark/gsSearch.php?" + random, {query: query, sessionID: document.getElementById('gsSessionID').value, limit: document.getElementById('gsLimit').value, isVersion26: jQuery('#gsDataStore').data('gsDataStore').isVersion26, isSmallBox: document.getElementById('isSmallBox').value}, function(){
            if (jQuery('#search-results-wrapper').children().length > 0) {
                // Header for the search result table
                jQuery('#queryResult').html('Search results for "' + query + '":');
                                // Show results
                jQuery('#search-results-container').add('#search-results-wrapper').show();
            } else {
                jQuery('#queryResult').html('There was an error with your search. If this error persists, please contact the author.').show();
            }
            // Revert buttons to inactive state
            obj.value = 'Search';
            obj.disabled = false;
        });
    }
}


//Handles selecting a song for addition to the post.
function addToSelected(songInfo) {
    var temp = [];
    temp = songInfo.split("::");
    songNameComplete = temp[0];
    songID = temp[1];
    if (songNameComplete && songID) {
        // Prepare the table with all selected songs
        selectedTable = jQuery('#selected-songs-table');
        // Alternating table styles
        var className = isVersion26 ? 'gsTr26' : 'gsTr27';
        var tableLength = selectedTable[0].rows.length;
        var isVersion26 = jQuery('#gsDataStore').data('gsDataStore').isVersion26;
        if (tableLength % 2) {
            className = 'gsTr1';
        }
        // Prepare the row with the selected song
        var rowContent = "<tr class='"+className+"'><td class='gsTableButton'><a title='Play This Song' class='gsPlay' name='"+songID+"' style='cursor: pointer;'></a></td><td>"+songNameComplete+"<input type='hidden' class='gsSong-"+songID+"' name='" + songNameComplete + "::" + songID + "' /><input type='hidden' name='songsInfoArray[]' class='songsInfoArrayClass' value='"+songID+"'/></td><td class='gsTableButton'><a title='Remove This Song' class='gsRemove' style='cursor: pointer; float: right;'></a></td></tr>";
        selectedTable.append(rowContent);
        // Auto-adjust the widget height for the new number of songs, unless height is predetermined by user
        widgetHeight = jQuery('#widgetHeight');
        if (widgetHeight.val() < 1000) {
            widgetHeight.val((+widgetHeight.val()) + 22);
        }
    }
    updateCount();
    if (jQuery.isFunction(gsUpdateMultiPreview)) {
        gsUpdateMultiPreview();
    }
}


//Handles unselecting a song for addition.
function removeFromSelected(element) {
    var gsDataStore = jQuery('#gsDataStore').data('gsDataStore');
    var lastPlayed = gsDataStore.lastPlayed;
    var currentPlayed = jQuery('.gsPlay, .gsPause', element.parentNode.parentNode.parentNode)[0];
    if (lastPlayed.name == currentPlayed.name) {
        // currently played song is deleted, stop playback
        jQuery('#apContainer').empty();
        gsDataStore.lastPlayed = false;
    }
    // Just remove the song's row, adjust widget height as necessary, and update selected song count
    jQuery(element.parentNode.parentNode).remove();
    if ((!!document.getElementById('widgetHeight')) && ((+document.getElementById('widgetHeight').value) > 176)) {
        document.getElementById('widgetHeight').value = (+document.getElementById('widgetHeight').value) - 22;
    }
    jQuery('#selected-songs-table tr:odd').attr('class', 'gsTr1');
    jQuery('#selected-songs-table tr:even').attr('class', jQuery('#gsDataStore').data('gsDataStore').isVersion26 ? 'gsTr26' : 'gsTr27');
    updateCount();
    if (jQuery.isFunction(gsUpdateMultiPreview)) {
        gsUpdateMultiPreview();
    }
}


//Clears all songs that are selected for addition.
function clearSelected() {
    jQuery('#selected-songs-table').empty();
    document.getElementById("selectedCount").innerHTML = "Selected Songs (0):"
    if (!!document.getElementById('widgetHeight')) {
        document.getElementById('widgetHeight').value = 176;
    }
}


//Only needed because the addToSelectedPlaylist function for some reason does not update the selected count on its own.
function updateCount() {
    var selectedCount = document.getElementById("selectedCount");
    selectedCountValue = jQuery('#selected-songs-table')[0].rows.length;
    selectedCount.innerHTML = "Selected Songs (" + selectedCountValue + "):";
    if (selectedCountValue > 0) {
        document.getElementById("selected-songs-table").className = 'gsSelectedPopulated';
    } else {
        document.getElementById("selected-songs-table").className = 'gsSelectedEmpty';
    }
}


// Handles appending a widget/link to a user's comment
function gsAppendToComment(obj) {
    var songsArray = jQuery("input.songsInfoArrayClass");
    if (songsArray.length > 0) {
        obj.value = 'Saving...';
        obj.disabled = true;
        var songIDs = [];
        var songCount = 0;
        var songLimit = document.getElementById('gsCommentSongLimit').value;
        if (songLimit == 0) {
            songLimit = 99999;
        }
        var arrayLength = songsArray.length;
        for (var i = 0; i < arrayLength; i++) {
            if (songCount < songLimit) {
                songIDs[i] = songsArray[i].value;
            }
            songCount++;
        }
        var widgetHeight = document.getElementById('widgetHeight').value;
        widgetHeight = (widgetHeight < 1000) ? widgetHeight : 1000;
        if (document.getElementById('gsCommentHeight').value != 0) {
            widgetHeight = document.getElementById('gsCommentHeight').value;
        }
        var widgetWidth = document.getElementById('gsCommentWidth').value;
        var displayOption = document.getElementById('gsCommentDisplayOption').value; // either link or widget
        var colorScheme = document.getElementById('gsCommentColorScheme').value;
        debug(colorScheme);
        var colorArray = getBackgroundHex(colorScheme);
        debug(colorArray);
        var songContent = '';
        if (songIDs.length == 1) {
            //single song
            if (displayOption == 'widget') {
                // should add support for custom single-song themes
                songContent = getSingleGSWidget(songIDs[0], widgetWidth, 'metal');
            } else {
                var name = jQuery('.gsSong-' + songIDs[0] + ':first').attr('name');
                var songNameComplete = name.split('::')[0];
                var songName = songNameComplete.split(' by ')[0];
                var displayPhrase = document.getElementById('gsCommentDisplayPhrase').value;
                jQuery.post(document.getElementById('gsBlogUrl').value + '/wp-content/plugins/grooveshark/gsGetSongLink.php', {songID: songIDs[0]}, function(returnedData) {
                    songContent = "<a target=_blank' href='" + returnedData + "'>" + displayPhrase + ": " + songNameComplete + "</a>";
                    if (!!document.getElementById('comment')) {
                        document.getElementById('comment').value += songContent;
                    } else {
                        // Some themes move the comment value from id to name
                        var comment = jQuery("textarea[name='comment']");
                        if (comment.length) {
                            comment.val(comment.val() + songContent);
                        }
                    }
                    obj.value = 'Save Music';
                    obj.disabled = false;
                    gsDisplayStatusMessage('Your music is in your comment.');
                });
                return;
            }
        } else {
            if (displayOption == 'widget') {
                songContent = getPlaylistGSWidget(songIDs, widgetWidth, widgetHeight, colorArray[1], colorArray[0], colorArray[0], colorArray[2], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[2], colorArray[1]);
                
            } else {
                // People should not be able to link to multiple songs in the comments section, alert them about this
                alert('You currently can only add one songs to your comments. Please remove the extra songs and try again.');
                obj.value = 'Save Music';
                obj.disabled = false;
                return;
            }
        }
        if (!!document.getElementById('comment')) {
            document.getElementById('comment').value += songContent;
        } else {
            // Some themes move the comment value from id to name
            var comment = jQuery("textarea[name='comment']");
            if (comment.length) {
                comment.val(comment.val() + songContent);
            }
        }
        obj.value = 'Save Music';
        obj.disabled = false;
        gsDisplayStatusMessage('Your music is in your comment.');
    } else {
        // no songs available, notify the user
        gsDisplayStatusMessage('Please select songs to save to your comment.');
    }
}

function gsDisplayStatusMessage(message) {
    var gsStatusMessage = jQuery('#gsCommentStatusMessage');
    gsStatusMessage.show().html(message);
    setTimeout(function() {gsStatusMessage.fadeOut(3000, function() {gsStatusMessage.html('');});}, 3000);
}


function getSingleGSWidget(songID, width, theme) {
    return getGSWidgetEmbedCode('songWidget.swf', width, 40, [songID], 0, '', "&amp;style=" + theme);
}

function getSingleApWidget(songID) {
    return getGSWidgetEmbedCode('songWidget.swf', 1, 1, [songID], 1, '', '');
}

function getPlaylistGSWidget(songIDs, width, height, bt, bth, bbg, bfg, pbg, pfg, pbgh, pfgh, lbg, lfg, lbgh, lfgh, sb, sbh, si) {
    var colors = ['&amp;bt=', bt, '&amp;bth=', bth, '&amp;bbg=', bbg, '&amp;bfg=', bfg, '&amp;pbg=', pbg, '&amp;pfg=', pfg, '&amp;pbgh=', pbgh, '&amp;pfgh=', pfgh, '&amp;lbg=', lbg, '&amp;lfg=', lfg, '&amp;lbgh=', lbgh, '&amp;lfgh=', lfgh, '&amp;sb=', sb, '&amp;sbh=', sbh, '&amp;si=', si].join('');
    return getGSWidgetEmbedCode('widget.swf', width, height, songIDs, 0, colors, '');
}

function getGSWidgetEmbedCode(swfName, width, height, songIDs, ap, colors, theme) {
    var songIDString = '';
    if (songIDs.length == 1) {
        songIDString = 'songID=' + songIDs[0];
    } else {
        songIDString = 'songIDs=' + songIDs.join();
    }
    var ap = (+ap != 0) ? '&amp;p=' + ap : '';
    var embed = ["<object width='", width, "' height='", height, "'>",
                         "<param name='movie' value='http://listen.grooveshark.com/", swfName, "'></param>",
                         "<param name='wmode' value='window'></param>",
                         "<param name='allowScriptAccess' value='always'></param>",
                         "<param name='flashvars' value='hostname=cowbell.grooveshark.com&amp;", songIDString, theme, ap, colors, "'></param>",
                         "<embed src='http://listen.grooveshark.com/", swfName, "' type='application/x-shockwave-flash' width='", width, "' height='", height, "' flashvars='hostname=cowbell.grooveshark.com&amp;", songIDString, theme, ap, colors, "' allowScriptAccess='always' wmode='window'></embed>",
                    "</object>"].join('');
    return embed;
}

function getBackgroundHex(colorSchemeID) {
    var colorArray = new Array();
    switch (+colorSchemeID) {
        case 0:
            colorArray[0] = '000000';
            colorArray[1] = 'FFFFFF';
            colorArray[2] = '666666';
        break;

        case 1:
            colorArray[0] = 'CCA20C';
            colorArray[1] = '4D221C';
            colorArray[2] = 'CC7C0C';
        break;

        case 2:
            colorArray[0] = '87FF00';
            colorArray[1] = '0088FF';
            colorArray[2] = 'FF0054';
        break;

        case 3:
            colorArray[0] = 'FFED90';
            colorArray[1] = '359668';
            colorArray[2] = 'A8D46F';
        break;

        case 4:
            colorArray[0] = 'F0E4CC';
            colorArray[1] = 'F38630';
            colorArray[2] = 'A7DBD8';
        break;

        case 5:
            colorArray[0] = 'FFFFFF';
            colorArray[1] = '377D9F'
            colorArray[2] = 'F6D61F';
        break;

        case 6:
            colorArray[0] = '450512';
            colorArray[1] = 'D9183D';
            colorArray[2] = '8A0721';
        break;

        case 7:
            colorArray[0] = 'B4D5DA';
            colorArray[1] = '813B45';
            colorArray[2] = 'B1BABF';
        break;

        case 8:
            colorArray[0] = 'E8DA5E';
            colorArray[1] = 'FF4746';
            colorArray[2] = 'FFFFFF';
        break;

        case 9:
            colorArray[0] = '993937';
            colorArray[1] = '5AA3A0';
            colorArray[2] = 'B81207';
        break;

        case 10:
            colorArray[0] = 'FFFFFF';
            colorArray[1] = '009609';
            colorArray[2] = 'E9FF24';
        break;

        case 11:
            colorArray[0] = 'FFFFFF';
            colorArray[1] = '7A7A7A';
            colorArray[2] = 'D6D6D6';
        break;

        case 12:
            colorArray[0] = 'FFFFFF';
            colorArray[1] = 'D70860';
            colorArray[2] = '9A9A9A';
        break;

        case 13:
            colorArray[0] = '000000';
            colorArray[1] = 'FFFFFF';
            colorArray[2] = '620BB3';
        break;

        case 14:
            colorArray[0] = '4B3120';
            colorArray[1] = 'A6984D';
            colorArray[2] = '716627';
        break;

        case 15:
            colorArray[0] = 'F1CE09';
            colorArray[1] = '000000';
            colorArray[2] = 'FFFFFF';
        break;

        case 16:
            colorArray[0] = 'FFBDBD';
            colorArray[1] = 'DD1122';
            colorArray[2] = 'FFA3A3';
        break;

        case 17:
            colorArray[0] = 'E0DA4A';
            colorArray[1] = 'FFFFFF';
            colorArray[2] = 'F9FF34';
        break;

        case 18:
            colorArray[0] = '579DD6';
            colorArray[1] = 'CD231F';
            colorArray[2] = '74BF43';
        break;

        case 19:
            colorArray[0] = 'B2C2E6';
            colorArray[1] = '012C5F';
            colorArray[2] = 'FBF5D3';
        break;

        case 20:
            colorArray[0] = '60362A';
            colorArray[1] = 'E8C28E';
            colorArray[2] = '482E24';
        break;

        default:
            colorArray[0] = '000000';
            colorArray[1] = 'FFFFFF';
            colorArray[2] = '666666';
        break;
    }
    return colorArray;
}

// Player callback and helper functions

function toggleSong(currentPlayed) {
    if (!currentPlayed.name) {
        return false;
    }
    var songID = currentPlayed.name
    // Toggle the status for a song (play, pause, new song)
    var gsDataStore = jQuery('#gsDataStore').data('gsDataStore');
    var lastPlayed = gsDataStore.lastPlayed;
    if (typeof lastPlayed == 'boolean') {
        // initial song play
        gsDataStore.lastPlayed = lastPlayed = currentPlayed;
    } else {
        if (lastPlayed.name != currentPlayed.name) {
            // new song play
            var widgetEmbed = getSingleApWidget(songID);
            jQuery('#apContainer').empty().html(widgetEmbed);
            lastPlayed.className = 'gsPlay';
            currentPlayed.className = 'gsPause';
            gsDataStore.lastPlayed = currentPlayed;
            return;
        }
    }
    if (lastPlayed.parentNode.parentNode.parentNode.parentNode.id != currentPlayed.parentNode.parentNode.parentNode.parentNode.id) {
        // same song play, different play button
        lastPlayed.className = 'gsPlay';
        currentPlayed.className = 'gsPause';
        gsDataStore.lastPlayed = currentPlayed;
        return;
    }
    if (currentPlayed.className.indexOf('gsPlay') != -1) {
        // Play the song
        var widgetEmbed = getSingleApWidget(songID);
        jQuery('#apContainer').empty().html(widgetEmbed);
        currentPlayed.className = 'gsPause';
    } else {
        if (currentPlayed.className.indexOf('gsPause') != -1) {
            // stop the song
            currentPlayed.className = 'gsPlay';
            jQuery('#apContainer').empty();
        }
    }
    return false;
}
