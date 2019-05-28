<?php

namespace ID\Skroutz\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

  const XML_PATH_FEED = 'skroutz/';

  public function getConfigValue($field, $storeId = null)
  {
    return $this->scopeConfig->getValue(
      $field, ScopeInterface::SCOPE_STORE, $storeId
    );
  }

  public function getGeneralConfig($code, $storeId = null)
  {
    return $this->getConfigValue(self::XML_PATH_FEED .'general/'. $code, $storeId);
  }

  public function getProductsConfig($code, $storeId = null)
  {
    return $this->getConfigValue(self::XML_PATH_FEED .'product_collection/'. $code, $storeId);
  }

  public function getMessagesConfig($code, $storeId = null)
  {
    return $this->getConfigValue(self::XML_PATH_FEED .'messages/'. $code, $storeId);
  }

  public function getAnalyticsConfig($code, $storeId = null)
  {
    return $this->getConfigValue(self::XML_PATH_FEED .'analytics/'. $code, $storeId);
  }

}
