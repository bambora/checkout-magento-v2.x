<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Controller\Checkout;

use \Magento\Framework\Webapi\Exception;
use \Magento\Framework\Webapi\Response;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Payment\Transaction;
use Bambora\Online\Model\Api\CheckoutApi;
use Bambora\Online\Model\Method\Checkout as CheckoutMethod;

class Callback extends AbstractCheckout
{
    /**
     * @var Transaction\BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Framework\Controller\Result\Json
     */
    private $_callbackResult;


    /**
     * Callback constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bambora\Online\Helper\Data $bamboraHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    ) {
        parent::__construct(
            $context,
            $orderFactory,
            $checkoutSession,
            $bamboraHelper,
            $resultJsonFactory,
            $bamboraLogger,
            $paymentHelper
         );
        $this->_transactionBuilder = $transactionBuilder;
        $this->_orderSender = $orderSender;
    }

    /**
     * Callback
     */
    public function execute()
    {
        $posted = $this->getRequest()->getParams();
        $transactionInfo = array();
        $isValid = $this->validateCallback($posted, $transactionInfo);

        if($isValid)
        {
            $this->processCallback($posted, $transactionInfo);
        }

        return $this->_callbackResult;
    }


    /**
     * @desc Process the callback from Bambora
     * @return void
     */
    private function processCallback($posted,$transactionInfo)
    {

        $order = $this->_getOrderByIncrementId($posted['orderid']);

        try
        {
            if($order->getState() === Order::STATE_PENDING_PAYMENT)
            {
                $this->_checkoutSession->setLastOrderId($order->getId());
                $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

                $payment = $order->getPayment();
                $payment->setTransactionId($posted['txnid']);

                $paymentType = $transactionInfo['transaction']['information']['paymenttypes'][0]['displayname'];
                $payment->setCcType($paymentType);

                $this->_transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($payment->getTransactionId())
                ->build(Transaction::TYPE_AUTH);

                $order->setState(Order::STATE_PROCESSING);

                $message = __("Payment authorization was a success.") . " " . __("Transaction Id:") . " " . $posted['txnid'];
                $order->addStatusHistoryComment($message, Order::STATE_PROCESSING);
                $order->setIsNotified(true);
                $order->save();

                if (!$order->getEmailSent()) {
                    $this->_orderSender->send($order);
                }
            }

            $message = "Callback Success";
            $this->setResult(Response::HTTP_OK, $message,$order->getIncrementId());
        }
        catch(Exception $ex)
        {
            $message = "Callback Failed - " .$ex->getMessage();
            $this->setResult(Exception::HTTP_INTERNAL_ERROR, $message,$order->getIncrementId());
        }
    }

    /**
     * @desc Validate the callback
     * @return bool
     */
    private function validateCallback($posted, &$transactionInfo)
    {
        //Validate response
        if(!isset($posted) || !$posted['txnid'])
        {
            $message = isset($posted) ? "TransactionId is missing" : "Response is null";
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $posted['orderid']);
            return false;
        }

        //Validate Order
        $order = $this->_getOrderByIncrementId($posted['orderid']);
        if(!isset($order))
        {
            $message = "The Order could be found or created";
            $this->setResult(Exception::HTTP_BAD_REQUEST,$message,$posted['orderid']);
            return false;
        }

        //Validate Transaction
        $transactionId = $posted['txnid'];
        $storeId = $order->getStoreId();
        $apiKey = $this->_bamboraHelper->generateApiKey($storeId);
        $merchantApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);

        $transactionInfo = $merchantApi->getTransaction($transactionId,$apiKey);

        //Validate transaction
        if(!isset($transactionInfo) || !$transactionInfo['meta']['result'])
        {
            $message = !isset($transactionInfo) ? "gettransactionInformation object is null" : $transactionInfo['meta']['message']['merchant'];
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
            return false;
        }

        //Validate orderId
        if($order->getIncrementId() != $transactionInfo['transaction']['orderid'])
        {
            $message = "The posted ordernumber does not match the transaction";
            $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());
            return false;
        }

        //Validate MD5
        $shopMd5 = $this->_bamboraHelper->getBamboraCheckoutConfigData('md5key', $storeId);
        $var = "";
        if(strlen($shopMd5) > 0)
        {
            foreach($posted as $key => $value)
            {
                if($key != "hash")
                {
                    $var .= $value;
                }
            }

            $genstamp = md5($var . $shopMd5);

            if($genstamp != $posted["hash"])
            {
                $message = "Bambora MD5 check failed";
                $this->setResult(Exception::HTTP_BAD_REQUEST, $message, $order->getIncrementId());

                return false;
            }
        }

        return true;
    }

    private function setResult($statusCode,$message,$id)
    {
        $result = $this->_resultJsonFactory->create();
        $result->setHttpResponseCode($statusCode);

        $result->setData(
            ['id'=>$id,
            'message'=>$message,
            'module'=>CheckoutMethod::MODULE_INFO,
            'version'=>CheckoutMethod::MODULE_VERSION]);

        if($statusCode === Response::HTTP_OK)
        {
            $this->_bamboraLogger->addCheckoutInfo($id,$message);
        }
        else
        {
            $this->_bamboraLogger->addCheckoutError($id,$message);
        }

        $this->_callbackResult = $result;
    }
}
