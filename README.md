A [YouRLs](http://yourls.org/) plugin to queue clicks to Amazon SimpleDB before processing. This allows using a regular MySQL store even in the face of a high frequency of writes, without concern of connection limit overflow. Clicks are inserted later into the database via an import job.

Install
=======

Ensure that the AWS PHP Client is installed: http://aws.amazon.com/sdkforphp/

To install the plugin itself copy the plugins/sdblog folder to your plugins folder (usually user/plugins).

Configuration
=======

To access SimpleDB you will need to include your AWS key and secret key, available from the AWS console. These should be defined in your YouRLs config.php

    define('AWS_KEY', 'KEY_GOES_HERE');
    define('AWS_SECRET_KEY', 'SECRET_KEY_HERE');
    
You can additionally define the region to run in and the domain (database name) for SimpleDB with the following parameters: 

    define('SDB_REGION', AmazonSDB::REGION_EU_W1);
    define('SDB_DOMAIN', 'myqueuetable');
    
Usage
======

The plugin will automatically shunt clicks onto a SimpleDB call. At appropriate points you are 
required to run the import.php script in order to pull the click data into the main YouRLS database
for use in generating statistics. This can be done from a cron script, for example

    */5 * * * * php /var/www/user/plugins/sdblog/import.php 2>&1 /dev/null
    
The timing of the script will depend on your need for accuracy in statistics versus tolerance for database writes.
