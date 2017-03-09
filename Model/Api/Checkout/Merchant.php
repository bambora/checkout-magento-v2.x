<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\Checkout\ApiEndpoints;
use Bambora\Online\Model\Api\CheckoutApiModels;

class Merchant extends Base
{
    /**
     * Get the allowed payment types
     *
     * @param string $currency
     * @param int|long $amount
     * @param string $apiKey
     * @return \Bambora\Online\Model\Api\Checkout\Response\ListPaymentTypes
     */
    public function getPaymentTypes($currency, $amount, $apiKey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_MERCHANT) . '/paymenttypes?currency='. $currency . '&amount=' . $amount;
            $resultJson = $this->_callRestService($serviceUrl, null, Base::GET, $apiKey);
            $result = json_decode($resultJson, true);

            /** @var \Bambora\Online\Model\Api\Checkout\Response\ListPaymentTypes */
            $listPaymentTypesResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_LISTPAYMENTTYPES);
            $listPaymentTypesResponse->meta = $this->_mapMeta($result);

            if ($listPaymentTypesResponse->meta->result) {
                $listPaymentTypesResponse->paymentCollections = array();

                foreach ($result['paymentcollections'] as $payment) {
                    /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentCollection */
                    $paymentCollection = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_PAYMENTCOLLECTION);
                    $paymentCollection->displayName = $payment['displayname'];
                    $paymentCollection->id = $payment['id'];
                    $paymentCollection->name = $payment['name'];
                    $paymentCollection->paymentGroups = array();

                    foreach ($payment['paymentgroups'] as $group) {
                        /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentGroup */
                        $paymentGroup = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_PAYMENTGROUP);
                        $paymentGroup->displayName = $group['displayname'];
                        $paymentGroup->id = $group['id'];
                        $paymentGroup->name = $group['name'];
                        $paymentGroup->paymentTypes = array();

                        foreach ($group['paymenttypes'] as $type) {
                            /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentType */
                            $paymentType = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_PAYMENTYPE);
                            $paymentType->displayName = $type['displayname'];
                            $paymentType->groupid = $type['groupid'];
                            $paymentType->id = $type['id'];
                            $paymentType->name = $type['name'];

                            /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Fee */
                            $fee = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_FEE);
                            $fee->amount = $type['fee']['amount'];
                            $fee->id = $type['fee']['id'];

                            $paymentType->fee = $fee;

                            $paymentGroup->paymentTypes[] = $paymentType;
                        }

                        $paymentCollection->paymentGroups[] = $paymentGroup;
                    }
                    $listPaymentTypesResponse->paymentCollections[] = $paymentCollection;
                }
            }

            return $listPaymentTypesResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Returns a transaction based on the transactionid
     *
     * @param string $transactionId
     * @param string $apiKey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Transaction
     */
    public function getTransaction($transactionId, $apiKey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(ApiEndpoints::ENDPOINT_MERCHANT) . '/transactions/' . sprintf('%.0F', $transactionId);

            $resultJson = $this->_callRestService($serviceUrl, null, Base::GET, $apiKey);
            $result = json_decode($resultJson, true);

            /** @var \Bambora\Online\Model\Api\Checkout\Response\Transaction */
            $transactionResponse = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_TRANSACTION);
            $transactionResponse->meta = $this->_mapMeta($result);

            if ($transactionResponse->meta->result) {
                $result = $result['transaction'];

                /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Transaction */
                $transaction = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_TRANSACTION);

                /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Available */
                $available = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_AVAILABLE);
                $available->capture = $result['available']['capture'];
                $available->credit = $result['available']['credit'];

                $transaction->available = $available;
                $transaction->canDelete = $result['candelete'];
                $transaction->createdDate = $result['createddate'];

                /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Currency */
                $currency = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_CURRENCY);
                $currency->code = $result['currency']['code'];
                $currency->minorunits = $result['currency']['minorunits'];
                $currency->name = $result['currency']['name'];
                $currency->number = $result['currency']['number'];

                $transaction->currency = $currency;
                $transaction->id = $result['id'];

                /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Information */
                $information = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_INFORMATION);
                $information->acquirers = array();
                foreach ($result['information']['acquirers'] as $acq) {
                    /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Acquirer */
                    $acquirer = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_ACQUIRER);
                    $acquirer->name = $acq['name'];
                    $information->acquirers[] = $acquirer;
                }
                $information->paymentTypes = array();
                foreach ($result['information']['paymenttypes'] as $type) {
                    /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\PaymentType */
                    $paymentType = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_PAYMENTYPE);
                    $paymentType->displayName = $type['displayname'];
                    $paymentType->groupid = $type['groupid'];
                    $paymentType->id = $type['id'];
                    $information->paymentTypes[] = $paymentType;
                }
                $information->primaryAccountnumbers = array();
                foreach ($result['information']['primaryaccountnumbers'] as $accountNumber) {
                    /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\PrimaryAccountnumber */
                    $primaryAccountnumber = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_PRIMARYACCOUNTNUMBER);
                    $primaryAccountnumber->number = $accountNumber['number'];
                    $information->primaryAccountnumbers[] = $primaryAccountnumber;
                }

                $transaction->information = $information;

                /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Links */
                $links = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_LINKS);
                $links->transactionoperations = $result['links']['transactionoperations'];

                $transaction->links = $links;
                $transaction->merchantnumber = $result['merchantnumber'];
                $transaction->orderid = $result['orderid'];
                $transaction->reference = $result['reference'];
                $transaction->status = $result['status'];

                /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Subscription */
                $subscription = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_SUBSCRIPTION);
                $subscription->id = $result['subscription']['id'];

                $transaction->subscription = $subscription;

                /** @var \Bambora\Online\Model\Api\Checkout\Response\Models\Total */
                $total = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::RESPONSE_MODEL_TOTAL);
                $total->authorized = $result['total']['authorized'];
                $total->balance = $result['total']['balance'];
                $total->captured = $result['total']['captured'];
                $total->credited = $result['total']['credited'];
                $total->declined = $result['total']['declined'];
                $total->feeamount = $result['total']['feeamount'];

                $transaction->total = $total;

                $transactionResponse->transaction = $transaction;
            }

            return $transactionResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCheckoutError("-1", $ex->getMessage());
            return null;
        }
    }
}
