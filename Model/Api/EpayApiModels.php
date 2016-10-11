<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api;

class EpayApiModels
{
    //Request
    const REQUEST_PAYMENT = 'Request\Payment';

    //Request Models
    const REQUEST_MODEL_AUTH = 'Request\Models\Auth';
    const REQUEST_MODEL_URL = 'Request\Models\Url';
    const REQUEST_MODEL_INVOICE = 'Request\Models\Invoice';
    const REQUEST_MODEL_CUSTOMER = 'Request\Models\Customer';
    const REQUEST_MODEL_SHIPPINGADDRESS = 'Request\Models\ShippingAddress';

    //Response
    const RESPONSE_CAPTURE = 'Response\Capture';
    const RESPONSE_CREDIT = 'Response\Credit';
    const RESPONSE_DELETE = 'Response\Delete';
    const RESPONSE_TRANSACTION = 'Response\Transaction';
    const RESPONSE_EPAYERROR = 'Response\EpayError';
    const RESPONSE_PBSERROR = 'Response\PbsError';

    //Response Models
    const RESPONSE_MODEL_TRANSACTIONHISTORYINFO = 'Response\Models\TransactionHistoryInfo';
    const RESPONSE_MODEL_TRANSACTIONINFORMATIONTYPE = 'Response\Models\TransactionInformationType';


}