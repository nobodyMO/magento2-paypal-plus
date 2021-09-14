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
namespace Iways\PayPalPlus\Model;

use Iways\PayPalPlus\Block\PaymentInfo;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;


/**
 * Class Payment model
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PPP_STATUS_APPROVED = 'approved';
    const CODE = 'iways_paypalplus_payment';
    const PPP_PENDING = 'pending';

    const PPP_INSTRUCTION_TYPE = 'PAY_UPON_INVOICE';

    protected $_code = self::CODE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_isGateway = true; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canOrder = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canAuthorize = true; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canCapture = true; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canCapturePartial = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canCaptureOnce = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canRefund = true; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canRefundInvoicePartial = true; // phpcs:ignore PSR2.Classes.PropertyDeclaration
    protected $_canUseCheckout = true; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Protected $_infoBlockType
     *
     * @var string
     */
    protected $_infoBlockType = PaymentInfo::class; // phpcs:ignore PSR2.Classes.PropertyDeclaration

    /**
     * Protected $request
     *
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * Protected $scopeConfig
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Protected $payPalPlusApiFactory
     *
     * @var \Iways\PayPalPlus\Model\ApiFactory
     */
    protected $payPalPlusApiFactory;

    /**
     * Protected $logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Protected $customerSession
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Protected $payPalPlusHelpe
     *
     * @var \Iways\PayPalPlus\Helper\Data
     */
    protected $payPalPlusHelper;

    /**
     * Protected $salesOrderPaymentTransactionFactory
     *
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $salesOrderPaymentTransactionFactory;

    /**
     * Protected $ppLogger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $ppLogger;

 
   /**
     * Protected $quoteFactory
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
	protected $quoteRepository;

/**
     * @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;
	
    /**
     * Payment constructor
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param ApiFactory $payPalPlusApiFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Iways\PayPalPlus\Helper\Data $payPalPlusHelper
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $salesOrderPaymentTransactionFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId	 
	 * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Payment\Model\Method\Logger $logger
	 * @param \Psr\Log\LoggerInterface $logger2
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection,	
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ApiFactory $payPalPlusApiFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Iways\PayPalPlus\Helper\Data $payPalPlusHelper,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $salesOrderPaymentTransactionFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,		
		\Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Payment\Model\Method\Logger $logger,
		\Psr\Log\LoggerInterface $logger2,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,		
        array $data = []
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->payPalPlusApiFactory = $payPalPlusApiFactory;
        $this->customerSession = $customerSession;
        $this->payPalPlusHelper = $payPalPlusHelper;
        $this->salesOrderPaymentTransactionFactory = $salesOrderPaymentTransactionFactory;
		$this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;		
		$this->quoteRepository = $quoteRepository;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        //$this->ppLogger = $context->getLogger();
		$this->ppLogger = $logger2;
    }

    /**
     * Authorize payment method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     *
     * @throws \Exception Payment could not be executed
     *
     * @return \Iways\PayPalPlus\Model\Payment
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $paymentId = $this->request->getParam('paymentId');
        $payerId = $this->request->getParam('PayerID');
        $maskedQuoteID = $this->request->getParam('quote_id');
		if (substr($maskedQuoteID, -1)=='/') $maskedQuoteID=substr($maskedQuoteID, 0, -1); // remove last / added by paypal
		
		$this->ppLogger->info ('Payment authorize for payment ' . $paymentId . ' payerID ' . $payerId . ' quoteID ' .  $maskedQuoteID . ' amount ' . $amount);

        try {
            if ($this->scopeConfig->getValue(
                'payment/iways_paypalplus_payment/transfer_reserved_order_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ) {
                $this->payPalPlusApiFactory->create()->patchInvoiceNumber(
                    $paymentId,
                    $payment->getOrder()->getIncrementId()					
                );
            }
			$ppPayment=$this->payPalPlusApiFactory->create()->getPayment($paymentId);
			$ppAmount=floatval($ppPayment->getTransactions()[0]->getAmount()->getTotal());			
			$this->ppLogger->info ('Payment authorize ppAmount ' .  $ppAmount);
			if ($amount!=$ppAmount){
				$this->ppLogger->info ('Payment authorize amount different. Try to patch');
				$quoteId=$this->maskedQuoteIdToQuoteId->execute($maskedQuoteID);
				$quote = $this->quoteRepository->get($quoteId);
				$this->payPalPlusApiFactory->create()->patchPayment($quote);
			}
			
			
        } catch (\Exception $e) {      
			$this->ppLogger->critical($e);
        }

        $ppPayment = $this->payPalPlusApiFactory->create()->executePayment(
            $paymentId,
            $payerId
        );

        $this->customerSession->setPayPalPaymentId(null);
        $this->customerSession->setPayPalPaymentPatched(null);

        if (!$ppPayment) {
            throw new LocalizedException(
                __('Payment could not be executed.')
            );
        }

        if ($paymentInstructions = $ppPayment->getPaymentInstruction()) {
            $payment->setData('ppp_reference_number', $paymentInstructions->getReferenceNumber());
            $payment->setData('ppp_instruction_type', $paymentInstructions->getInstructionType());
            $payment->setData(
                'ppp_payment_due_date',
                $this->payPalPlusHelper->convertDueDate($paymentInstructions->getPaymentDueDate())
            );
            $payment->setData('ppp_note', $paymentInstructions->getNote());

            $bankInsctructions = $paymentInstructions->getRecipientBankingInstruction();
            $payment->setData('ppp_bank_name', $bankInsctructions->getBankName());
            $payment->setData('ppp_account_holder_name', $bankInsctructions->getAccountHolderName());
            $payment->setData(
                'ppp_international_bank_account_number',
                $bankInsctructions->getInternationalBankAccountNumber()
            );
            $payment->setData('ppp_bank_identifier_code', $bankInsctructions->getBankIdentifierCode());
            $payment->setData('ppp_routing_number', $bankInsctructions->getRoutingNumber());

            $ppAmount = $paymentInstructions->getAmount();
            $payment->setData('ppp_amount', $ppAmount->getValue());
            $payment->setData('ppp_currency', $ppAmount->getCurrency());
        }

        $transactionId = null;
        try {
            $transactions = $ppPayment->getTransactions();

            if ($transactions && isset($transactions[0])) {
                $resource = $transactions[0]->getRelatedResources();
                if ($resource && isset($resource[0])) {
                    $sale = $resource[0]->getSale();
                    $transactionId = $sale->getId();
                    if ($sale->getState() == self::PPP_PENDING) {
                        $payment->setIsTransactionPending(true);
                    }
                }
            }
        } catch (\Exception $e) {
            $transactionId = $ppPayment->getId();
        }
        $payment->setTransactionId($transactionId)->setLastTransId($transactionId);

        if ($ppPayment->getState() == self::PPP_STATUS_APPROVED) {
            $payment->setIsTransactionApproved(true);
        }

        $payment->setStatus(self::STATUS_APPROVED)
            ->setIsTransactionClosed(false)
            ->setAmount($amount)
            ->setShouldCloseParentTransaction(false);
        if ($payment->isCaptureFinal($amount)) {
            $payment->setShouldCloseParentTransaction(true);
        }

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     *
     * @return $this|\Magento\Payment\Model\Method\AbstractMethod
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $ppRefund = $this->payPalPlusApiFactory->create()->refundPayment(
            $this->_getParentTransactionId($payment),
            $amount
        );
        $payment->setTransactionId($ppRefund->getId())->setTransactionClosed(1);
        return $this;
    }

    /**
     * Parent transaction id getter
     *
     * @param \Magento\Framework\DataObject $payment
     *
     * @return string
     */
    protected function _getParentTransactionId(DataObject $payment) // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $transaction = $this->salesOrderPaymentTransactionFactory->create()->load($payment->getLastTransId(), 'txn_id');
        if ($transaction && $transaction->getParentTxnId()) {
            return $transaction->getParentTxnId();
        }
        return $payment->getLastTransId();
    }

	
}
