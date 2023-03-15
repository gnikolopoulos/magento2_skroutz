<?php

namespace ID\Skroutz\Block\Checkout\Onepage;

class Success extends \Magento\Checkout\Block\Onepage\Success
{
	protected $_productRepository;
	protected $_helper;
	
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
	 * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
	 * @param \ID\Skroutz\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\ID\Skroutz\Helper\Data $helper,
        array $data = []
    ) 
	{
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
		$this->_productRepository = $productRepository;
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

		if(true || ($order->getGrandTotal() < 48 && rand(1, 10) <= 9) || rand(1, (int) ($order->getGrandTotal() / 10)) == 1 ) {
			$html = "<script type=\"text/javascript\">
					skroutz_analytics('ecommerce', 'addOrder', {
						order_id: '".$order->getIncrementId()."',
						revenue:  '".($order->getGrandTotal())."',
						shipping: '".round($order->getShippingAmount() * 1.24, 2)."',
						tax:      '".$order->getTaxAmount()."'
					});
				</script>
			";

			$orderItems = $order->getAllVisibleItems();

			$productRepository = $this->_productRepository;
			foreach($orderItems as $item) 
			{
				$productId = $item->getProductId();
				$outputProductId = $productId;
				$skroutzVersion = $productRepository->getById($productId)->getSkroutzVersion();
				if($skroutzVersion)
				{
					$outputProductId .= '-' . $skroutzVersion;
				}
				
				$html .= "<script type=\"text/javascript\">
					skroutz_analytics('ecommerce', 'addItem', {
						order_id:   '".$order->getIncrementId()."',
						product_id: '".$outputProductId."',
						name:       '".addslashes($item->getName())."',
						price:      '".$item->getPriceInclTax()."',
						quantity:   '".$item->getQtyOrdered()."'
					});
				</script>
				";
			}
		} else {
			$html = "";
		}

        return $html;
    }
}