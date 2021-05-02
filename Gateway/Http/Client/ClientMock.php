<?php
/**
 * Copyright © 2019 Bananacode SA, All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bananacode\GreenPay\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

/**
 * Class ClientMock
 * @package Bananacode\GreenPay\Gateway\Http\Client
 */
class ClientMock implements ClientInterface
{
    /**
     * GreenPay Success Code
     */
    const SUCCESS = 200;

    /**
     * GreenPay Sandbox URLS
     */
    const SANDBOX_PAYMENT_URL = 'https://sandbox-merchant.greenpay.me';
    const SANDBOX_CHECKOUT_URL = 'https://sandbox-checkout.greenpay.me';

    /**
     * GreenPay Production URLS
     */
    const CHECKOUT_URL = 'https://checkout.greenpay.me';
    const PAYMENT_URL = 'https://merchant.greenpay.me';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $_curl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Bananacode\GreenPay\Model\Split
     */
    private $_splitModel;

    /**
     * @var \Bananacode\GreenPay\Model\ResourceModel\Split
     */
    private $_splitResource;

    /**
     * ClientMock constructor.
     * @param Logger $logger
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Bananacode\GreenPay\Model\Split $splitModel,
        \Bananacode\GreenPay\Model\ResourceModel\Split $splitResource
    ) {
        $this->logger = $logger;
        $this->_curl = $curl;
        $this->_storeManager = $storeManager;
        $this->_splitModel = $splitModel;
        $this->_splitResource = $splitResource;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = $this->placeOrder($transferObject->getBody());

        /*$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/payment.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($response, true));*/

        return $response;
    }

    /**
     * @param $requestData
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function placeOrder($requestData)
    {
        $url = self::CHECKOUT_URL;
        if (isset($requestData['sandbox'])) {
            if ($requestData['sandbox']) {
                $url = self::SANDBOX_CHECKOUT_URL;
            }
        }

        $brand = array_search('brand', array_column($requestData['payment_method_nonce'], 'type'));
        $master = array_search('master', array_column($requestData['payment_method_nonce'], 'type'));
        $shipping = false;
        foreach ($requestData['payment_method_nonce'] as $datum) {
            if ($datum->type === 'shipping') {
                $found = array_search($requestData['shipping'], explode(',', $datum->ref));
                if (!is_bool($found)) {
                    $shipping = $datum;
                }
            }
        }

        if (!is_bool($brand)) {
            $brand = $requestData['payment_method_nonce'][$brand];
        }

        if (!is_bool($master)) {
            $master = $requestData['payment_method_nonce'][$master];
        }

        if ($master) {
            $i = 1;
            $orders = [];
            if ($shipping && $brand) {
                $i = 2;
                $orders[] = $shipping;
                $orders[] = $brand;
            }

            if ($shipping && !$brand) {
                $i = 2;
                $orders[] = $shipping;
                $orders[] = $master;
            }

            if (!$shipping && $brand) {
                $i = 2;
                $orders[] = $brand;
                $orders[] = $master;
            }

            if (!$shipping && !$brand) {
                $i = 1;
                $orders[] = $master;
            }

            $responses = [];
            for ($j = 0; $j < $i; $j++) {
                $continue = true;
                $partnerData = [];
                if ($i > 1) {
                    $this->_splitResource->load(
                        $this->_splitModel,
                        $orders[$j]->id,
                        'split_id'
                    );
                    if ($this->_splitModel->getId()) {
                        $partnerData = $requestData;
                        $partnerData['secret'] = $this->_splitModel->getSecret();
                        $partnerData['merchant_id'] = $this->_splitModel->getMerchantId();
                        $partnerData['terminal'] = $this->_splitModel->getTerminalId();

                        if ($orders[$j]->type === 'shipping') {
                            $partnerData['amount'] = $requestData['shipping_amount'];
                            $partnerData['description'] = 'Envío - ' . $this->_splitModel->getName();
                        } else {
                            $partnerData['amount'] = ($requestData['amount'] - $requestData['shipping_amount']);
                            $partnerData['description'] = $this->_splitModel->getName() . ' - Orden #' . ($requestData['order_id']) ?? '';
                        }
                    } else {
                        $continue = false;
                    }
                } else {
                    $partnerData = $requestData;
                }

                unset($partnerData['payment_method_nonce']);
                unset($partnerData['shipping']);
                unset($partnerData['shipping_amount']);

                if ($continue) {
                    if ($orderData = $this->getPaymentOrder($partnerData)) {
                        $parameters = [
                            'session' => ($orderData['session']) ?? '',
                            'ld' => ($orders[$j]->ld) ?? '',
                            'lk' => ($orders[$j]->lk) ?? ''
                        ];
                        $this->_curl->setHeaders([
                            "Content-Type" => "application/json",
                            "Accept" => "application/json",
                            "liszt-token" => ($orderData['token']) ?? '',
                        ]);
                        $this->_curl->post($url, json_encode($parameters));
                        if ($response = $this->_curl->getBody()) {
                            $response = (array)json_decode($response);
                            $responses[] = $response;
                            $this->logger->debug($response);
                        }
                    }
                } else {
                    $responses[] = false;
                }
            }

            return $responses;
        }

        return false;
    }

    /**
     * @param $requestData
     * @return bool|mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getPaymentOrder($requestData)
    {
        $parameters = [
            'secret' => ($requestData['secret']) ?? '',
            'merchantId' => ($requestData['merchant_id']) ?? '',
            'terminal' => ($requestData['terminal']) ?? '',
            'description' => ($requestData['description'] ?? ''),
            'amount' => (float)($requestData['amount']) ?? '',
            'currency' => $this->_storeManager->getStore()->getBaseCurrencyCode(),
            'orderReference' => ($requestData['order_id']) ?? '',
            'callback' => '',
        ];

        $url = self::PAYMENT_URL;
        if (isset($requestData['sandbox'])) {
            if ($requestData['sandbox']) {
                $url = self::SANDBOX_PAYMENT_URL;
            }
        }

        $headers = ["Content-Type" => "application/json"];
        $this->_curl->setHeaders($headers);
        $this->_curl->post($url, json_encode($parameters));
        if ($response = $this->_curl->getBody()) {
            $response = (array)json_decode($response);
            if (is_array($response)) {
                if (isset($response['session']) & isset($response['token'])) {
                    return $response;
                } else {
                    $this->logger->debug($response);
                }
            }
        }

        return false;
    }
}
