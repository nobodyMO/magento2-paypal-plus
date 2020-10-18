<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 7.3.17
 *
 * @category Modules
 * @package  Magento
 * @author   Robert Hillebrand <hillebrand@i-ways.net>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License 3.0
 * @link     https://www.i-ways.net
 */

namespace Iways\PayPalPlus\Controller\Order;

use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Request\Http;

use Zend\Log\Writer\Stream;
use Zend\Log\Logger;

/**
 * PayPalPlus checkout controller
 *
 * @author robert
 */
class Create extends \Magento\Framework\App\Action\Action
{
    const MAX_SEND_MAIL_VERSION = '2.2.6';
    /**
     * Protected $logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Protected $checkoutSession
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Protected $checkoutHelper
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * Protected $customerSession
     *
     * @var Session
     */
    protected $customerSession;

    /**
     * Protected $cartManagement
     *
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * Protected $guestCartManagement
     *
     * @var \Magento\Quote\Api\GuestCartManagementInterface
     */
    protected $guestCartManagement;

    /**
     * Protected $quoteIdMaskFactory
     *
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Protected $orderFactory
     *
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * Protected $orderSender
     *
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * Protected $historyFactory
     *
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
    protected $historyFactory;

    /**
     * Protected $productMetadata
     *
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;
	
    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    protected $_responseFactory;
	
    /**
     * @var \Magento\Framework\App\Request\Http
     */
	protected $request;
	

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Api\GuestCartManagementInterface $guestCartManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        OrderSender $orderSender,
        OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory,
        Session $customerSession,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\ResponseFactory $responseFactory,
		\Magento\Framework\App\Request\Http $request
    ) {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->cartManagement = $cartManagement;
        $this->guestCartManagement = $guestCartManagement;
        $this->customerSession = $customerSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderSender = $orderSender;
        $this->orderFactory = $orderFactory;
        $this->historyFactory = $historyFactory;
        $this->productMetadata = $productMetadata;
        $this->_responseFactory = $responseFactory;		
		$this->request = $request;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        try {
            $cartId = $this->checkoutSession->getQuoteId();		
			$this->printLog("cartId $cartId");
			$maskedId=$this->request->getParam('quote_id');
			if (substr($maskedId, -1)=='/') $maskedId=substr($maskedId, 0, -1); // remove last / added by paypal
			$this->printLog("maskedId from url $maskedId");
			
            $result = new DataObject();
            if ($cartId) {
				$this->printLog("use quote id from session context");
				if ($this->customerSession->isLoggedIn()) {
					$orderId = $this->cartManagement->placeOrder($cartId);
				} else {
					$quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'quote_id');
					$orderId = $this->guestCartManagement->placeOrder($quoteIdMask->getMaskedId());
				}
			} else {
					// fallback if session context not found after redirect
					$this->printLog("use quote id from url");					
					$orderId = $this->guestCartManagement->placeOrder($maskedId);				
			}
			$this->printLog("Try to create order for $orderId , $cartId");

            if ($orderId) {
                $order = $this->orderFactory->create()->load($orderId);
                if ($order->getCanSendNewEmailFlag()
                    && version_compare($this->productMetadata->getVersion(), self::MAX_SEND_MAIL_VERSION, '<')
                ) {
                    try {
						// MK Fix multiple confirmation mails
						if (!$order->getEmailSent()) {
							$this->orderSender->send($order);
							$this->printLog('Send Mail for (Create.php)');
						} else {
							$this->printLog('Order already sent (Create.php)');						
						}
                    } catch (\Exception $e) {
						$this->printLog("Caught $e");
                        $this->logger->critical($e);
                    }
                }
                try {
                    // IWD_Opc Order Comment
                    if ($this->customerSession->getOrderComment()) {
                        if ($order->getData('entity_id')) {
                            $status = $order->getData('status');
                            $history = $this->historyFactory->create();
                            // set comment history data
                            $history->setData('comment', strip_tags($this->customerSession->getOrderComment()));
                            $history->setData('parent_id', $orderId);
                            $history->setData('is_visible_on_front', 1);
                            $history->setData('is_customer_notified', 0);
                            $history->setData('entity_name', 'order');
                            $history->setData('status', $status);
                            $history->save();
                            $this->customerSession->setOrderComment(null);
                        }
                    }
                } catch (\Exception $e) {
					$this->printLog("Caught $e");
                    $this->logger->log($e);
                }
            }
			// MK Fix success page
			$this->checkoutSession->start();
			$this->checkoutSession->setLastQuoteId($cartId);
			$this->checkoutSession->setLastSuccessQuoteId($cartId);
			$this->checkoutSession->setLastOrderId($order->getId());
			$this->checkoutSession->setLastRealOrderId($order->getIncrementId());
			$this->checkoutSession->setLastOrderStatus($order->getStatus());
			
			$this->printLog('C:getLastSuccessQuoteId:'.$this->checkoutSession->getLastSuccessQuoteId());
			$this->printLog('C:getLastQuoteId:'.$this->checkoutSession->getLastQuoteId());
			$this->printLog('C:getLastOrderId:'.$this->checkoutSession->getLastOrderId());
			
            $result->setData('success', true);
            $result->setData('error', false);

            $this->_eventManager->dispatch(
                'checkout_controller_onepage_saveOrder',
                [
                    'result' => $result,
                    'action' => $this
                ]
            );
			$response = $this->_responseFactory->create();
			$response->setRedirect('/checkout/onepage/success');
			$response->setNoCacheHeaders();
			return $response;			
            //$this->_redirect('checkout/onepage/success');
        } catch (\Exception $e) {
 			$this->printLog("Caught $e");
            $this->messageManager->addError($e->getMessage());
			$response = $this->_responseFactory->create();
			$response->setRedirect('/checkout/cart');
			$response->setNoCacheHeaders();
			return $response;			
            //$this->_redirect('checkout/cart');
        }
    }
	
	public function printLog($log) { 
       $writer = new Stream(BP . '/var/log/checkoutSuccess.log');
       $logger = new Logger();
       $logger->addWriter($writer);
       $logger->info($log);
	}	
	
}
