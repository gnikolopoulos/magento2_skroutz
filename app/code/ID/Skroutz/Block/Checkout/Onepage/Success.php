<?php

namespace ID\Skroutz\Block\Checkout\Onepage;

class Success extends \Magento\Checkout\Block\Onepage\Success
{
    protected $_helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \ID\Skroutz\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \ID\Skroutz\Helper\Data $helper,
        array $data = []
    )
    {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->_helper = $helper;
    }

    /**
     * @return int
     */
    public function getTrackingCode()
    {
        if (!$this->_helper->getAnalyticsConfig('enabled')) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();

        $html = "<script>
                skroutz_analytics('ecommerce', 'addOrder', {
                    order_id: '".$order->getIncrementId()."',
                    revenue:  '".($order->getSubtotalInclTax() + $order->getShippingAmount())."',
                    shipping: '".round($order->getShippingAmount(), 2)."',
                    tax:      '".$order->getTaxAmount()."'
                });
            </script>
        ";

        $orderItems = $order->getAllVisibleItems();
        foreach($orderItems as $item) {
            $html .= "<script>
                skroutz_analytics('ecommerce', 'addItem', {
                    order_id:   '".$order->getIncrementId()."',
                    product_id: '".$item->getProductId()."',
                    name:       '".addslashes($item->getName())."',
                    price:      '".$item->getPriceInclTax()."',
                    quantity:   '".$item->getQtyOrdered()."'
                });
            </script>
            ";
        }

        return $html;
    }
}