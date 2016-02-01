<?php
/**
 * Sofortueberweisung
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015 Dominik Pfaffenbauer (http://dominik.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

use CoreShop\Controller\Action\Payment;
use Pimcore\Model\Object\CoreShopPayment;
use CoreShop\Tool;

class Sofortueberweisung_PaymentController extends Payment
{
    public function paymentAction() {
        $configkey = \CoreShop\Model\Configuration::get("SOFORTUEBERWEISUNG.KEY");

        $sofort = new \Sofort\SofortLib\Sofortueberweisung($configkey);
        $sofort->setAmount(Tool::numberFormat($this->cart->getTotal()));
        $sofort->setVersion("CoreShop " . \CoreShop\Version::getVersion());
        $sofort->setReason("Buy Order (CoreShop)");
        $sofort->setCurrencyCode(Tool::getCurrency()->getIsoCode());
        $sofort->setSuccessUrl(Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return"));
        $sofort->setAbortUrl(Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "payment-return-abort"));
        $sofort->setNotificationUrl(Pimcore\Tool::getHostUrl() . $this->getModule()->url($this->getModule()->getIdentifier(), "notification"));
        $sofort->sendRequest();

        if($sofort->isError())
        {
            var_dump($sofort);
            die("error");
        }
        else
        {
            $transactionId = $sofort->getTransactionId();

            $this->cart->setCustomIdentifier($transactionId);
            $this->cart->save();

            $this->redirect($sofort->getPaymentUrl());
        }
    }

    public function paymentReturnAction()
    {
        if(!$this->cart instanceof \CoreShop\Model\Cart) {
            $this->redirect($this->view->url(array(), "coreshop_index"));
        }

        if($this->cart->getOrder() instanceof \CoreShop\Model\Order) {
            $this->redirect($this->getModule()->getConfirmationUrl($this->cart->getOrder()));
        }
        else {
            //Sofortüberweißgung did return to success, but we did not yet got notified, so we create the order
            $order = $this->getModule()->createOrder($this->cart, \CoreShop\Model\OrderState::getById(\CoreShop\Model\Configuration::get("SYSTEM.ORDERSTATE.PREPERATION")), $this->cart->getTotal(), $this->view->language); //TODO: Fix Language

            $this->redirect($this->getModule()->getConfirmationUrl($order));
        }

        $this->redirect($this->view->url(array(), "coreshop_index"));
    }

    public function paymentReturnAbortAction()
    {
        $this->redirect($this->view->url(array(), "coreshop_index"));
    }

    public function notificationAction() {
        $configkey = \CoreShop\Model\Configuration::get("SOFORTUEBERWEISUNG.KEY");

        $SofortLib_Notification = new \Sofort\SofortLib\Notification();

        $transaction = $SofortLib_Notification->getNotification(file_get_contents('php://input'));
        $SofortLib_Notification->getTransactionId();

        $SofortLibTransactionData = new \Sofort\SofortLib\TransactionData($configkey);
        $SofortLibTransactionData->addTransaction($transaction);
        $SofortLibTransactionData->sendRequest();

        $cart = \CoreShop\Model\Cart::findByCustomIdentifier($SofortLib_Notification->getTransactionId());

        \Logger::info("Sofortüberweißung: Status:" . $SofortLibTransactionData->getStatus());

        if($SofortLibTransactionData->getStatus() === "received" || $SofortLibTransactionData->getStatus() === "pending") {
            if(!$cart->getOrder() instanceof \CoreShop\Model\Order)
            {
                $order = $this->getModule()->createOrder($cart, \CoreShop\Model\OrderState::getById(\CoreShop\Model\Configuration::get("SYSTEM.ORDERSTATE.PAYMENT")), $cart->getTotal(), "en"); //TODO: Fix Language
            }
            else
            {
                //Maybe order was already created by payment-return action?
                $order = $cart->getOrder();
            }

            $payments = $order->getPayments();

            foreach ($payments as $p) {
                $dataBrick = $p->getPaymentInformation()->getCoreShopPaymentSofortueberweisung();

                if(!$dataBrick) {
                    $dataBrick = new \Pimcore\Model\Object\Objectbrick\Data\CoreShopPaymentSofortueberweisung($p);
                }

                $dataBrick->setTransactionId($SofortLib_Notification->getTransactionId());
                $dataBrick->setStatus($SofortLibTransactionData->getStatus());
                $dataBrick->setStatusReason($SofortLibTransactionData->getStatusReason());
                $dataBrick->setStatusModifiedTime($SofortLibTransactionData->getStatusModifiedTime());
                $dataBrick->setLanguageCode($SofortLibTransactionData->getLanguageCode());
                $dataBrick->setCurrency($SofortLibTransactionData->getCurrency());

                $p->save();
            }
        }

        exit;
    }

    public function confirmationAction()
    {
        $orderId = $this->getParam("order");

        if ($orderId) {
            $order = \Pimcore\Model\Object\CoreShopOrder::getById($orderId);

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
