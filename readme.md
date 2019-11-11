 # Swedbank gateway integration 

# General info
 
 This is Laravel package that enables support for Swedbank Gateway. Allowed operation is preparing Payment in Swedbank internet bank.
 
 Example use case is ERP or other system that makes purchase orders and wants to assure that all of these orders get paid. This system can prepare payment once the purchase order is confirmed.
 
 Prerequisite is Java support in server because for PKI signing and cryptographic operations is jdigidoc java library used.
 
 All communication is asynchronous. Request is sent to the bank and when bank has finised processing then it will prepare query that you can poll
 
 This module will save all response and request XML-s to swedbank_requests table. There is also model Smartman\Swedbank\SwedbankRequest for this table. 
 
##Installation
 run `composer require smartman/swedbank-gateway`
 
## Configuration
 
 run `php artisan vendor:publish` which copies main config file swedbank.php together with migration java crypto library configuration files to your config folder.
 
 swedbank.php config file contains references to the data that you will get or negotiate with Swedbank. Default settings are for example only and you need to change these except charge_bearer
 
 There are big differences in sandbox and production mode. See jdigidoc.cfg and jdigidoc-sandbox.cfg in your config folder.
 In jdigidoc.cfg main thing you need to change is DIGIDOC_LOG4J_CONFIG to its absolute path if you want crypto operations to be logged.
 
## Usage instructions

Laravel 5.5+ registers service provider Smartman\Swedbank\SwedbankGatewayProvider and Facade "Swedbank" => "Smartman\Swedbank\SwedbankFacade"

**Testing configuration**

Call `Swedbank::sendPing()` This command will be responded with XML that contains element <Pong></Pong>

**Checking responses**

Call `Swedbank::checkRequests()`, This will check for pending messages and triggers `Smartman\Swedbank\Events\SwedbankResponseEvent`. Listen to this event with parameters SwedbankRequest and success (true/false).
Optionally `Swedbank::checkRequests()` returns array with format `['request'=>SwedbankRequest', 'success'=>true/false]`
It is your own responsibility to check the response_xml elements, parse these and read out needed data
It is possible to get responses without requests, for example daily scheduled account statements. In this case request_xml is empty.

**Preparing payments**

This will prepare payment to be signed in internet bank for final processing.

Call `Swedbank::sendPreparePayment($amountInCents, $receiverIban, $receiverName, $currency, $explanation, $referenceNumber)`

$explanation or $referenceNumber must exist

Payments are processed asynchronously. This means that `Swedbank::checkRequests()` must be executed periodically to see how payment is doing.

**Getting Account Statements**

Call `Swedbank::getAccountStatement($startDate, $endDate)`. Maximum date period is 90 days and there is maximum of 10 000 transaction records in the response 

## Support
 
 In case you need help in configuring or additional custom developments you can email to margus.pala@gmail.com or call +372 555 29 332
 All the complex bank side business rules prechecks are not implemented and help might be needed if errors are happening. For example bank has rules like below
 
 Estonia: unstructured or structured remittance information or both needs to be present.
 
 Latvia: Either unstructured or structured remittance information must be present but not both.
 
 Lithuania: Either unstructured or structured remittance information must be present but not
 both.
 
 European Payment: Either unstructured or structured remittance information must be present
 but not both.
 
 International non-European payments: Only unstructured remittance information is accepted.
 
 If unstructured and structured references are both used the maximum accepted combined
 length is 130.  
 
   