<?php
namespace Bambora\Online\Model\Method;

interface PaymentInterface
{
    /**
     * Get payment window
     *
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getPaymentWindow($order);

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount);

    /**
     * Refund payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount);

    /**
     * Void payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment);

    /**
     * Cancel payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment);

    /**
     * Get Bambora Checkout Transaction
     *
     * @param mixed $transactionId
     * @param string $storeId
     * @param string $message
     * @return mixed
     */
    public function getTransaction($transactionId, $storeId, &$message);
}
