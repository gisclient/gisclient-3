<?php

namespace GisClient\Author\Command;

use GisClient\Author\Security\ApiToken\ApiTokenStoreInterface;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base for the API Token commands, which run against the Tenant selected via HTTP_HOST
 */
abstract class AbstractApiTokenCommand extends Command
{
    /**
     * @var ApiTokenStoreInterface|null
     */
    private $store;

    /**
     * @param ApiTokenStoreInterface|null $store defaults to the store of the Tenant selected via HTTP_HOST
     */
    public function __construct(?ApiTokenStoreInterface $store = null)
    {
        $this->store = $store;
        parent::__construct();
    }

    protected function getStore(): ApiTokenStoreInterface
    {
        if ($this->store === null) {
            $this->store = new PdoApiTokenStore(\GCApp::getDB());
        }

        return $this->store;
    }

    protected function getErrorOutput(OutputInterface $output): OutputInterface
    {
        return $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }
}
