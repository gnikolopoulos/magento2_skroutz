<?php
/**
 * Copyright © Interactive Design 2026 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ID\Skroutz\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class Skroutz extends AbstractMethod
{

    protected $_code = "skroutz";
    protected $_isOffline = true;

    public function isAvailable(?CartInterface $quote = null): bool
    {
        $email = $quote->getBillingAddress()->getEmail();
        if ($this->getConfigData('email') != $email) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}

