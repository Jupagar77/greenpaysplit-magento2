<?php
namespace Bananacode\GreenPay\Model\ResourceModel;

/**
 * Class Split
 * @package Bananacode\GreenPay\Model\ResourceModel
 */
class Split extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Split constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('greenpay_split', 'split_id');
    }
}
