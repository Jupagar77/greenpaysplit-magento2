<?php

namespace Bananacode\GreenPay\Controller\Adminhtml\Split;

use Magento\Backend\App\Action;

/**
 * Class Edit
 * @package Bananacode\GreenPay\Controller\Adminhtml\Split
 */
class Edit extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bananacode_GreenPay::split_edit');
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bananacode_GreenPay::greenpay')
            ->addBreadcrumb(__('Partner'), __('Partner'))
            ->addBreadcrumb(__('Manage Partners'), __('Manage Partners'));
        return $resultPage;
    }

    /**
     * Edit Split Partner Page
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('split_id');
        $model = $this->_objectManager->create('Bananacode\GreenPay\Model\Split');


        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This partner no longer exits. '));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        // 3. Set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        $this->_coreRegistry->register('greenpay_split', $model);

        // 5. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();

        $resultPage->addBreadcrumb(
            $id ? __('Edit Partner') : __('New Partner'),
            $id ? __('Edit Partner') : __('New Partner')
        );

        $resultPage->getConfig()->getTitle()->prepend(__('Partners'));

        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getName() : __('New Partner'));

        return $resultPage;
    }
}
