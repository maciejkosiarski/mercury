<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HttpVideoStreamServerV2Command extends Command
{
    use LockableTrait;

    private string $videos;
    private LoggerInterface $logger;

    public function __construct(string $videos, LoggerInterface $videoStreamLogger)
    {
        $this->videos = $videos;
        $this->logger = $videoStreamLogger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:video-stream:v2')
            ->setDescription('Video stream server in AMP library')
            ->addArgument('port', InputArgument::REQUIRED,'Port number for communicate.')
            ->addArgument('uri', InputArgument::REQUIRED);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
