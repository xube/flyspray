; <?php die( 'Do not access this page directly.' ); ?>
; This is the Flysplay configuration file. It contains the basic settings
; needed for Flyspray to operate. All other preferences are stored in the
; database itself and are managed directly within the Flyspray admin interface.
; You should consider putting this file somewhere that isn't accessible using
; a web browser, and editing header.php to point to wherever you put this file.

[general]
basedir = "/var/www/flyspray/"  ; Location of your Flyspray installation
cookiesalt = "4t"               ; Randomisation value for cookie encoding
adodbpath = "/usr/share/adodb/adodb.inc.php"  ; Path to the main ADODB include
jpgraphpath = "/usr/share/jpgraph.php"  ; Path to the main JPGraph include

[database]
dbtype = "mysql"                 ; Type of database ('mysql' or 'pgsql') 
dbhost = "localhost"             ; Name or IP of your database server
dbname = "flyspray"              ; The name of the database
dbuser = "USERNAME"              ; The user to access the database
dbpass = "PASSWORD"              ; The password to go with that username above
