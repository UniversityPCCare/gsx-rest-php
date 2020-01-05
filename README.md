About
=====

GSX-Rest is a PHP library that handles interactions with Apple's GSX REST API. It is a spiritual successor to [filipp/gsxlib][1],
which was used to interact with Apple's GSX SOAP API, which was retired in late 2019.

Requirements
===========

- PHP >7.0
- GSX Client Certificate and Configuration (see below)
- GSX account with "Web Services" privilege enabled in MyAccess (see below)


Usage
=====

After setting up `config\config.ini`:

    <?php
    
    require_once("../src/GSXHandler.php");
    
    $gsx = new UPCC\GSXHandler($gsxUserEmail. $gsxShipTo);
    $productDetails = $gsx->ProductDetails($serial);
    echo $productDetails->configDescription;
    > iPhone XS Max

GSX Client Certificate and Configuration
===

You will need to work with the GSX Web Support team to obtain the following:

  * A certificate bundle, signed by Apple (generally takes a few business days)
  * Your static IP(s) whitelisted (this may take up to a week)
  
Follow the initial setup guide in Apple's eServiceCentral to get started.


[1]: https://github.com/filipp/gsxlib
