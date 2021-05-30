# Kreuzmich Login
* Provides a cURL-based external authentification method against Kreuzmich Server
* based on https://github.com/nextcloud/user_external 
* Kreuzmich users are stored in an external users table
* Existing users who validate against the Nextcloud database kann still login with their old password, only recommended for admins 
* You can specify in settings if expired users or users that have not authenticate before should be allowed, as well es a default Nextcloud group for all users.
* PHP programmers can specify an additional function where another source or function can be specified to set groups for individual users. 

## Install and activate
* Place this app in **nextcloud/apps/**
* Activate this app via Nextcloud app list in GUI
* Navigate to Settings and Kreuzmich Login to specify your Kreuzmich city / subdomain

## Building the app
The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web
* npm: for building and testing everything JS, only required if a package.json is placed inside the **js/** folder

The make command will install or update Composer dependencies if a composer.json is present and also **npm run build** if a package.json is present in the **js/** folder. The npm **build** script should use local paths for build systems and package managers, so people that simply want to build the app won't need to install npm libraries globally, e.g.:

**package.json**:
```json
"scripts": {
    "test": "node node_modules/gulp-cli/bin/gulp.js karma",
    "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
    "build": "node node_modules/gulp-cli/bin/gulp.js"
}
```


## Publish to App Store

First get an account for the [App Store](http://apps.nextcloud.com/) then run:

    make && make appstore

The archive is located in build/artifacts/appstore and can then be uploaded to the App Store.

