
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
- (Optional) Rename `tests/test_declarations.php.example` to `tests/test_declaration.php` and setup
variables within. This is only necessary if you choose to run `tests/test.php` to verify your setup is working.
- (Optional) After setting up `test_declarations.php`, `cd tests` and run `php ./test.php`
- Upon first run, the database will be created for you (if you hadn't made it already), as well as the
tables within.

Configuration
=====

This library can be configured either by setting values in a `config.ini` file or by passing in an
associative array of options in a `key => value` format. For example, `["SOLD_TO" => "0123456789", ... ]`

Basic configuration requires the following parameters:

- `PHP_TZ`: A valid [PHP Timezone][2] (ie `America/New_York`)
- `STORAGE`: The storage method for persistence. Currently only supports MySQL, but SQLite support is planned.
- `[DATABASE]`: A set of connection parameters for MySQL (`HOST`, `PORT`, `DB`, `USER`, `PASS`)

Both the REST and the SOAP API require the following parameters:

- `SOLD_TO`: Your Sold-To account number, provided by Apple.

The REST API requires the following parameters: 

- `REST_CERT_PATH`: Path to the certificate bundle in `.pem` format. This will be the `AppleCare-Partner-##########.Prod.apple.com.chain.pem`
file you obtain from Apple with your private key appended.  Note that you must request separate certificates, one
for each API. Keep this in a safe place, not in your web directory!
- `REST_CERT_PASS`: Password for your private key.
- `REST_BASE_URL`: The base URL used for making calls to the REST API, found in eServiceCentral
- `ACCEPT_LANGUAGE`: The `Accept-Language` header. Default is en_US. Other options are available, found in eServiceCentral

Until Apple completely replaces the SOAP API, this library will implement all legacy SOAP functions as well.
The SOAP API requires the following parameters:

- `SOAP_CERT_PATH`: Path to the certificate bundle in `.pem` format. Like `REST_CERT_PATH`, this is the "chain.pem"
certificate provided by Apple, with your private key appended. Note that you must request separate certificates, one
for each API.
- `SOAP_CERT_PASS`: Password for your private key, in case you used a separate private key for your SOAP certificate
- `SOAP_WSDL_URL`: Path to the WSDL file. This can either be the URL provided to you by Apple (in which chase your program will
have to make a separate HTTP request to retrieve the WSDL for every instance of GSXHandler) or you can download the file
and store it somewhere (like in the `config/` folder) and set this variable as the path to the downloaded file.
- `SOAP_ENVIRONMENT`: Will either be `ut` (for test environment) or `it` (for production environment).
- `SOAP_REGION`: Your region code, see REST API documentation in GSX for valid codes (default is `am` for America)
- `SOAP_TIMEZONE`: A Timezone abbreviation (default: `EST`).
- `SOAP_LANGUAGE`: A valid language code (default: `en` for English) according to GSX REST API Documentation

Usage
=====

After setting up `config\config.ini`:

    <?php
    
    require_once("../src/GSXHandler.php");
    
    $gsx = new UPCC\GSXHandler($gsxUserEmail, $gsxShipTo);
    $productDetails = $gsx->ProductDetails($serial);
    echo $productDetails->device->configDescription;
    > iPhone XS Max

Implemented Endpoints
===

Authenticate
- [x] `/authenticate/check`
- [x] `/authenticate/token`
- [ ] `/authenticate/end-session`

Repair
- [x] `/repair/summary`
- [x] `/repair/details`
- [x] `/repair/eligibility`
- [ ] `/repair/update`
- [x] `/repair/audit`
- [ ] `/repair/product/serializer`
- [x] `/repair/questions`
- [ ] `/repair/loaner/return`
- [x] `/repair/create`
- [x] `/repair/product/componentissue`
- [x] `/repair/product/details`
- [ ] `/repair/product/serializer/lookup`

Diagnostics
- [x] `/diagnostics/suites`
- [x] `/diagnostics/initiate-test`
- [x] `/diagnostics/lookup`
- [ ] `/diagnostics/customer-report-url`
- [x] `/diagnostics/status`

Consignment
- [ ] `/consignment/validate`
- [ ] `/consignment/delivery/acknowledge`
- [ ] `/consignment/order/shipment`
- [ ] `/consignment/order/lookup`
- [x] `/consignment/delivery/lookup`
- [ ] `/consignment/order/submit`

Content
- [x] `/content/article`
- [x] `/content/article/lookup`

Other
- [ ] `/document-download` (POST)
- [ ] `/document-download` (GET)
- [ ] `/attachment/upload-access`
- [x] `/parts/summary`
- [x] `/technician/lookup`

GSX Client Certificate and Configuration
===

You will need to work with the GSX Web Support team to obtain the following:

  * A certificate bundle, signed by Apple (generally takes a few business days) for the REST API
  * A certificate bundle, signed by Apple for the SOAP API
  * Your static IP(s) whitelisted (this may take up to a week)
  
Follow the initial setup guide in Apple's eServiceCentral to get started.

GSX account with "Web Services" privilege enabled
===

You (or anyone else with admin access to MyAccess) will need to provision at least one account
in MyAccess to have the "Web Services" privilege for GSX.

[1]: https://github.com/filipp/gsxlib
[2]: https://www.php.net/manual/en/timezones.php
