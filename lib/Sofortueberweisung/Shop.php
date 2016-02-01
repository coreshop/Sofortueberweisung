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

namespace Sofortueberweisung;

use CoreShop\Model\Cart;
use CoreShop\Model\Order;
use CoreShop\Model\Plugin\Payment as CorePayment;
use CoreShop\Plugin as CorePlugin;
use CoreShop\Tool;
use Payunity\Shop\Install;
use Sofort\SofortLib\Sofortueberweisung;

class Shop extends CorePayment
{
    public static $install;

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function attachEvents()
    {
        self::getInstall()->attachEvents();

        CorePlugin::getEventManager()->attach("payment.getProvider", function ($e) {
            return $this;
        });
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Sofortueberweisung";
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return "/plugins/Sofortueberweisung/static/img/sofortueberweisung.gif";
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return "Sofortueberweisung";
    }

    /**
     * @param Cart $cart
     * @return int
     */
    public function getPaymentFee(Cart $cart)
    {
        return 0;
    }

    /**
     * Process Validation for Payment
     *
     * @param Cart $cart
     * @return mixed
     */
    public function process(Cart $cart)
    {
        return $this->getProcessValidationUrl();
    }

    /**
     * Get url for confirmation link
     *
     * @param Order $order
     * @return string
     */
    public function getConfirmationUrl($order)
    {
        return $this->url($this->getIdentifier(), 'confirmation') . "?order=" . $order->getId();
    }

    /**
     * get url for validation link
     *
     * @return string
     */
    public function getProcessValidationUrl()
    {
        return $this->url($this->getIdentifier(), 'validate');
    }

    /**
     * get url payment link
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->url($this->getIdentifier(), 'payment');
    }

    /**
     * get error url
     *
     * @return string
     */
    public function getErrorUrl()
    {
        return $this->url($this->getIdentifier(), 'error');
    }

    /**
     * @return Install
     */
    public static function getInstall()
    {
        if (!self::$install) {
            self::$install = new Install();
        }
        return self::$install;
    }
}