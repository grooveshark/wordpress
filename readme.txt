=== Grooveshark for Wordpress ===
Contributors: Grooveshark
Tags: music, Grooveshark, play, Post, posts, tinysong, sidebar, widget
Requires at least: 2.6
Tested up to: 3.0
Stable tag: 1.4.1

The Grooveshark plugin allows you to insert music links or Grooveshark Widgets into your blog.

== Description ==

The Grooveshark plugin is a Wordpress plugin that allows you to insert a link to music on [Grooveshark](http://www.grooveshark.com "Grooveshark") or a [Grooveshark Widget](http://widgets.grooveshark.com "Grooveshark Widget") that allows visitors to play music as they view your blog.

Grooveshark is a music website that allows visitors to listen to music and share music with friends. Registered users can also save favorite songs and playlists on Grooveshark. With millions of songs contributed by Grooveshark users, you can find just about any song to share with your friends and blog visitors.

The Grooveshark plugin utilizes the Grooveshark API to bring Grooveshark to your blog. Some of the main features are:

* Easily find and add any song on Grooveshark to your posts, in link or widget format, as you write your post
* If you are a registered Grooveshark user, find and add your favorite songs and your playlists to your posts
* Preview songs while you edit with no need to open external pages
* Add Grooveshark widgets to your blog's sidebar or admin dashboard
* Add RSS feeds to your blog's sidebar that share your favorites and recently listened songs with your blog visitors
* Allow your blog visitors to add music to post comments

What's New in 1.4:

* Adding music to your sidebar is now much easier with the new Grooveshark Sidebar options.
* You can now preview widgets before adding them to your posts or sidebar under Appearance options.
* Fixed a bug where some songs could not be added as links

== Installation ==

1. Upload the Grooveshark folder to your plugins ('/wp-content/plugins/') directory.
2. Activate the plugin from your Plugins page.
3. (Optional) Enter your Grooveshark username and password in the settings page to access your favorite songs and playlists. Your login information will remain secure.
4. Add music to your blog. The Add Music box will appear below the post content when you edit your posts. The Add Music box will also appear in the widget options for the Grooveshark Sidebar.

== Frequently Asked Questions ==

= Who can I contact for support? =

Send all support questions to: roberto.sanchez [at] escapemg.com. You can also visit [Grooveshark Help](http://help.grooveshark.com "Grooveshark Help").

= What do I need for the Grooveshark plugin? =

The requirements to use the Grooveshark Wordpress plugin are:

* Standard Wordpress requirements for version 2.6 and higher.
* Curl enabled in PHP.
* Javascript enabled in browser.
* PHP 5 or greater

Optional requirements are:

* Flash version 9.0.0 or higher needed for in-edit music playback.

Requirements for blog visitors are:

* Flash version 9.0.0 or higher needed to play Grooveshark widgets and music on Grooveshark.com (Flash version 10 and higher recommended).

= Do I need to register a Grooveshark account? =

You only need to register a [Grooveshark](http://www.grooveshark.com "Grooveshark") account if you want to add music from your favorite songs and your playlists on Grooveshark, and if you want to link to more than one song on Grooveshark. The plugin will notify you if you choose these options, but didn't enter your username and password from Grooveshark.

= Why does the Grooveshark Plugin slow the load time for my edit pages? =

When you first provide your login information in the Grooveshark Settings page, the plugin will retrieve all your playlists and their songs from Grooveshark. If you have many playlists and many songs saved to those playlists, this process may take from several seconds to complete. However, after the initial retrieval of your playlists, all playlist information is stored by the plugin locally, reducing the need to retrieve playlist information. Once the playlist information is saved, your edit page load times should return to what they were before.

= When I install the plugin, I get a fatal error: call to undefined function curl_init(). Why is this happening? =

The curl functions are included with PHP starting with version 4.0.2 and later. If you have an older PHP version, you will need to upgrade.
Additionally, for users running their blogs on XAMPP, curl may not be enabled by default. You will need to enable curl in your php.ini file by uncommenting the line extension=php_curl.dll. More information can be found [here](http://chrismeller.com/2007/04/using-curl-in-xampp "here.")
Finally, it may be possible that your webhost has curl disabled for security reasons. Many of these webhosts offer curl support for a higher hosting fee, so you will need to talk with your webhost about your options.

= What benefits does the Grooveshark for Wordpress Plugin bring to my blog? =

The plugin allows you to bring Grooveshark and it's extensive music library directly to your blog. Several admin tools allow you to easily add music to any post and page, add a Grooveshark Widget to your Wordpress sidebar or dashboard, and even give your blog visitors the ability to share music with everyone who reads your blog.

The services provided with this plugin are great for music blogs. Instead of hosting music files yourself, you can let Grooveshark take care of serving music to your blog visitors. Insert a Grooveshark Widget or a link to a song on Grooveshark, and Grooveshark will take care of the rest. Having music easily accessible in your blog will bring value to your blog and give your visitors another reason to keep coming back. Best of all, it's all free.

= How do I let my blog visitors share music? =

The plugin comes with an option to allow blog visitors to add music to comments they make on your blog. Once you enable this option, visitors will normally find an "Add Music To Your Comment" section below the comment edit textarea, and here they will be able to search for any song on Grooveshark and add music to their comments.

To enable music comments, go to Grooveshark Settings (under the settings tab in admin navigation for Wordpress 2.8 and later) and click the Enable button next to the "Allow Music Comments" option. Once you have enabled music comments, you can customize how visitors can add music to their comments. You can decide whether they can embed Grooveshark Widgets or links to music on Grooveshark. You can also set widget width and height for comment-embedded Grooveshark widgets. For links to songs on Grooveshark, you can customize the introductory phrase for the link and the playlist name that will show on the link. You can choose from 20 widget color schemes to have the Grooveshark Widgets blend perfectly into your blog. Finally, you can set a limit on how many songs visitors can embed into their comments.

Note that your Wordpress theme template must support comments or have comments enabled for your visitors to use music comments. Also note that your blog visitors may not link to playlists, as this would require login information (and you don't want to have someone else's playlists mixed with your playlists on Grooveshark).

= How can I add a Grooveshark Widget to my Wordpress sidebar or dashboard? =

As of the release of Grooveshark for Wordpress version 1.2.0, you now have the ability to add music to your wordpress sidebar and your wordpress dashboard as easily as you can add music to your posts.

To add music to your dashboard, simply check the option to add music to your dashboard under the Apperance Options in the Add Music box when you add music to your posts.

To add music to your sidebar, go to the Widgets page on your Wordpress Admin. Drag Grooveshark Sidebar from your Available Widgets to a sidebar. Once you drop Grooveshark Sidebar in a sidebar, an Add Music box will appear that allows you to choose which songs to add. You can search for music, or add music from your favorites and playlists if you provided your Grooveshark login information on the Grooveshark Settings page. You can then customize the appearance of single-song and playlist widgets under Appearance Options. Finally, you can click Save to save the songs as a Grooveshark Widget to your Wordpress sidebar.

Note that you can remove music from your sidebar by checking the Clear Sidebar checkbox under Appearance Options for the Groooveshark Sidebar, or by clicking Save with no music selected.

= How can I add RSS feeds of my music to my blog sidebar? =

Once you install the plugin, you will find a Grooveshark RSS widget when you go to the admin Widget page. Simply drag this widget to one of your sidebars. You will have the option of showing your favorite songs feed and your recently listened songs feed. Any visitor to your blog can then subscribe to your feeds. The feeds are updated periodically as you listen to songs and add songs to your favorites, so anyone subscribe to your feeds will be able to share your taste in music even as it changes.

Note that some wordpress installations are incompatible with the Grooveshark RSS feature of the Grooveshark for Wordpress plugin. As a result, Grooveshark RSS is now disabled by default, and must be enabled via the plugin's Settings page for Grooveshark RSS to appear as a widget.

= Why does the Grooveshark Widget disrupt the appearance of my blog? =

If you choose to add a Grooveshark Widget to your Wordpress sidebar, it is recommended that you keep the widget width to 200px, since this width fits best with the default Wordpress theme and with sidebar width for a majority of Wordpress themes. If a Grooveshark Widget disrupts the appearance of your blog from your sidebar, decrease the width of the widget, but do keep in mind that the minimum width is 150px.

If you make the widget too large, it could also disrupt the appearance of your blog posts when you add a widget to a post. The maximum length and width of a widget are 1000 pixels each, but you may want to keep the dimensions small, particularly width, to ensure that the Grooveshark Widgets do not disrupt the layout and appearance of your blog.

= How will the Grooveshark Plugin work with multiple blogs? =

Starting with Wordpress 3.0, Wordpress has been merged with Wordpress MU, allowing anyone using Wordpress to host multiple blogs using one single installation of Wordpress. With multiple blogs enabled, the biggest difference you will notice is that all plugins, including Grooveshark for Wordpress, now have a "network activate" option, which activates the plugin for all blogs you are hosting. The only other major difference is that the plugin options will be stored separately for each blog hosted under your installation of Wordpress. This means you will have to provide your login information for each blog if you wish to use Favorites and Playlists on each blog, or if separate users are allowed to manage each blog, they can provide their own login information on their respective blogs. This also applies to the other Grooveshark for Wordpresss settings.

== Screenshots ==

1. Grooveshark for Wordpress Add Music Panel
2. Grooveshark for Wordpress Administration Panel
3. Grooveshark for Wordpress Post Widget
