<?php

namespace Bananacode\GreenPay\Model;

class Split extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     *
     */
    const CACHE_TAG = 'bananacode_greenpay_split';

    /**
     * @var string
     */
    protected $_cacheTag = 'bananacode_greenpay_split';

    /**
     * @var string
     */
    protected $_eventPrefix = 'bananacode_greenpay_split';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Bananacode\GreenPay\Model\ResourceModel\Split');
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
