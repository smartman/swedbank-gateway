 # Swedbank gateway integration 

# General info
 
 This is Laravel package that enables support for Swedbank Gateway. Allowed operation is preparing Payment in Swedbank internet bank.
 
 Example use case is ERP or other system that makes purchase orders and wants to assure that all of these orders get paid. This system can prepare payment once the purchase order is confirmed.
 
 Prerequisite is Java support in server because for PKI signing and cryptographic operations is jdigidoc java library used.
 
## Configuration
 
 run `php artisan vendor:publish` which copies main config file swedbank.php together with migration and jdigidoc.cfg.
 
 swedbank.php config file contains references to the data that you will get or negotiate with Swedbank. Default settings are for example only and you need to change these except charge_bearer
 
 In jdigidoc.cfg main thing you need to change is DIGIDOC_LOG4J_CONFIG if you want crypto operations to be logged.
 
## Usage instructions

Laravel 5.5+ registers service provider Smartman\Swedbank\SwedbankGatewayProvider and Facade "Swedbank" => "Smartman\Swedbank\SwedbankFacade"

Call `Swedbank::sendPreparePayment($amountInCents, $receiverIban, $receiverName, $currency, $explanation, $referenceNumber)`

$explanation or $referenceNumber must exist

Payments are processed asynchronously. This means that `Swedbank::checkRequests()` must be executed periodically to see how payment is doing.

If request gets response then Event Smartman\Swedbank\Events\SwedbankResponseEvent is dispatched with property ->swedbank_request that is of type `Smartman\Swedbank\SwedbankRequest`. You can examine the response_xml to see if there was errors with processing the payment.

## Support
 
 In case you need help in configuring or additional custom developments you can email to margus.pala@gmail.com or call +372 555 29 332  
 
   