<?php
/**
 * Skroutz.gr XML Feed Generator
 * Copyright (C) 2019 2019 Interactive Design
 *
 * This file is part of ID/Skroutz.
 *
 * ID/Skroutz is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace ID\Skroutz\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use ID\Skroutz\Helper\Data;

class Generator extends AbstractHelper
{

    protected $logger;
    protected $helper;
    protected $storeManager;
    protected $iterator;
    protected $productCollectionFactory;
    protected $productFactory;
    protected $categoryFactory;
    protected $stockFilter;
    protected $stockRegistry;

    private $store_name;
    private $xml_file_name;
    private $xml_path;
    private $file;
    private $show_outofstock;
    private $brand_attribute;
    private $excluded;
    private $instock_msg;
    private $nostock_msg;
    private $backorder_msg;
    private $base_url;
    private $media_url;
    private $xml;
    private $base_node;
    private $collection;
    private $notAllowed = array('Νο', 'Όχι', 'Root Catalog', 'Default Category');

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param ID\Skroutz\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\Iterator $iterator
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\CatalogInventory\Helper\Stock $stockFilter
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger,
        Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\Iterator $iterator,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockFilter = $stockFilter;
        $this->stockRegistry = $stockRegistry;
        $this->categoryFactory = $categoryFactory;
        parent::__construct($context);
    }

    public function generateXML(int $storeId)
    {
        $time_start = microtime(true);
        $this->init($storeId);
        $this->createXML();
        $this->openXML();
        $this->base_node = $this->xml->getElementsByTagName('products')->item(0);

        $this->getProducts($storeId);

        $this->xml->formatOutput = true;
        $this->xml->save($this->file);

        echo 'Done. Found: '.$this->collection->getSize().' products.'.PHP_EOL;
        $this->logger->info( 'XML Feed generated in: ' . number_format((microtime(true) - $time_start), 2) . ' seconds' );
    }

    private function init(int $storeId)
    {
        $this->store_name = $this->helper->getGeneralConfig('store_name', $storeId);
        $this->xml_file_name = $this->helper->getGeneralConfig('xml_filename', $storeId);
        $this->xml_path = $this->helper->getGeneralConfig('xml_path', $storeId);
        $this->file = $this->xml_path . $this->xml_file_name;

        $this->show_outofstock = $this->helper->getProductsConfig('show_out_of_stock', $storeId);
        $this->brand_attribute = $this->helper->getProductsConfig('brand_attribute', $storeId);
        $this->excluded = explode(',', $this->helper->getProductsConfig('excluded_categories', $storeId) ?: '');

        $this->instock_msg = $this->helper->getMessagesConfig('available', $storeId);
        $this->nostock_msg = $this->helper->getMessagesConfig('out_of_stock', $storeId);
        $this->backorder_msg = $this->helper->getMessagesConfig('backorder', $storeId);

        $this->base_url = $this->storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $this->media_url = $this->storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';
    }

    private function createXML()
    {
        $dom = new \DomDocument("1.0", "utf-8");
        $dom->formatOutput = true;

        $root = $dom->createElement($this->store_name);

        $stamp = $dom->createElement('created_at', date('Y-m-d H:i') );
        $root->appendChild($stamp);

        $nodes = $dom->createElement('products');
        $root->appendChild($nodes);

        $nameAttribute = $dom->createAttribute('name');
        $nameAttribute->value = $this->store_name;
        $root->appendChild($nameAttribute);

        $urlAttribute = $dom->createAttribute('url');
        $urlAttribute->value = $this->base_url;
        $root->appendChild($urlAttribute);

        $dom->appendChild($root);

        $dom->save($this->file);
    }

    private function openXML()
    {
        $this->xml = new \DOMDocument();
        $this->xml->formatOutput = true;
        $this->xml->load($this->file);
    }

    private function getProducts(int $storeId)
    {
        $this->collection = $this->productCollectionFactory->create();
        $this->collection->addAttributeToFilter('status', 1); //enabled
        $this->collection->addAttributeToFilter('visibility', 4); //catalog, search
        $this->collection->addAttributeToFilter('skroutz', 1);
        if( !$this->show_outofstock ) {
            $this->stockFilter->addInStockFilterToCollection($this->collection);
        }
        $this->collection->addStoreFilter($storeId);

        $this->iterator->walk(
            $this->collection->getSelect(),
            [[$this, 'productCallback']],
            [
                'store' => $storeId,
            ]
        );
    }

    public function productCallback($args)
    {
        $oProduct = $this->productFactory->create()
            ->setStoreId($args['store'])
            ->load($args['row']['entity_id']);
        $aCats = $this->getCategories($oProduct);

        $aData = array();

        $aData['id'] = $oProduct->getId();
        $aData['mpn'] = mb_substr($oProduct->getSku(),0,99,'UTF-8');
        $aData['brand'] = strtoupper( $oProduct->getAttributeText($this->brand_attribute) );
        $aData['title'] = mb_substr($oProduct->getName(),0,299,'UTF-8');
        $aData['description'] = strip_tags($oProduct->getDescription() ?: '');
        $aData['price'] = preg_replace('/,/', '.', $oProduct->getFinalPrice());

        $aData['link'] = mb_substr($oProduct->getProductUrl(),0,299,'UTF-8');
        $aData['image_link_large'] = mb_substr($this->media_url.$oProduct->getData('image'),0,399,'UTF-8');

        $mediaGallery = $oProduct->getMediaGalleryImages();
        if( count($mediaGallery) > 0 ) {
            foreach ($mediaGallery as $image) {
                if( $image->getPosition() > 1 ) {
                    $aData['additional_imageurl'][] = $image->getUrl();
                }
            }
        }

        if( $oProduct->isSalable() ) {
            $aData['stock'] = 'Y';
            $aData['stock_descrip'] = $this->instock_msg;
        } else {
            $aData['stock'] = 'N';
            $aData['stock_descrip'] = $this->nostock_msg;
        }

        $aData['categoryid'] = array_key_exists('cid', $aCats) ? $aCats['cid'] : '';
        $aData['category'] = array_key_exists('bread', $aCats) ? $aCats['bread'] : '';

        if( $oProduct->hasData('color') ) {
            $aData['color'] = @mb_substr($oProduct->getAttributeText('color'),0,99,'UTF-8');
        } else {
            $aData['color'] = '';
        }

        if( $oProduct->getTypeId() == 'configurable' ) {
            unset($sizes);
            $size_attribute_name = $oProduct->getTypeInstance()->getConfigurableAttributes($oProduct)->getFirstItem()->getProductAttribute()->getAttributeCode();

            $aData['variations'] = [];
            foreach($oProduct->getTypeInstance()->getSalableUsedProducts($oProduct) as $simple_product) {
                if (in_array($simple_product->getAttributeText($size_attribute_name), $this->notAllowed)) {
                    continue;
                }

                $stockItem = $this->stockRegistry->getStockItem($simple_product->getId(), 1);
                if ($stockItem->getIsInStock()) {
                    $sizes[] = $simple_product->getAttributeText($size_attribute_name);
                    $aData['variations'][] = [
                        'id' => $oProduct->getId() . '.' . $simple_product->getAttributeText($size_attribute_name),
                        'link' => $aData['link'],
                        'availability' => $aData['stock_descrip'],
                        'manufacturersku' => $aData['mpn'],
                        'ean' => $simple_product->getSku(),
                        'price_with_vat' => $aData['price'],
                        'size' => $simple_product->getAttributeText($size_attribute_name),
                        'quantity' => $stockItem->getQty(),
                    ];
                }
            }

            if( isset($sizes) && count($sizes) > 0 ) {
                $aData['size'] = implode(',', $sizes);
            } else {
                $aData['size'] = '';
            }
        }
        $this->appendXML($aData);
    }

    private function appendXML(array $p)
    {
        $product = $this->xml->createElement("product");
        $this->base_node->appendChild( $product );

        $product->appendChild( $this->xml->createElement('id', $p['id']) );
        $product->appendChild( $this->xml->createElement('mpn', $p['mpn']) );
        $product->appendChild( $this->xml->createElement('manufacturer', htmlspecialchars($p['brand'])) );
        $product->appendChild( $this->xml->createElement('name', ucwords(htmlspecialchars(trim($p['title'])))) );

        $description = $product->appendChild($this->xml->createElement('description'));
        $description->appendChild($this->xml->createCDATASection( $p['description'] ));

        $product->appendChild( $this->xml->createElement('price', $p['price']) );
        $product->appendChild( $this->xml->createElement('link', $p['link']) );
        $product->appendChild( $this->xml->createElement('image', $p['image_link_large']) );

        if( array_key_exists('additional_imageurl', $p) ) {
            foreach($p['additional_imageurl'] as $image) {
                $product->appendChild( $this->xml->createElement('additional_imageurl', $image) );
            }
        }

        $product->appendChild( $this->xml->createElement('InStock', $p['stock']) );
        $product->appendChild( $this->xml->createElement('Availability', $p['stock_descrip']) );

        $category = $product->appendChild($this->xml->createElement('category'));
        $category->appendChild($this->xml->createCDATASection( $p['category'] ));

        $product->appendChild( $this->xml->createElement('categoryid', $p['categoryid']) );

        if( array_key_exists('color', $p) && $p['color'] != '' && !in_array($p['color'], $this->notAllowed) ) {
            $product->appendChild( $this->xml->createElement('color', $p['color']) );
        }

        if( array_key_exists('size', $p) && $p['size'] != '' ) {
            $product->appendChild( $this->xml->createElement('size', $p['size']) );
        }

        if (array_key_exists('variations', $p) && count($p['variations']) > 0) {
            $variations = $product->appendChild($this->xml->createElement('variations'));
            foreach ($p['variations'] as $v) {
                $variation = $variations->appendChild($this->xml->createElement('variation'));
                $variation->appendChild( $this->xml->createElement('variationid', $v['id']) );
                $variation->appendChild( $this->xml->createElement('link', $v['link']) );
                $variation->appendChild( $this->xml->createElement('availability', $v['availability']) );
                $variation->appendChild( $this->xml->createElement('manufacturersku', $v['manufacturersku']) );
                $variation->appendChild( $this->xml->createElement('ean', $v['ean']) );
                $variation->appendChild( $this->xml->createElement('price_with_vat', $v['price_with_vat']) );
                $variation->appendChild( $this->xml->createElement('size', $v['size']) );
                $variation->appendChild( $this->xml->createElement('quantity', $v['quantity']) );
            }
        }
    }

    private function getCategories($oProduct)
    {
        $aIds = $oProduct->getCategoryIds();
        $aCategories = array();
        $catPath = array();
        $aCategories['bread'] = '';

        foreach($aIds as $iCategory) {
            if (!in_array($iCategory, $this->excluded)) {
                $oCategory = $this->categoryFactory->create()->load($iCategory);
                if( !in_array($oCategory->getParentId(), $this->excluded) ) {
                    $aCategories['bread'] = '';
                    $aCategories['cid'] = $oCategory->getId();
                    $aCategories['catpath'] = $oCategory->getPath();
                    $catPath = explode('/', $aCategories['catpath']);
                    foreach($catPath as $cpath) {
                        $pCategory = $this->categoryFactory->create()->load($cpath);
                        if( !in_array($pCategory->getName(), $this->notAllowed) && $pCategory->getName() != '') {
                            if (!in_array($pCategory->getId(), $this->excluded)) {
                                $aCategories['bread'] .= $pCategory->getName() . ' > ';
                            }
                        }
                    }
                    $aCategories['bread'] = mb_substr(trim(substr($aCategories['bread'],0,-3)),0,299,'UTF-8');
                }
            }
        }
        return $aCategories;
    }
}
