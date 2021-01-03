<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindHostsCommand extends Command
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $hostsLogger)
    {
        $this->logger = $hostsLogger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:hosts:find')
            ->setDescription('Scan web pages and extract addresses');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
