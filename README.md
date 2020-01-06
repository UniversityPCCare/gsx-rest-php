About
=====

GSX-Rest is a PHP library that handles interactions with Apple's GSX REST API. It is a spiritual successor to [filipp/gsxlib][1] 
used to interact with Apple's GSX SOAP API, which was retired in late 2019.

Requirements
===========

- PHP >7.0
- MySQL (for authorization token persistence)
- GSX Client Certificate and Configuration (see below)
- GSX account with "Web Services" privilege enabled in MyAccess (see below)

Installation
=====

- Clone the repo using `git clone https://github.com/UniversityPCCare/gsx-rest-php.git`
- Setup a MySQL instance if you haven't already. Create a database (default database name is `gsxrest`) and
associate a user with it. Alternatively, in `config/config.ini` you can provide a MySQL user with sufficient
privilege to create databases and the database will be created for you.
- Rename `config/config.ini.example` to `config/config.ini` and set default configuration values
- (Optional) Rename `tests/test_declarations.php.example` to 'tests/test_declaration.php` and setup
variables within. This is only necessary if you choose to run `tests/test.php` to verify your setup is working.
- (Optional) After setting up `test_declarations.php`, `cd tests` and run `php ./test.php`
- Upon first run, the database will be created for you (if you hadn't made it already), as well as the
tables within.

Usage
=====

After setting up `config\config.ini`:

    <?php
    
    require_once("../src/GSXHandler.php");
    
    $gsx = new UPCC\GSXHandler($gsxUserEmail. $gsxShipTo);
    $productDetails = $gsx->ProductDetails($serial);
    echo $productDetails->configDescription;
    > iPhone XS Max

Implemented Endpoints
===

- [x] `/authenticate/check`
- [x] `/authenticate/token`
- [ ] `/authenticate/end-session`

- [x] `/repair/summary`
- [x] `/repair/details`
- [ ] `/repair/eligibility`
- [ ] `/repair/update`
- [x] `/repair/audit`
- [ ] `/repair/product/serializer`
- [ ] `/repair/questions`
- [ ] `/repair/loaner/return`
- [ ] `/repair/create`
- [ ] `/repair/product/componentissue`
- [x] `/repair/product/details`
- [ ] `/repair/product/serializer/lookup`

- [x] `/diagnostics/suites`
- [ ] `/diagnostics/initiate-test`
- [ ] `/diagnostics/lookup`
- [ ] `/diagnostics/customer-report-url`
- [ ] `/diagnostics/status`

- [ ] `/consignment/validate`
- [ ] `/consignment/delivery/acknowledge`
- [ ] `/consignment/order/shipment`
- [ ] `/consignment/order/lookup`
- [ ] `/consignment/delivery/lookup`
- [ ] `/consignment/order/submit`

- [x] `/content/article`
- [ ] `/content/article/lookup`

- [ ] `/document-download` (POST)
- [ ] `/document-download` (GET)
- [ ] `/attachment/upload-access`
- [ ] `/parts/summary`
- [x] `/technician/lookup`

GSX Client Certificate and Configuration
===

You will need to work with the GSX Web Support team to obtain the following:

  * A certificate bundle, signed by Apple (generally takes a few business days)
  * Your static IP(s) whitelisted (this may take up to a week)
  
Follow the initial setup guide in Apple's eServiceCentral to get started.

GSX account with "Web Services" privilege enabled
===

You (or anyone else with admin access to MyAccess) will need to provision at least one account
in MyAccess to have the "Web Services" privilege for GSX.

[1]: https://github.com/filipp/gsxlib
