<?php

namespace Smartman\Swedbank;

use Smartman\Swedbank\Events\SwedbankResponseEvent;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Smartman\Swedbank\Facade\Swedbank;

class SwedbankGatewayImplementation
{

    public function __construct()
    {
        $this->jarPath      = __DIR__ . "/jdigidoc/jdigidocutil-3.12.0-785.jar";
        $this->keyPass      = config('swedbank.private_key_pass');
        $this->keyPath      = config('swedbank.private_key');
        $this->gatewayCert  = config('swedbank.gateway_certificate');
        $this->gatewayCa    = config('swedbank.gateway_ca');
        $this->clientCert   = config('swedbank.client_cert');
        $this->clientSslKey = config('swedbank.client_ssl_key');
        $this->agreementId  = config('swedbank.agreement_id');
        $this->configPath   = config('swedbank.config_path');
        $this->gatewayUrl   = config('swedbank.production_mode') ? "https://swedbankgateway.net" : "https://dev.hansagateway.net";

        $this->client = new Client();

        $this->path = storage_path() . "/swedbank";
        if ( ! file_exists($this->path)) {
            mkdir($this->path, 0750);
        }
    }

    public function sendPing()
    {
        $date = Carbon::now()->toDateTimeString();
        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Ping><Value>Wassup $date</Value></Ping>";

        $request = Swedbank::sendRequest($xml);

        return "Ping sent, id=$request->id";
    }

    /**
     * @param $amountInCents
     * @param $receiverIban
     * @param $receiverName
     * @param $currency
     * @param $explanation
     * @param bool $structured if plain text description or reference number
     *
     * @return mixed
     */
    public function sendPreparePayment(
        $amountInCents,
        $receiverIban,
        $receiverName,
        $currency,
        $explanation,
        $structured = false
    ) {
        $correlationId = time() . str_random();
        $amount        = number_format($amountInCents / 100, 2);

        $document = new \SimpleXMLElement("<Document></Document>");
        $document->addAttribute("xmlns", "urn:iso:std:iso:20022:tech:xsd:pain.001.001.03");
        $customerTransInf = $document->addChild("CstmrCdtTrfInitn");

        $groupHeader = $customerTransInf->addChild("GrpHdr");
        $groupHeader->addChild("MsgId", $correlationId);
        $groupHeader->addChild("CreDtTm", Carbon::now()->toRfc3339String());
        $groupHeader->addChild("NbOfTxs", 1);
        $groupHeader->addChild("CtrlSum", $amount);
        $groupHeader->addChild("InitgPty");
        $groupHeader->InitgPty->addChild("Nm", config('swedbank.payee_name'));

        $paymentInf = $customerTransInf->addChild("PmtInf");
        $paymentInf->addChild("PmtInfId", $correlationId);
        $paymentInf->addChild("PmtMtd", "TRF");
        $paymentInf->addChild("NbOfTxs", 1);
        $paymentInf->addChild("CtrlSum", $amount);
        $paymentInf->addChild("PmtTpInf")
                   ->addChild("SvcLvl")
            //Empty or other values will be set to NURG or SEPA depending on payment instructions
                   ->addChild("Cd", "SEPA");

        $paymentInf->addChild("ReqdExctnDt", Carbon::now()->toDateString());
        $paymentInf->addChild("Dbtr");
        $paymentInf->addChild("DbtrAcct")
                   ->addChild("Id")
                   ->addChild("IBAN", config('swedbank.payee_account'));
        $paymentInf->addChild("DbtrAgt")->addChild("FinInstnId"); //BIC optional ->addChild("BIC", "HABAEE2X");
        $paymentInf->addChild("ChrgBr", config('swedbank.charge_bearer'));

        $transaction = $paymentInf->addChild("CdtTrfTxInf");
        $transaction->addChild("PmtId")->addChild("EndToEndId", $correlationId);

        $instdAmt = $transaction->addChild("Amt")->addChild("InstdAmt", $amount);
        $instdAmt->addAttribute("Ccy", $currency);

        $transaction->addChild("Cdtr")
                    ->addChild("Nm", $receiverName);
        $transaction->addChild("CdtrAcct")
                    ->addChild("Id")
                    ->addChild("IBAN", $receiverIban);

        $remittance = $transaction->addChild("RmtInf");
        if ($structured) {
            $creditRefInf = $remittance->addChild("Strd")->addChild("CdtrRefInf");
            $creditRefInf->addChild("Tp")->addChild("CdOrPrtry")->addChild("Cd", "SCOR");
            $creditRefInf->addChild("Ref", $explanation);
        } else {
            $remittance->addChild("Ustrd", $explanation);
        }

        $request = $this->sendRequest($document->asXML(), $correlationId);

        return $request;
    }

    public function checkRequests()
    {
        info("Checking next message from Swedbank channel");
        try {
            $getRes = $this->client->request("GET", $this->gatewayUrl, [
                'verify'  => config('swedbank.gateway_ca'),
                'cert'    => config('swedbank.client_cert'),
                'ssl_key' => config('swedbank.client_ssl_key'),
                "headers" => [
                    "X-AgreementId" => config('swedbank.agreement_id')
                ]
            ]);
        } catch (ClientException $exception) {
            $status = $exception->getResponse()->getStatusCode();
            if ($status == "404") {
                info("No messages right now");

                return true;
            } else {
                throw $exception;
            }
        }

        $trackingId    = $getRes->getHeader("TrackingID")[0];
        $correlationId = $getRes->getHeader("CorrelationID")[0];

        $swedbankRequest = SwedbankRequest::where('correlation_id', $correlationId)->first();

        if ( ! $swedbankRequest) {
            Log::error("No request made with correlation ID $correlationId, tracking $trackingId. Deleting anyway");
            $this->sendDeleteCall($swedbankRequest, $trackingId);

            return false;
        }

        $swedbankRequest->tracking_id = $trackingId;

        $responseCdocLocation = "$this->path/$correlationId.response.cdoc";
        $responseBdocLocation = "$this->path/$correlationId.response.bdoc";
        $responseXmlLocation  = "$this->path/$correlationId.response.xml";

        $decrypOutput  = [];
        $extractOutput = [];

        file_put_contents($responseCdocLocation, $getRes->getBody());
        $decryptCommand = "java -jar $this->jarPath -config $this->configPath -cdoc-in $responseCdocLocation -cdoc-decrypt-pkcs12 $this->keyPath $this->keyPass PKCS12 $responseBdocLocation";
        $decryptResult  = exec($decryptCommand, $decrypOutput);

        $extractCommand = "java -jar $this->jarPath -config $this->configPath -ddoc-in $responseBdocLocation -ddoc-extract $correlationId-response.xml $responseXmlLocation";
        $extractResult  = exec($extractCommand, $extractOutput);

        if ( ! str_contains($decryptResult, "success") || ! str_contains($extractResult, "success")) {
            $message = "Preparing crypted Swedbank call failed $correlationId";
            Log::error("$message: decryptOutput=" . implode(" ",
                    $decrypOutput) . ", extractOutput=" . implode(" ", $extractOutput));
            throw new \RuntimeException($message);
        }

        $extractedXmlContents = file_get_contents($responseXmlLocation);

        $swedbankRequest->response_xml = $extractedXmlContents;
        $swedbankRequest->save();

        info("Received swedbank response for $swedbankRequest->id $correlationId $swedbankRequest->response_xml");


        $xml = simplexml_load_string($swedbankRequest->response_xml);
        if (isset($xml->HGWError)) {
            Log::error("Swedbank gateway Request failed: " . $xml->HGWError->Message);
            event(new SwedbankResponseEvent($swedbankRequest, false));
        } else {
            event(new SwedbankResponseEvent($swedbankRequest, true));
        }

        $this->sendDeleteCall($swedbankRequest);

        unlink($responseCdocLocation);
        unlink($responseBdocLocation);
        unlink($responseXmlLocation);
    }

    protected function sendRequest($xml, $correlationId = null)
    {
        if ($correlationId == null) {
            $correlationId = time() . str_random();
        }

        info("Sending Swedbank request with correlationId $correlationId: $xml ");

        $xmlLocation = "$this->path/$correlationId.xml";
        file_put_contents($xmlLocation, $xml);

        $bdocLocation = "$this->path/$correlationId.request.bdoc";
        $cdocLocation = "$this->path/$correlationId.request.cdoc";

        $signResult  = exec("java -jar $this->jarPath -config $this->configPath -ddoc-new BDOC 2.1 -ddoc-add $xmlLocation text/xml BINARY -ddoc-sign $this->keyPass ERP \"\" \"\" \"\" \"\" 0 \"BES\" PKCS12 $this->keyPath -ddoc-out $bdocLocation",
            $signOutput, $signStatus);
        $cryptResult = exec("java -jar $this->jarPath -config $this->configPath -cdoc-recipient $this->gatewayCert HGW -cdoc-encrypt $bdocLocation $cdocLocation",
            $cryptOutput, $cryptStatus);

        if ( ! str_contains($signResult, "success") || ! str_contains($cryptResult, "success")) {
            $message = "Preparing crypted Swedbank call failed $correlationId";
            Log::error("$message: signOutput=" . implode(" ",
                    $signOutput) . ", cryptOutput=" . implode(" ", $cryptOutput));
            throw new \RuntimeException($message);
        }

        $putRes = $this->client->request("PUT", $this->gatewayUrl, [
            'verify'  => $this->gatewayCa,
            'cert'    => $this->clientCert,
            'ssl_key' => $this->clientSslKey,
            'headers' => [
                "CorrelationID" => $correlationId,
                "X-AgreementId" => $this->agreementId
            ],
            'body'    => file_get_contents($cdocLocation)
        ]);

        $gatewayMessage = $putRes->getHeader("X-Gateway-Message")[0];
        if ($gatewayMessage != "1") {
            $message = "Swedbank call $correlationId did not reach gateway $gatewayMessage";
            Log::error("$message: " . print_r($putRes->getHeaders(), true));
            throw new \RuntimeException($message);
        }

        $swedbankRequest                 = new SwedbankRequest();
        $swedbankRequest->correlation_id = $correlationId;
        $swedbankRequest->request_xml    = $xml;
        $swedbankRequest->save();
        info("New swedbank request $correlationId sent");

        unlink($xmlLocation);
        unlink($bdocLocation);
        unlink($cdocLocation);

        return $swedbankRequest;
    }

    protected function sendDeleteCall($swedbankRequest, $trackingId = null)
    {
        $deleteRes = $this->client->request("DELETE", $this->gatewayUrl, [
            'verify'  => $this->gatewayCa,
            'cert'    => $this->clientCert,
            'ssl_key' => $this->clientSslKey,
            'headers' => [
                "TrackingID" => $trackingId ?? $swedbankRequest->tracking_id
            ]
        ]);

        $gatewayMessage = $deleteRes->getHeader('X-Gateway-Message')[0];

        if ($trackingId != null) {
            info("Deleted Swedbank request $trackingId without updating Request itseld");

            return;
        }

        if ($deleteRes->getStatusCode() == 200 && $gatewayMessage == 1) {
            info("Swedbank request with correlation ID $swedbankRequest->correlation_id and tracking $swedbankRequest->tracking_id deleted");
        }

        $swedbankRequest->deleted = true;
        $swedbankRequest->save();

    }

}
