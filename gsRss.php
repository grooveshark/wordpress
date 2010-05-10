<?php
        if (!empty($gs_options['sidebarRss']['favorites'])) {
            print $args['before_widget'] . $args['before_title'] . 
                  "<a class='rsswidget' title='Syndicate this content' href='{$gs_options['sidebarRss']['favorites']['url']}'>
                       <img width='14' height='14' alt='RSS' src='$wpurl/wp-includes/images/rss.png' style='border: medium none; background: orange none repeat scroll 0% 0%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: white;'/>
                   </a>
                   <a class='rsswidget' title='Last 100 favorited songs on Grooveshark by me' href='{$gs_options['sidebarRss']['favorites']['url']}'>
                       {$gs_options['sidebarRss']['favorites']['title']}
                   </a>"
                    . $args['after_title'];
            // RSS feed content goes here IF the user's wordpress has fetch_feed
            if (function_exists('fetch_feed')) {
                $favoritesFeed = fetch_feed($gs_options['sidebarRss']['favorites']['url']);
                if ($favoritesFeed instanceof WP_Error) {
                    // Display an error message to the visitor
                    print "<p>This feed is currently unavailable. Please try again later.</p>";
                    // Attempt to get the correct feed
                    $gs_options['sidebarRss']['favorites']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gs_options['username']) . "/recent_favorite_songs.rss";
                    update_option('gs_options', $gs_options);
                } elseif ($gs_options['sidebarRss']['count'] > 0) {
                    // Add the 
                    print "<ul>";
                    $count = 0;
                    $limit = $gs_options['sidebarRss']['count'];
                    $displayContent = $gs_options['sidebarRss']['displayContent'];
                    foreach ($favoritesFeed->get_items() as $item) {
                        $count++;
                        if ($count <= $limit) {
                            print "<li>
                                      <a class='rsswidget' target='_blank' title='{$item->get_description()}' href='{$item->get_permalink()}'>{$item->get_title()}</a>";
                            if ($displayContent) {
                                print "<div class='rssSummary'>{$item->get_description()}</div>";
                            }
                            print "</li>";
                        }
                    }
                    print "</ul>";
                }
            }
            print $args['after_widget'];
        }
        if (!empty($gs_options['sidebarRss']['recent'])) {
            print $args['before_widget'] . $args['before_title'] . 
                  "<a class='rsswidget' title='Syndicate this content' href='{$gs_options['sidebarRss']['recent']['url']}'>
                      <img width='14' height='14' alt='RSS' src='$wpurl/wp-includes/images/rss.png' style='border: medium none; background: orange none repeat scroll 0% 0%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: white;'/>
                   </a>
                   <a class='rsswidget' title='Last 100 song plays over 30 seconds on Grooveshark by me' href='{$gs_options['sidebarRss']['recent']['url']}'>
                       {$gs_options['sidebarRss']['recent']['title']}
                   </a> " 
                  . $args['after_title'];
            // RSS feed content goes here IF the user's wordpress has fetch_feed
            if (function_exists('fetch_feed')) {
                $recentFeed = fetch_feed($gs_options['sidebarRss']['recent']['url']);
                if ($recentFeed instanceof WP_Error) {
                    // Display an error message
                    print "<p>This feed is currently unavialable. Please try again later.</p>";
                    $gs_options['sidebarRss']['recent']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gs_options['username']) . "/recent_listens.rss";
                    update_option('gs_options', $gs_options);
                } elseif ($gs_options['sidebarRss']['count'] > 0) {
                    print "<ul>";
                    $count = 0;
                    $limit = $gs_options['sidebarRss']['count'];
                    $displayContent = $gs_options['sidebarRss']['displayContent'];
                    foreach ($recentFeed->get_items() as $item) {
                        $count++;
                        if ($count <= $limit) {
                            print "<li>
                                       <a class='rsswidget' target='_blank' title='{$item->get_description()}' href='{$item->get_permalink()}'>{$item->get_title()}</a>";
                            if ($displayContent) {
                                print "<div class='rssSummary'>{$item->get_description()}</div>";
                            }
                            print "</li>";
                        }
                    }
                    print "</ul>";
                }
            }
            print $args['after_widget'];
        }
?>
