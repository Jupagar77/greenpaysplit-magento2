<?php

namespace Bananacode\GreenPay\Block\Adminhtml\Split\Edit;

/**
 * Class Form
 * @package Bananacode\GreenPay\Block\Adminhtml\Split\Edit
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var $model \Bananacode\GreenPay\Model\Split */
        $model = $this->_coreRegistry->registry('greenpay_split');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );

        $form->setUseContainer(true);

        $form->setHtmlIdPrefix('split_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Partner Information')]
        );

        if ($model->getId()) {
            $fieldset->addField('split_id', 'hidden', ['name' => 'split_id']);
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'name'     => 'name',
                'label'    => __('Name'),
                'title'    => __('Name'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'type',
            'select',
            [
                'label' => __('Type'),
                'title' => __('Type'),
                'name' => 'type',
                'options' => [
                    "1" => "Brand",
                    "2" => "Shipping"
                ],
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'reference',
            'text',
            [
                'name'     => 'reference',
                'label'    => __('Partner ID/Code'),
                'title'    => __('Partner ID/Code'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'secret',
            'text',
            [
                'name'     => 'secret',
                'label'    => __('Secret'),
                'title'    => __('Secret'),
                'note'     => __(''),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'public_key',
            'textarea',
            [
                'name'     => 'public_key',
                'style'    => 'height:200px;',
                'label'    => __('Public Key'),
                'title'    => __('Public Key'),
                'disabled' => $isElementDisabled,
                'required' => true
            ]
        );

        $fieldset->addField(
            'terminal_id',
            'text',
            [
                'name'     => 'terminal_id',
                'label'    => __('Terminal ID'),
                'title'    => __('Terminal ID'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'merchant_id',
            'text',
            [
                'name'     => 'merchant_id',
                'label'    => __('Merchant ID'),
                'title'    => __('Merchant ID'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
