<?php

namespace ID\Skroutz\Block\Checkout\Onepage;

class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * @return int
     */
    public function getTrackingCode()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();

        $html = "<script>
                skroutz_analytics('ecommerce', 'addOrder', {
                    order_id: ".$order->getId().",
                    revenue:  ".$order->getGrandTotal().",
                    shipping: ".$order->getShippingAmount().",
                    tax:      ".$order->getTaxAmount()."
                });
            </script>
        ";

        $orderItems = $order->getAllVisibleItems();

        foreach($orderItems as $item) {
            $html .= "<script>
                skroutz_analytics('ecommerce', 'addItem', {
                    order_id:   ".$order->getId().",
                    product_id: ".$item->getId().",
                    name:       ".$item->getName().",
                    price:      ".$item->getPrice().",
                    quantity:   ".$item->getQtyOrdered()."
                });
            </script>
            ";
        }

        return $html;
    }
}