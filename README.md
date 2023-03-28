# Laravel  Quickbooks Desktop 


Following the instructions below accordingly in other  to integrate and use this package in your laravel project.


# Installation

   ## Require the QuickBooks PHP DevKit (QuickBooks integration support)

    composer require "consolibyte/quickbooks:dev-master"

  ## Then require this package using :

    composer require sylvester/quickbooks

## Next add the below to your .env :

     QB_DSN=
     QB_USERNAME=quickbooks
     QB_PASSWORD=password
     QB_TIMEZONE=America/New_York
     QB_LOGLEVEL=QUICKBOOKS_LOG_DEVELOP
     QB_MEMLIMIT=512M
     QB_SOAPSERVER=QUICKBOOKS_SOAPSERVER_BUILTIN
     QB_QUICKBOOKS_CONFIG_LAST=last
     QB_QUICKBOOKS_CONFIG_CURR=curr
     QB_QUICKBOOKS_MAX_RETURNED=100
     QB_PRIORITY_ITEM=0
     QB_QUICKBOOKS_MAILTO=support@onehealthng.com


  ### NOTE: Your database connection in your .env needs to be set.

# ADD the following be to your Config/app  under providers:

     
     Sylvester\Quickbooks\Providers\QuickbookdProvider::class
     
     
# After that then run the following below: 

    php artisan vendor:publish --tag=config

# Then visit your the url below to see if it works.

    https://yourappurl/qbd-webconnector/qbwc

















