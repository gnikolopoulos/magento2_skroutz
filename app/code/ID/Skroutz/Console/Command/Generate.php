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

namespace ID\Skroutz\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use ID\Skroutz\Helper\Generator;

class Generate extends Command
{

    private const STORE = "store";

    protected $_helper;
    private $_state;

    public function __construct(
        Generator $helper,
        State $state
    )
    {
        $this->_helper = $helper;
        $this->_state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try{
            $this->_state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } finally {
            $output->writeln( $this->_helper->generateXML($input->getOption(self::STORE) ?: 0) );
        }

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName("skroutz_feed:generate");
        $this->setDescription("XML Generation");
        $this->addOption(
            self::STORE,
            null,
            InputOption::VALUE_REQUIRED,
            'Store',
            0
        );
        parent::configure();
    }
}
