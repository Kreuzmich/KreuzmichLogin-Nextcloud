# Kreuzmich Login
* Provides a cURL-based external authentification method against Kreuzmich Server
* based on https://github.com/nextcloud/user_external 
* Kreuzmich users are stored in an external users table
* Existing users who validate against the Nextcloud database kann still login with their old password, only recommended for admins 
* You can specify in settings if expired users or users that have not authenticate before should be allowed, as well es a default Nextcloud group for all users.
* PHP programmers can specify an additional function where another source or function can be specified to set groups for individual users. 

## Configuration
You must add the following configuration parameters in your config.php. 
Apart from necessary configuration it also contains several settings. These will be overwritten by your choices in the GUI app settings after activation. 

```php
'user_backends' => 
  array (
    0 => 
    array (
      'class' => 'OC_User_Kreuzmich',
      'arguments' => 
      array (
        0 => 'duesseldorf',
        1 => '',
        2 => '',
        3 => false,
        4 => true,
        5 => 'All users',
      ),
    ),
  ),
  ```

In the code above, the array "arguments" are equivalent to the arguments function arguments of the constructor in /lib/kreuzmich.php in the following order:

1. Kreuzmich city/subdomain ('duesseldorf', 'koeln', ...)
2. HTTP auth user ('' by default)
3. HTTP auth password ('' by default)
4. allow Kreuzmich users that are expired in Kreuzmich (false or true)
5. allow new users that have never logged into Nextcloud before (false or true)
6. Default Nextcloud group that __every__ user is put into (mind spelling, must be an already existing group in Nextcloud)

Please mind that following:
1. copy the code exactly as below
2. only change the arguments right to the __=>__ at points 0 to 5 starting in the 7th line.
3. settings parameters below are examples
4. all arrays have to start with 0 
5. don't forget comma after the arguments and all closing brackets at the ed
6. 0, 1,2 and 4 need __'__ before and after the word
7. __Configuration settings from your config.php are not automatically stored in your database and should be manually set in the GUI app settings again__. Otherwise every person with file access can change your login settings.

## Install and activate
* Configurate first (see above)
* Place this app in **nextcloud/apps/**
* Activate this app via Nextcloud app list in GUI
* Navigate to Settings and Kreuzmich Login to specify your Kreuzmich settings

## Overwrite bugged settings
* You need database access, e.g. Adminer or phpmyadmin
* refer to your config.php to find out your database name and password
* also backup your database
* when in your Nextcloud database, delete the saved settings using this MySQL command (your table prefix of the table appconfig may differ, default is oc_)
```
DELETE FROM `oc_appconfig` WHERE (`appid` = 'kreuzmichlogin' AND `configkey` = 'settings')
```
* now you can edit your config.php as described under configuration above to specify new settings to log into Nextcloud.
