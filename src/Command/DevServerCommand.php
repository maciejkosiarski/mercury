<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevServerCommand extends Command
{
    private InputInterface $input;
    private OutputInterface $output;
    private bool $running = true;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:dev:server')
            ->setDescription('Command for developing tests')
            ->addArgument('port', InputArgument::REQUIRED,'Port number for communicate.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);

        $this->input = $input;
        $this->output = $output;
        // set some variables
        $host = "127.0.0.1";
        $port = (int)$this->input->getArgument('port');
        // don't timeout!
        set_time_limit(0);

        // create socket
        $socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
        // bind socket to port
        $result = socket_bind($socket, $host, $port) or die("Could not bind to socket\n");
        // start listening for connections
        $result = socket_listen($socket, 3) or die("Could not set up socket listener\n");
        dump('Server listening...');

        while ($this->running) {

            // accept incoming connections
            // spawn another socket to handle communication
            $spawn = socket_accept($socket) or die("Could not accept incoming connection\n");
            dump('socket accepted');
            // read client input
            $input = socket_read($spawn, 1024) or die("Could not read input\n");
            dump('socket read');
            // clean up input string
            $input = trim($input);
            dump("Client Message: ".$input);
            // reverse client input and send back
            $output = strrev($input) . "\n";
            socket_write($spawn, $output, strlen ($output)) or die("Could not write output\n");
        }

        socket_close($spawn);
        socket_close($socket);

        return Command::SUCCESS;
    }

    public function stopCommand()
    {
        $this->running = false;
        dump('Server stopped');
    }
}


