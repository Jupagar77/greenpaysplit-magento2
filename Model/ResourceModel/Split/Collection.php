<?php
namespace Bananacode\GreenPay\Model\ResourceModel\Split;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'split_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'bananacode_greenpay_split_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'split_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Bananacode\GreenPay\Model\Split',
            'Bananacode\GreenPay\Model\ResourceModel\Split'
        );
    }
}
