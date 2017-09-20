# Changes between version 4 and 5
The library now supports PHP 7 and can only be used with PHP 5.4 or greater.

## PSR-2
The library has now been updated to use the PSR-2 coding style guide and support a PSR-4 autoloader.

As such the names of the library classes has been changed:

| Old             | New                                     |
| --------------- | --------------------------------------- |
| Klarna          | Klarna\XMLRPC\Klarna                    |
| KlarnaAddr      | Klarna\XMLRPC\Address                   |
| KlarnaCalc      | Klarna\XMLRPC\Calc                      |
| KlarnaCountry   | Klarna\XMLRPC\Country                   |
| KlarnaCurrency  | Klarna\XMLRPC\Currency                  |
| KlarnaEncoding  | Klarna\XMLRPC\Encoding                  |
| KlarnaException | Klarna\XMLRPC\Exception\KlarnaException |
| KlarnaFlags     | Klarna\XMLRPC\Flags                     |
| KlarnaLanguage  | Klarna\XMLRPC\Language                  |
| KlarnaPClass    | Klarna\XMLRPC\PClass                    |

## Removed functionality
### XMLRPC methods
Some XMLRPC methods has been removed from `Klarna\XMLRPC\Klarna` because they are deprecated and should not be used by newer integrations:

* `removeCustomerNo`
* `setCustomerNo`
* `getCustomerNo`
* `updateOrderno`
* `updateGoodsQty`
* `updateChargeAmount`
* `invoicePartAmount`
* `invoiceAddress`
* `invoiceAmount`
* `changeReservation`
* `activateReservation`
* `activatePart`
* `deleteInvoice`
* `activateInvoice`
* `addInvoice`

### PClass Storage
Now the library has no opinions on how PClasses are stored. The following classes were removed:

* `JSONStorage`
* `MySQLStorage`
* `PCStorage`
* `SQLStorage`
* `XMLStorage`

This has resulted in `Klarna\XMLRPC\Klarna->getPClasses` replacing `Klarna\XMLRPC\Klarna->fetchPClasses`.

### Custom numeric HTML entities conversion
The method `Klarna\XMLRPC\Klarna::num_htmlentities` has been removed and instead relies on the package [phpxmlrpc/phpxmlrpc](https://packagist.org/packages/phpxmlrpc/phpxmlrpc) with [`PhpXmlRpc\Helper\Charset->encodeEntities`](https://github.com/gggeek/phpxmlrpc/blob/master/src/Helper/Charset.php#L89)
