<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Smartman\Swedbank\Events\SwedbankResponseEvent;

class NewSwedResponseListener
{

    //Sample code of how to extract meaningful data from XML files

//    protected function registerPayment($transaction, $currency)
//    {
//        $transactionId = (string)$transaction->AcctSvcrRef;
//        $oldPayment    = Payment::where('transaction_id', $transactionId)->first();
//        if ((string)($transaction->CdtDbtInd) === "DBIT") {
//            info("Payment with id=$transactionId is DEBIT, not saving outgoing payments");
//
//            return false;
//        }
//        if ($oldPayment) {
//            info("Payment with id=$transactionId already exists since $oldPayment->created_at");
//
//            return false;
//        }
//        if ( ! $transaction->NtryDtls->TxDtls->RltdPties) {
//            info("Payment with id=$transactionId does not have related parties, must be other that regular payment");
//
//            return false;
//        }
//        $payment                 = new Payment();
//        $payment->currency       = $currency;
//        $payment->transaction_id = $transactionId;
//        $payment->iban           = (string)$transaction->NtryDtls->TxDtls->RltdPties->Dbtr->Nm;
//        $payment->name           = (string)$transaction->NtryDtls->TxDtls->RltdPties->DbtrAcct->Id->IBAN;
//        $payment->amount         = (string)$transaction->Amt;
//        $payment->date           = (string)$transaction->ValDt->Dt;
//        if ($transaction->NtryDtls->TxDtls->RmtInf->Ustrd) {
//            $payment->description = (string)$transaction->NtryDtls->TxDtls->RmtInf->Ustrd;
//        }
//        if ($transaction->NtryDtls->TxDtls->RmtInf->Strd->CdtrRefInf->Ref) {
//            $payment->reference_no = (string)$transaction->NtryDtls->TxDtls->RmtInf->Strd->CdtrRefInf->Ref;
//        }
//
//        $payment->save();
//        info("Saved payment " . json_encode($payment));
//        return $payment;
//    }
//
//    public function processCreditNotification($notification)
//    {
//        $currency = (string)$notification->Acct->Ccy;
//        $this->registerPayment($notification->Ntry, $currency);
//    }
//
//    public function processPreviousTransactions($currencyTransactions)
//    {
//        foreach ($currencyTransactions as $currencyTransaction) {
//            $currency = (string)$currencyTransaction->Acct->Ccy;
//            foreach ($currencyTransaction->Ntry as $transaction) {
//                $this->registerPayment($transaction, $currency);
//            }
//        }
//    }
//
//    public function handle(SwedbankResponseEvent $event)
//    {
//        info("Handling new swedbank response event");
//        $swedbankResponse = $event->swedbank_request;
//        $success          = $event->success;
//
//        if ( ! $success) {
//            Log::error("Swedbank resoponse not successful, cannot handle. " . json_encode($swedbankResponse));
//
//            return;
//        }
//
//        $xml = new \SimpleXMLElement($swedbankResponse->response_xml);
//
//        $nameSpaces = $xml->getNamespaces();
//        $nameSpace  = reset($nameSpaces);
//
//        if ($nameSpace === "urn:iso:std:iso:20022:tech:xsd:camt.053.001.02") {
//            $this->processPreviousTransactions($xml->BkToCstmrStmt->Stmt);
//        } elseif ($nameSpace === "urn:iso:std:iso:20022:tech:xsd:camt.054.001.02") {
//            $this->processCreditNotification($xml->BkToCstmrDbtCdtNtfctn->Ntfctn);
//        } else {
//            info("Parsing $nameSpace not implemented");
//        }
//        info("Handling new swedbank response completed");
//    }

}
