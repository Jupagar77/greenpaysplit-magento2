<?php

namespace Bananacode\GreenPay\Block\Adminhtml;

/**
 * Class Split
 * @package Bananacode\GreenPay\Block\Adminhtml
 */
class Split extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_split';
        $this->_blockGroup = 'Bananacode_GreenPay';
        $this->_headerText = __('Split Payments');
        $this->_addButtonLabel = __('Add Split Partner');
        parent::_construct();
    }
}

