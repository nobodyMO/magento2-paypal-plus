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

namespace Iways\PayPalPlus\Plugin\Sales\Model\Order;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Sales\Api\Data\OrderPaymentExtensionFactory;
use Magento\Sales\Model\Order\Payment;

class PaymentPlugin
{
    /**
     * Protected $orderPaymentExtensionFactory
     *
     * @var \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory
     */
    protected $orderPaymentExtensionFactory;

    /**
     * PaymentPlugin constructor
     *
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $orderPaymentExtensionFactory
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $orderPaymentExtensionFactory
    ) {
        $this->orderPaymentExtensionFactory = $orderPaymentExtensionFactory;
    }

    /**
     * Add stock item information to the product's extension attributes
     *
     * @param Payment $payment
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function afterGetExtensionAttributes(Payment $payment)
    {
        $paymentExtension = $payment->getData(AbstractExtensibleModel::EXTENSION_ATTRIBUTES_KEY);
        if ($paymentExtension === null) {
            $paymentExtension = $this->orderPaymentExtensionFactory->create();
        }
        $pppAttributes = [
            'ppp_reference_number',
            'ppp_instruction_type',
            'ppp_payment_due_date',
            'ppp_note',
            'ppp_bank_name',
            'ppp_account_holder_name',
            'ppp_international_bank_account_number',
            'ppp_bank_identifier_code',
            'ppp_routing_number',
            'ppp_amount',
            'ppp_currency'
        ];

        foreach ($pppAttributes as $pppAttribute) {
            $paymentExtension->setData($pppAttribute, $payment->getData($pppAttribute));
        }

        $payment->setExtensionAttributes($paymentExtension);
        return $paymentExtension;
    }
}
