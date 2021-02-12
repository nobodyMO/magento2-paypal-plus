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

namespace Iways\PayPalPlus\Model\PaymentInformationManagement;

use Iways\PayPalPlus\Api\PPPPaymentInformationManagementInterface as ClassInterface;
use Iways\PayPalPlus\Model\PaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;

class PPPPaymentInformationManagement extends PaymentInformationManagement implements ClassInterface
{
    /**
     * Protected $billingAddressManagement
     *
     * @var \Magento\Quote\Api\BillingAddressManagementInterface
     */
    protected $billingAddressManagement;

    /**
     * Protected $paymentMethodManagement
     *
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * Protected $cartManagement
     *
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * Protected $paymentDetailsFactory
     *
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;

    /**
     * Protected $cartTotalsRepository
     *
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * PPPPaymentInformationManagement constructor
     *
     * @param \Iways\PayPalPlus\Model\ApiFactory $payPalPlusApiFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     */
    public function __construct(
        \Iways\PayPalPlus\Model\ApiFactory $payPalPlusApiFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
    ) {
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartManagement = $cartManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        parent::__construct($payPalPlusApiFactory, $quoteRepository, $customerSession);
    }

    /**
     * {@inheritDoc}
     */
    public function savePaymentInformation(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($billingAddress) {
            $this->billingAddressManagement->assign($cartId, $billingAddress);
        }
        $paymentMethod = $this->handleComment($paymentMethod);
        $this->paymentMethodManagement->set($cartId, $paymentMethod);

        try {
            $this->patchPayment($cartId);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
            return false;            
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentInformation($cartId)
    {
        $paymentDetails = $this->paymentDetailsFactory->create();
        $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
        $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));
        return $paymentDetails;
    }
}
