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
 * @author   Matteo Bertozzi <bertozzi@i-ways.net>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License 3.0
 * @link     https://www.i-ways.net
 */
namespace Iways\PayPalPlus\Observer;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class TpmiObserver implements ObserverInterface
{
    /**
     * Protected $request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Protected $writer
     *
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $writer;

    public function __construct(
        RequestInterface $request,
        WriterInterface $writer
    ) {
        $this->request = $request;
        $this->writer = $writer;
    }

    public function execute(Observer $observer)
    {
        $groups = $this->request->getParam('groups');

        if (isset($groups['iways_paypalplus_section']['groups']['third_party_modul_info'])) {
            $tpmiFields = $groups['iways_paypalplus_section']['groups']['third_party_modul_info']['fields'];
            foreach ($tpmiFields as $key => $value) {
				if (array_key_exists('value', $value))
					$this->writer->save('payment/iways_paypalplus_section/third_party_modul_info_' . $key, $value['value']);
             }
        }

        return $this;
    }
}
