function debug(message) {
    if (window.console && console.log)
        return console.log(message);
    if (window.opera && opera.postError) {
        return opera.postError(message);
    }
}

/*
 * Sidebar setup functions
 */

jQuery(function() {
    jQuery('#myHiddenData').data('data', {selectedCount: {}, selectedSongsTable: {}, sidebarWidgetHeight: {}, apContainer: {}, searchWrapper: {}, queryResult: {}, singleWidgetPreview: {}, multiWidgetPreview: {}, lastPlayed: false, query: '', theme: 'metal', 'colorScheme': 0, widgetHeight: 176, widgetWidth:200});
    jQuery('#selected-songs-table').data('data', {stuff: 1, morestuff: 2});
    jQuery('#gs-query').data('data', {value: ''});
});

function getSelectedCount(data, context) {
    var selectedCount = {};
    if ((!!data.selectedCount) && (!!data.selectedCount[0])) {
        selectedCount = data.selectedCount;
    } else {
        selectedCount = jQuery('#selectedCount', context);
        data.selectedCount = selectedCount;
    }
    return selectedCount;
}

function getSelectedSongsTable(data, context) {
    var selectedTable = {};
    if ((!!data.selectedSongsTable) && (!!data.selectedSongsTable[0])) {
        selectedTable = data.selectedSongsTable;
    } else {
        selectedTable = jQuery('#selected-songs-table', context);
        data.selectedSongsTable = selectedTable;
        if (typeof(selectedTable.data('events')) == 'undefined') {
            setUpTableDnD(selectedTable);
        }
    }
    return selectedTable;
}

function setUpTableDnD(jtable) {
    /* The following code for dragging and dropping table rows was adapted from TableDND, created by Denis Howlet
     * You can find the original version at: http://www.isocra.com/
     */
    jtable.data('dnd', {table: jtable[0], oldY: 0, allRows: []});
    jtable.mousemove(function(ev) {
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
        var currentData = jtable.data('dnd');
        if (currentData.dragObject && currentData.allRows.length) {
            var allRows = currentData.allRows;
            for (var i = 0; i < allRows.length; i++) {
                allRows[i].className = (allRows[i].rowIndex % 2) ? 'gsTr1' : 'gsTr26';
            }
            currentData.dragObject = null;
            currentData.mouseOffset = null;
            currentData.oldY = 0;
            currentData.allRows = [];
        }
    });
    jtable.mousedown(function(ev) {
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
}    

function getSidebarWidgetHeight(data, context) {
    var sidebarWidgetHeight = {};
    if ((!!data.sidebarWidgetHeight) && (!!data.sidebarWidgetHeight[0])) {
        sidebarWidgetHeight = data.sidebarWidgetHeight;
    } else {
        sidebarWidgetHeight = jQuery('#gsSidebarWidgetHeight', context);
        data.sidebarWidgetHeight = sidebarWidgetHeight;
    }
    return sidebarWidgetHeight;
}

function getApContainer(data, context) {
    var apContainer = {};
    if ((!!data.apContainer) && (!!data.apContainer[0])) {
        apContainer = data.apContainer;
    } else {
        apContainer = jQuery('#apContainer', context);
        data.apContainer = apContainer;
    }
    return apContainer;
}

function getSearchResultsWrapper(data, context) {
    var searchWrapper = {};
    if ((!!data.searchWrapper) && (!!data.searchWrapper[0])) {
        searchWrapper = data.searchWrapper;
    } else {
        searchWrapper = jQuery('#search-results-wrapper', context);
        data.searchWrapper = searchWrapper;
    }
    return searchWrapper;
}

function getSingleWidgetPreview(data, context) {
    var widgetPreview = {};
    if ((!!data.singleWidgetPreview) && (!!data.singleWidgetPreview[0])) {
        widgetPreview = data.singleWidgetPreview;
    } else {
        widgetPreview = jQuery('#gsSingleWidgetPreview', context);
        data.singleWidgetPreview = widgetPreview;
    }
    return widgetPreview;
}

function getMultiWidgetPreview(data, context) {
    var widgetPreview = {};
    if ((!!data.multiWidgetPreview) && (!!data.multiWidgetPreview[0])) {
        widgetPreview = data.multiWidgetPreview;
    } else {
        widgetPreview = jQuery('#gsWidgetExample', context);
        data.multiWidgetPreview = widgetPreview;
    }
    return widgetPreview;
}

function getQueryResult(data, context) {
    var queryResult = {};
    if ((!!data.queryResult) && (!!data.queryResult[0])) {
        queryResult = data.queryResult;
    } else {
        queryResult = jQuery('#queryResult', context);
        data.queryResult = queryResult;
    }
    return queryResult;
}

function gsEnterSearch(obj) {
    gsSearch(jQuery('#gsSearchButton', obj.parentNode.parentNode.parentNode)[0]);
}

function gsSearch(obj) {
    obj.value = '...';
    obj.disabled = true;
    var random = Math.floor(Math.random()*10000);
    var myData = jQuery('#myHiddenData').data('data');
    var query = myData.query;
    
    var searchWrapper = getSearchResultsWrapper(myData, obj.parentNode.parentNode.parentNode);
    if (query != '') {
        // load the table containing the search results
        searchWrapper.load(document.getElementById('gsBlogUrl').value + "/wp-content/plugins/grooveshark/gsSearch.php?" + random, {query: query, sessionID: document.getElementById('gsSessionID').value, limit: document.getElementById('gsLimit').value, isVersion26: true, isSmallBox: 0, isSidebar: 1}, function(){
            var queryResult = getQueryResult(myData, obj.parentNode.parentNode.parentNode);
            if (searchWrapper.children().length > 0) {
                // Header for the search result table
                queryResult.html('Search results for "' + query + '":');
                                // Show results
                searchWrapper.add('#search-results-container').show();
            } else {
                queryResult.html('There was an error with your search. If this error persists, please contact the author.').add('#search-results-container').show();
            }
            // Revert buttons to inactive state
            obj.value = 'Search';
            obj.disabled = false;
        });
    }
    return false;
}

function gsUpdateValue(obj) {
    setTimeout(function() {
        jQuery('#myHiddenData').data('data').query = obj.value
    }, 150);
}

function gsClickSearch(obj) {
    jQuery(obj).keydown();
    jQuery('#gsSearchButton').click();
}

//Handles selecting a playlist for addition to the post.
function addToSelectedPlaylist(obj) {
    // prepare playlist info
    var myData = jQuery('#myHiddenData').data('data');
    var playlistSongs = obj.firstChild.innerHTML;
    var playlistID = obj.name;
    var playlistSongs = jQuery.parseJSON(playlistSongs);
    var context = obj.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode;
    var selectedTable = getSelectedSongsTable(myData, context);
    if ((!playlistSongs) || (playlistSongs.length == 0)) {
        // No songs available, display error message
        selectedTable.append('<tr class="temporaryError"><td></td><td>An unexpected error occurred while loading your playlist. Contact the author for support</td><td></td></tr>');
        setTimeout(function(){jQuery('.temporaryError').fadeOut('slow', function(){jQuery(this).remove();});}, 3000);
        return;
    }
    // prepare needed variables
    // Prepare widgetHeight for auto-adjust
    var sidebarWidgetHeight = getSidebarWidgetHeight(myData, context.parentNode);
    var widgetHeight = (+sidebarWidgetHeight.val());
    // Prepare the new song content
    var newSongContent = [];
    var count = selectedTable[0].rows.length;
    jQuery.each(playlistSongs, function(i, song) {
        if ((!!song.songNameComplete) && (!!song.songID)) {
            count++;
            newSongContent.push("<tr class='", (count % 2 ? 'gsTr26' : 'gsTr1'), " newRow'><td class='gsTableButton'><a title='Play this song' class='gsPlay' onclick='toggleSong(this)' name='", song.songID, "' style='cursor: pointer;'></a></td><td>", song.songNameComplete, "<input type='hidden' name='songsInfoArray[]' class='songsInfoArrayClass' value='", song.songID, "'/></td><td class='gsTableButton'><a title='Remove This Song' class='gsRemove' onclick='removeFromSelected(this)' style='cursor: pointer; float: right;'></a></td></tr>");
            if (widgetHeight < 1000) {
                widgetHeight += 22;
            }
        }
    });
    gsUpdateSidebarHeight(sidebarWidgetHeight[0]);
    selectedTable.append(newSongContent.join(''));
    updateCount(context);
    sidebarWidgetHeight.val(widgetHeight);
}

//Handles selecting a song for addition to the post.
function addToSelected(obj) {
    var songInfo = obj.name;
    var temp = [];
    temp = songInfo.split("::");
    songNameComplete = temp[0];
    songID = temp[1];
    if (songNameComplete && songID) {
        // Prepare the table with all selected songs
        var myData = jQuery('#myHiddenData').data('data');
        var context = obj.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode;
        var selectedTable = getSelectedSongsTable(myData, context);
        // Alternating table styles
        var className = 'gsTr26';
        var tableLength = selectedTable[0].rows.length;
        if (tableLength % 2) {
            className = 'gsTr1';
        }
        // Prepare the row with the selected song
        var rowContent = "<tr class='"+className+"'><td class='gsTableButton'><a title='Play This Song' class='gsPlay' onclick='toggleSong(this)' name='"+songID+"' style='cursor: pointer;'></a></td><td>"+songNameComplete+"<input type='hidden' name='songsInfoArray[]' class='songsInfoArrayClass' value='"+songID+"'/></td><td class='gsTableButton'><a title='Remove This Song' class='gsRemove' onclick='removeFromSelected(this)' style='cursor: pointer; float: right;'></a></td></tr>";
        selectedTable.append(rowContent);
        // auto-adjust the widget height for the new number of songs
        var widgetHeight = getSidebarWidgetHeight(myData, context.parentNode);
        if (widgetHeight.val() < 1000) {
            widgetHeight.val((+widgetHeight.val()) + 22);
        }
        gsUpdateSidebarHeight(widgetHeight[0]);
        updateCount(context);
    }
}

// Handles showing all playlist songs before adding to post
function showPlaylistSongs(obj) {
    var playlistID = obj.name;
    jQuery('.child-'+playlistID, obj.parentNode.parentNode.parentNode).toggle();
    jQuery(obj).attr('class', 'gsHide').attr('title', 'Hide All Songs In This Playlist').unbind('click').attr('onclick', '').click(function() {hidePlaylistSongs(this);});
}

function hidePlaylistSongs(obj) {
    var playlistID = obj.name;
    jQuery('.child-'+playlistID, obj.parentNode.parentNode.parentNode).toggle();
    jQuery(obj).attr('class', 'gsShow').attr('title', 'Show All Songs In This Playlist').unbind('click').attr('onclick', '').click(function() {showPlaylistSongs(this);});
}

//Clears all songs that are selected for addition.
function clearSelected(obj) {
    var myData = jQuery('#myHiddenData').data('data');
    getSelectedSongsTable(myData, obj.parentNode.parentNode).empty();
    getSidebarWidgetHeight(myData, obj.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode).val(176);
    updateCount(obj.parentNode);
}

//Handles unselecting a song for addition.
function removeFromSelected(element) {
    var myData = jQuery('#myHiddenData').data('data');
    var context = element.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode;
    var selectedTable = getSelectedSongsTable(myData, context);
    var widgetHeight = getSidebarWidgetHeight(myData, context.parentNode);
    var lastPlayed = myData.lastPlayed;
    var currentPlayed = jQuery('.gsPlay, .gsPause', element.parentNode.parentNode.parentNode)[0];
    if (lastPlayed.name == currentPlayed.name) {
        // currently played song is deleted, stop playback
        getApContainer(myData, context.parentNode).empty();
        myData.lastPlayed = false;
    }

    // Just remove the song's row, adjust widget height as necessary, and update selected song count
    jQuery(element.parentNode.parentNode).remove();
    if ((+widgetHeight.val()) > 176) {
        widgetHeight.val((+widgetHeight.val()) - 22);
    }
    gsUpdateSidebarHeight(widgetHeight[0]);
    selectedTable.find('tr:odd').attr('class', 'gsTr1');
    selectedTable.find('tr:even').attr('class', 'gsTr26');
    updateCount(context);
}

function updateCount(context) {
    var myData = jQuery('#myHiddenData').data('data');
    var selectedCount = getSelectedCount(myData, context);
    var selectedTable = getSelectedSongsTable(myData, context);
    var selectedCountValue = selectedTable[0].rows.length;
    selectedCount.html('Selected Songs (' + selectedCountValue + '):');
    if (selectedCountValue > 0) {
        selectedTable[0].className = 'gsSelectedPopulated';
    } else {
        selectedTable[0].className = 'gsSelectedEmpty';
    }
}

function changeSidebarColor(obj) {
    if (!obj) {
        return false;
    }
    var curValue = (+obj.options[obj.selectedIndex].value);
    var myData = jQuery('#myHiddenData').data('data');
    if (curValue != myData.colorScheme) {
        myData.colorScheme = curValue;
        var context = jQuery(obj.parentNode.parentNode.parentNode);
        var colorArray = getBackgroundHex(curValue);
        jQuery('#widget-base-color', context)[0].style.backgroundColor = '#' + colorArray[0];
        jQuery('#widget-primary-color', context)[0].style.backgroundColor = '#' + colorArray[1];
        jQuery('#widget-secondary-color', context)[0].style.backgroundColor = '#' + colorArray[2];
        gsUpdateMultiPreview(myData, obj);
    }
}

function changeSidebarTheme(obj) {
    var curValue = obj.options[obj.selectedIndex].value;
    var myData = jQuery('#myHiddenData').data('data');
    if (curValue != myData.theme) {
        myData.theme = curValue;
        gsUpdateSinglePreview(myData, obj);
    }
}

function gsUpdateSidebarWidth(obj) {
    var myData = jQuery('#myHiddenData').data('data');
    myData.widgetWidth = +obj.value;
    gsUpdateSinglePreview(myData, obj);
    gsUpdateMultiPreview(myData, obj);
}

function gsUpdateSidebarHeight(obj) {
    var myData = jQuery('#myHiddenData').data('data');
    myData.widgetHeight = +obj.value;
    gsUpdateMultiPreview(myData, obj);
}

function gsUpdateSinglePreview(myData, obj) {
    var songID = '203993'; // Stay on Your Toes by Del the Funky Homosapien
    var songsArray = jQuery('input.songsInfoArrayClass');
    if (songsArray.length == 1) {
        // single song available in selected song, use that for preview instead
        songID = songsArray[0].value;
    }
    var embed = getSingleGSWidget(songID, myData.widgetWidth, myData.theme);
    getSingleWidgetPreview(myData, obj.parentNode.parentNode.parentNode).html(embed);
}

function gsUpdateMultiPreview(myData, obj) {
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
    var colorArray = getBackgroundHex(myData.colorScheme);
    var widgetWidth = myData.widgetWidth;
    var widgetHeight = myData.widgetHeight;
    var embed = getPlaylistGSWidget(songIDs, widgetWidth, widgetHeight, colorArray[1], colorArray[0], colorArray[0], colorArray[2], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[0], colorArray[2], colorArray[1], colorArray[1], colorArray[2], colorArray[1]);
    getMultiWidgetPreview(myData, obj.parentNode.parentNode.parentNode).html(embed);
}

//Toggles whether the user is shown the search, their favorites, or their playlists.
function gsToggleSongSelect(myRow){
    context = myRow.parentNode.parentNode.parentNode;
    var isQueryEmpty = (jQuery('#queryResult', context).html() == '');
    switch (myRow.innerHTML) {
        case 'Search':
            jQuery('#playlists-option', context).attr('class', 'gsTabInactive27');
            jQuery('#songs-playlists', context).hide();
            jQuery('#favorites-option', context).attr('class', 'gsTabInactive27');
            jQuery('#songs-favorites', context).hide();
            jQuery('#search-option', context).attr('class', 'gsTabActive26');
            jQuery('#songs-search', context).show();
            if (!isQueryEmpty) {
                jQuery('#search-results-container', context).show();
            }
            break;

        case 'Favorites':
            jQuery('#playlists-option', context).attr('class', 'gsTabInactive27');
            jQuery('#songs-playlists', context).hide();
            jQuery('#search-option', context).attr('class', 'gsTabInactive27');
            jQuery('#songs-search', context).hide();
            jQuery('#search-results-container', context).hide();
            jQuery('#favorites-option', context).attr('class', 'gsTabActive26');
            jQuery('#songs-favorites', context).show();
            break;

        case 'Playlists':
            jQuery('#favorites-option', context).attr('class', 'gsTabInactive27');
            jQuery('#songs-favorites', context).hide();
            jQuery('#search-option', context).attr('class', 'gsTabInactive27');
            jQuery('#songs-search', context).hide();
            jQuery('#search-results-container', context).hide();
            jQuery('#playlists-option', context).attr('class', 'gsTabActive26');
            jQuery('#songs-playlists', context).show();
            break;

        default:
            break;
    }
}


// Player callback and helper functions

function toggleSong(currentPlayed) {
    if (!currentPlayed.name) {
        return false;
    }
    var songID = currentPlayed.name
    // Toggle the status for a song (play, pause, new song)
    var gsDataStore = jQuery('#myHiddenData').data('data');
    var lastPlayed = gsDataStore.lastPlayed;
    var apContainer = getApContainer(gsDataStore, currentPlayed.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode);
    if (typeof lastPlayed == 'boolean') {
        // initial song play
        gsDataStore.lastPlayed = lastPlayed = currentPlayed;
    } else {
        if (lastPlayed.name != currentPlayed.name) {
            // new song play
            apContainer.empty().html(getSingleApWidget(songID));
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
        apContainer.empty().html(getSingleApWidget(songID));
        currentPlayed.className = 'gsPause';
    } else {
        if (currentPlayed.className.indexOf('gsPause') != -1) {
            // stop the song
            currentPlayed.className = 'gsPlay';
            apContainer.empty();
        }
    }
    return false;
}

// Widget functions

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

/* Code for jquery.json plugin */
(function($){var m={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'},s={'array':function(x){var a=['['],b,f,i,l=x.length,v;for(i=0;i<l;i+=1){v=x[i];f=s[typeof v];if(f){v=f(v);if(typeof v=='string'){if(b){a[a.length]=',';}a[a.length]=v;b=true;}}}a[a.length]=']';return a.join('');},'boolean':function(x){return String(x);},'null':function(x){return"null";},'number':function(x){return isFinite(x)?String(x):'null';},'object':function(x){if(x){if(x instanceof Array){return s.array(x);}var a=['{'],b,f,i,v;for(i in x){v=x[i];f=s[typeof v];if(f){v=f(v);if(typeof v=='string'){if(b){a[a.length]=',';}a.push(s.string(i),':',v);b=true;}}}a[a.length]='}';return a.join('');}return'null';},'string':function(x){if(/["\\\x00-\x1f]/.test(x)){x=x.replace(/([\x00-\x1f\\"])/g,function(a,b){var c=m[b];if(c){return c;}c=b.charCodeAt();return'\\u00'+Math.floor(c/16).toString(16)+(c%16).toString(16);});}return'"'+x+'"';}};$.parseJSON=function(v,safe){if(safe===undefined)safe=$.parseJSON.safe;if(safe&&!/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/.test(v))return undefined;return eval('('+v+')');};$.parseJSON.safe=false;})(jQuery);
