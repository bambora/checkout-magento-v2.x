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

class CheckoutApiModels
{
    // Request
    const REQUEST_CHECKOUT = 'Request\Checkout';
    const REQUEST_CAPTURE = 'Request\Capture';
    const REQUEST_CREDIT = 'Request\Credit';

    // Request Models
    const REQUEST_MODEL_ADDRESS = 'Request\Models\Address';
    const REQUEST_MODEL_CALLBACK = 'Request\Models\Callback';
    const REQUEST_MODEL_CUSTOMER = 'Request\Models\Customer';
    const REQUEST_MODEL_ORDER = 'Request\Models\Order';
    const REQUEST_MODEL_LINE = 'Request\Models\Line';
    const REQUEST_MODEL_URL = 'Request\Models\Url';

    //Response
    const RESPONSE_CHECKOUT = 'Response\Checkout';
    const RESPONSE_LISTPAYMENTTYPES = 'Response\ListPaymentTypes';
    const RESPONSE_TRANSACTION = 'Response\Transaction';
    const RESPONSE_CAPTURE = 'Response\Capture';
    const RESPONSE_CREDIT = 'Response\Credit';
    const RESPONSE_DELETE = 'Response\Delete';

    //Response Models
    const RESPONSE_MODEL_META = 'Response\Models\Meta';
    const RESPONSE_MODEL_MESSAGE = 'Response\Models\Message';
    const RESPONSE_MODEL_PAYMENTCOLLECTION = 'Response\Models\PaymentCollection';
    const RESPONSE_MODEL_PAYMENTGROUP = 'Response\Models\PaymentGroup';
    const RESPONSE_MODEL_PAYMENTYPE = 'Response\Models\PaymentType';
    const RESPONSE_MODEL_FEE = 'Response\Models\Fee';
    const RESPONSE_MODEL_AVAILABLE = 'Response\Models\Available';
    const RESPONSE_MODEL_CURRENCY = 'Response\Models\Currency';
    const RESPONSE_MODEL_INFORMATION = 'Response\Models\Information';
    const RESPONSE_MODEL_ACQUIRER = 'Response\Models\Acquirer';
    const RESPONSE_MODEL_PRIMARYACCOUNTNUMBER = 'Response\Models\PrimaryAccountnumber';
    const RESPONSE_MODEL_LINKS = 'Response\Models\Links';
    const RESPONSE_MODEL_SUBSCRIPTION = 'Response\Models\Subscription';
    const RESPONSE_MODEL_TOTAL = 'Response\Models\Total';
    const RESPONSE_MODEL_TRANSACTION = 'Response\Models\Transaction';
    const RESPONSE_MODEL_TRANSACTIONOPERATION = 'Response\Models\TransactionOperation';








}