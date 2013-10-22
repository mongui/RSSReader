RSS Reader
=========

The best way to read your feeds. Based on the MVCious framework, RSS Reader offers you the possibility to read your favorite blogs all together and in order. With its simple and clean interface it only takes 2 clicks to add a new feed to your account and start reading it. Forget the dozens of shortcuts you have in your bookmarks, they are not longer necessary.


## Installation ##
1. Download download it directly from github and uncompress it in your WWW directory or inside of one of its directories.
2. Import the rssreader.sql file to your SQL database.
3. Edit the /config.php and change any settings in it according to your needs.
4. If you have an Apache server installed and you want to use this web application in a different directory than /rssreader/, it's necessary to edit the next line from the .htaccess file:

   ```
   RewriteRule ^(.*)$ /rssreader/index.php?/$1 [L]
   ```
   To:
   ```
   RewriteRule ^(.*)$ /rssreader/index.php?/$1 [L]
   ```

## License ##
Code is open-sourced software licensed under Apache 2.0 License.