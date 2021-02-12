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

use Iways\PayPalPlus\Api\GuestPPPPaymentInformationManagementInterface as ClassInterface;
use Iways\PayPalPlus\Model\PaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;

class GuestPPPPaymentInformationManagement extends PaymentInformationManagement implements ClassInterface
{
    /**
     * Protected $billingAddressManagement
     *
     * @var \Magento\Quote\Api\GuestBillingAddressManagementInterface
     */
    protected $billingAddressManagement;

    /**
     * Protected $paymentMethodManagement
     *
     * @var \Magento\Quote\Api\GuestPaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * Protected $cartManagement
     *
     * @var \Magento\Quote\Api\GuestCartManagementInterface
     */
    protected $cartManagement;

    /**
     * Protected $paymentInformationManagement
     *
     * @var \Magento\Checkout\Api\PaymentInformationManagementInterface
     */
    protected $paymentInformationManagement;

    /**
     * Protected $quoteIdMaskFactory
     *
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Protected $cartRepository
     *
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * GuestPPPPaymentInformationManagement constructor
     *
     * @param \Iways\PayPalPlus\Model\ApiFactory $payPalPlusApiFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement
     * @param \Magento\Quote\Api\GuestPaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Quote\Api\GuestCartManagementInterface $cartManagement
     * @param \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Iways\PayPalPlus\Model\ApiFactory $payPalPlusApiFactory,
        CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\GuestPaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\GuestCartManagementInterface $cartManagement,
        \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartManagement = $cartManagement;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $quoteRepository;
        parent::__construct($payPalPlusApiFactory, $quoteRepository, $customerSession);
    }

    /**
     * {@inheritDoc}
     */
    public function savePaymentInformation(
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        if ($billingAddress) {
            $billingAddress->setEmail($email);
            $this->billingAddressManagement->assign($cartId, $billingAddress);
        } else {
            $this->cartRepository->getActive($quoteIdMask->getQuoteId())->getBillingAddress()->setEmail($email);
        }
        $paymentMethod = $this->handleComment($paymentMethod);
        $this->paymentMethodManagement->set($cartId, $paymentMethod);

        try {
            $this->patchPayment($quoteIdMask->getQuoteId());
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
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->paymentInformationManagement->getPaymentInformation($quoteIdMask->getQuoteId());
    }
}
