<?php
/**
 * Copyright © 2019 Bananacode SA, All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bananacode\GreenPay\Gateway\Request;

use Magento\Braintree\Gateway\SubjectReader;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class CaptureRequest
 * @package Bananacode\GreenPay\Gateway\Request
 */
class CaptureRequest implements BuilderInterface
{
    use Formatter;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * CaptureRequest constructor.
     * @param ConfigInterface $config
     * @param SubjectReader $subjectReader
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        ConfigInterface $config,
        SubjectReader $subjectReader,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->_encryptor = $encryptor;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Builds required request data
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        $sandbox = $this->config->getValue(
            'sandbox',
            $order->getStoreId()
        );

        $shippingAddress = $this->_checkoutSession->getQuote()->getShippingAddress();

        return [
            'shipping' => $shippingAddress->getShippingMethod(),
            'shipping_amount' => $this->formatPrice($shippingAddress->getShippingAmount()),
            'amount' => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            'payment_method_nonce' => (array)json_decode($payment->getAdditionalInformation('payment_method_nonce')),
            'order_id' => $order->getOrderIncrementId(),
            'merchant_id' => $this->_encryptor->decrypt($this->config->getValue(
                $sandbox ? 'merchant_id_sandbox' : 'merchant_id',
                $order->getStoreId()
            )),
            'secret' => $this->_encryptor->decrypt($this->config->getValue(
                $sandbox ? 'secret_sandbox' : 'secret',
                $order->getStoreId()
            )),
            'terminal' => $this->_encryptor->decrypt($this->config->getValue(
                $sandbox ? 'terminal_sandbox' : 'terminal',
                $order->getStoreId()
            )),
            'sandbox' => $sandbox
        ];
    }
}
