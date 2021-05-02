<?php
/**
 * Copyright © 2019 Bananacode SA, All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bananacode\GreenPay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\Config\Config;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'greenpay';

    const PUBLIC_KEY = 'public_key';

    const PUBLIC_KEY_SANDBOX = 'public_key_sandbox';

    /**
     * @var \Magento\Payment\Gateway\ConfigInterface
     */
    protected $config;

    /**
     * @var \Bananacode\GreenPay\Model\ResourceModel\Split\Collection
     */
    protected $_shippingPartnersCollection;

    /**
     * @var \Bananacode\GreenPay\Model\ResourceModel\Split\Collection
     */
    protected $_brandsPartnersCollection;

    /**
     * @var \Rokanthemes\Brand\Model\ResourceModel\Brand
     */
    public $_brandResource;

    /**
     * @var \Rokanthemes\Brand\Model\Brand
     */
    public $_brandModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param \Bananacode\GreenPay\Model\ResourceModel\Split\Collection $splitPaymentsCollection
     * @param \Rokanthemes\Brand\Model\ResourceModel\Brand $brandResource
     * @param \Rokanthemes\Brand\Model\Brand $brandModel
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        Config $config,
        \Bananacode\GreenPay\Model\ResourceModel\Split\Collection $splitPaymentsCollection,
        \Rokanthemes\Brand\Model\ResourceModel\Brand $brandResource,
        \Rokanthemes\Brand\Model\Brand $brandModel,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->config = $config;
        $this->_shippingPartnersCollection = $splitPaymentsCollection;
        $this->_brandsPartnersCollection = $splitPaymentsCollection;
        $this->_brandResource = $brandResource;
        $this->_brandModel = $brandModel;
        $this->_checkoutSession = $checkoutSession;
        $this->config->setMethodCode(self::CODE);
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $this->config->setMethodCode(self::CODE);

        $sandbox = $this->config->getValue('sandbox');

        /**
         * Split Payments Public Keys
         */
        $splitPartnersKeys = [];
        try {
            $shippingPartners =
                $this->_shippingPartnersCollection
                    ->addFieldToSelect(
                        '*'
                    )
                    ->addFieldToFilter(
                        'type',
                        ['eq' => 2]
                    )
                    ->load();
            foreach ($shippingPartners as $splitPartner) {
                $splitPartnersKeys[] = [
                    "reference" => $splitPartner->getReference(),
                    "key" => $splitPartner->getPublicKey(),
                    "type" => 'shipping',
                    "id" => $splitPartner->getId(),
                ];
            }
            unset($splitPartner);

            /**
             * @var \Magento\Quote\Model\Quote $quote
             */
            $quote = $this->_checkoutSession->getQuote();

            $this->_brandResource->load(
                $this->_brandModel,
                $quote->getBrandCode(),
                'brand_id'
            );

            if ($this->_brandModel->getId()) {
                if ((boolean)$this->_brandModel->getPaidPlan()) {
                    $this->_brandsPartnersCollection
                        ->clear()
                        ->getSelect()
                        ->reset(\Zend_Db_Select::WHERE);

                    $this->_shippingPartnersCollection
                        ->clear()
                        ->getSelect()
                        ->reset(\Zend_Db_Select::WHERE);

                    $brandPartner =
                        $this->_brandsPartnersCollection
                            ->addFieldToSelect(
                                '*'
                            )
                            ->addFieldToFilter(
                                'type',
                                ['eq' => 1]
                            )
                            ->addFieldToFilter(
                                'reference',
                                ['eq' => $this->_brandModel->getId()]
                            )->load();

                    if ($brandPartner->getFirstItem()) {
                        $splitPartnersKeys[] = [
                            $brand = $brandPartner->getFirstItem(),
                            "reference" => $brand->getReference(),
                            "key" => $brand->getPublicKey(),
                            "type" => 'brand',
                            "id" => $brand->getId(),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return [
            'payment' => [
                self::CODE => [
                    'publicKey' => $this->config->getValue($sandbox ? self::PUBLIC_KEY_SANDBOX : self::PUBLIC_KEY),
                    'partnerKeys' => $splitPartnersKeys,
                    'sandbox' => $sandbox
                ]
            ]
        ];
    }
}
