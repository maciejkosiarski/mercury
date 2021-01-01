<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DevClientCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:dev:client')
            ->addArgument('port', InputArgument::REQUIRED,'Port number for communicate.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = "127.0.0.1";
        $port = (int)$input->getArgument('port');

        $helper = $this->getHelper('question');

        while ($message = $helper->ask($input, $output, new Question('Say something: ', false))) {
            $socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
            // connect to server
            $result = socket_connect($socket, $host, $port) or die("Could not connect to server\n");
            // send string to server
            socket_write($socket, $message, strlen($message)) or die("Could not send data to server\n");
            // get server response
            $result = socket_read ($socket, 1024) or die("Could not read server response\n");
            // close socket
            socket_close($socket);
            if ('Bye' === $message) {
                $output->writeln('<info>Bye!</info>');
                break;
            }
        }

        return Command::SUCCESS;
    }
}

