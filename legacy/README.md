## Installation Guide
### First steps
  - clone this repository to a local storage
  - copy config/config.php.tpl to /config/config.php
  - install a LAMP Server 
### Webserver
#### Built in PHP Webserver
For Developing purpose it is recomended to use the built-in php webserver. 
Therefore lets say /var/www is your public webroot just use 
```
$ cd /var/www/
$ ln -s /path/to/FinanzAntragUI/www/ nameOfSubdirectory
$ php -S localhost:8000 
```
and visit http://localhost:8000/nameOfSubdirectory/
#### Apache 
Linux: link /www to your webroot (make sure followSymlinks is activated)
Windows (or without Linking): add to Apache httpd.conf
    
    <Directory "C:/absolut/path/to/www">
        Options Indexes FollowSymLinks Includes ExecCGI
        AllowOverride All
        Require all granted
    </Directory>
    Alias /FinanzAntragUI "C:/absolut/path/to/www"

Anmerkung: statt /FinanzAntragUI kann auch ein anderer url-suffix verwendet werden (oder keiner)
#### nginx
see /nginx.conf.example 
### PHP 
install PHP7.0+ is required - big changes would be needed for 5.4
#### XMLPR2 Client
Make sure to have latest Version of XMLRPC 2 Client from PEAR installed

    pear install XML_PRC2
    
If installation (with php 7.2+) has an error may check https://www.dotkernel.com/php-troubleshooting/fix-installing-pear-packages-with-php-7-2/

In further updates Pear XML_RPC will not be used anymore
### Datenbank
Mysql or MariaDB are recommended - it may works also with others (as long as dialect is the same)

    MySQL -> Working
    Tested with 0.1.38-MariaDB -> not working

Generate User and Database and plug in the credentials at config/config.php (see config/config.php.tpl for example)

Set (in config/config.php)
 
    BUILD_DB => true,
    
on first startup to generate the database tables. Therefore you have to startup www/index.php once. If they are any errors please try to fix them (and report them) and reload the webpage again.

For better performance you should deactivate this option afterwards again.

### Config
Set 

    define("SAML", XXX);
    
replace XXX with true if you want to use SAML (SimpleSAMLphp) for authentication. Otherwise false (for local testing only).
Other authentications could be implemented with a new AuthHandler - therefore it has to implement the methods from lib/class.AuthHandler.php
Small changes in index.php may be necessary to use the new Handler instead of SAML. 