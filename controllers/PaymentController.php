<?php
/**
 * Sofortueberweisung.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (http://www.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

use CoreShop\Controller\Action\Payment;
use CoreShop\Tool;

/**
 * Class Sofortueberweisung_PaymentController
 */
class Sofortueberweisung_PaymentController extends Payment
{
    public function paymentAction()
    {
        $configkey = \CoreShop\Model\Configuration::get('SOFORTUEBERWEISUNG.KEY');

        $sofort = new \Sofort\SofortLib\Sofortueberweisung($configkey);
        $sofort->setAmount(Tool::numberFormat($this->cart->getTotal()));
        $sofort->setVersion('CoreShop '.\CoreShop\Version::getVersion());
        $sofort->setReason('Buy Order (CoreShop)');
        $sofort->setCurrencyCode(Tool::getCurrency()->getIsoCode());
        $sofort->setSuccessUrl(Pimcore\Tool::getHostUrl().$this->getModule()->url($this->getModule()->getIdentifier(), 'payment-return'));
        $sofort->setAbortUrl(Pimcore\Tool::getHostUrl().$this->getModule()->url($this->getModule()->getIdentifier(), 'payment-return-abort'));
        $sofort->sendRequest();

        if ($sofort->isError()) {
            var_dump($sofort);
            die('error');
        } else {
            $transactionId = $sofort->getTransactionId();

            $this->cart->setCustomIdentifier($transactionId);
            $this->cart->save();

            $this->redirect($sofort->getPaymentUrl());
        }
    }

    public function paymentReturnAction()
    {
        if (!$this->cart instanceof \CoreShop\Model\Cart) {
            $this->redirect($this->view->url(array(), 'coreshop_index'));
        }

        $configkey = \CoreShop\Model\Configuration::get('SOFORTUEBERWEISUNG.KEY');

        $SofortLibTransactionData = new \Sofort\SofortLib\TransactionData($configkey);
        $SofortLibTransactionData->addTransaction($this->cart->getCustomIdentifier());
        $SofortLibTransactionData->sendRequest();

        if ($SofortLibTransactionData->getStatus() === 'received' || $SofortLibTransactionData->getStatus() === 'pending') {
            $order = $this->cart->createOrder(\CoreShop\Model\Order\State::getById(\CoreShop\Model\Configuration::get("SYSTEM.ORDERSTATE.PAYMENT")), $this->getModule(), $this->cart->getTotal(), $this->view->language);

            $payments = $order->getPayments();

            foreach ($payments as $p) {
                $dataBrick = $p->getPaymentInformation()->getCoreShopPaymentSofortueberweisung();

                if (!$dataBrick) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentSofortueberweisung($p);
                }

                $dataBrick->setTransactionId($this->cart->getCustomIdentifier());
                $dataBrick->setStatus($SofortLibTransactionData->getStatus());
                $dataBrick->setStatusReason($SofortLibTransactionData->getStatusReason());
                $dataBrick->setStatusModifiedTime($SofortLibTransactionData->getStatusModifiedTime());
                $dataBrick->setLanguageCode($SofortLibTransactionData->getLanguageCode());
                $dataBrick->setCurrency($SofortLibTransactionData->getCurrency());

                $p->save();
            }

            $this->redirect($this->getModule()->getConfirmationUrl($order));
        }

        $this->redirect($this->view->url(array(), 'coreshop_index'));
    }

    public function paymentReturnAbortAction()
    {
        $this->redirect($this->view->url(array(), 'coreshop_index'));
    }

    public function confirmationAction()
    {
        $orderId = $this->getParam('order');

        if ($orderId) {
            $order = \CoreShop\Model\Order::getById($orderId);

            if ($order instanceof \CoreShop\Model\Order) {
                $this->session->order = $order;
            }
        }

        parent::confirmationAction();
    }

    /**
     * @return Sofortueberweisung\Shop
     */
    public function getModule()
    {
        return parent::getModule();
    }
}
