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

namespace ID\Skroutz\Cron;

use ID\Skroutz\Helper\Generator;
use ID\Skroutz\Helper\Data;

class Feed
{

    protected $logger;
    protected $storeManager;
    protected $_generator;
    protected $_helper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Generator $generator,
        Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_generator = $generator;
        $this->_helper = $helper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $feeds = 0;
        foreach ($this->storeManager->getStores() as $store) {
            if (!$this->_helper->getGeneralConfig('enabled', $store->getId())) {
                continue;
            }

            $this->logger->addInfo("Feed generating for store: ".$store->getId());
            $this->_generator->generateXML($store->getId());
            $feeds++;
        }
        $this->logger->addInfo("Generated {$feeds} feeds");
    }
}
