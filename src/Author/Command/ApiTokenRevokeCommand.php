<?php

namespace GisClient\Author\Command;

use GisClient\Author\Security\ApiToken\ApiTokenStoreInterface;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiTokenRevokeCommand extends Command
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

    protected function configure(): void
    {
        $this->setName('gisclient:api-token:revoke')
            ->setDescription('Revokes an API Token')
            ->setHelp(
                "Deletes the API Token with the given id: its next request is rejected with 401.\n"
                . 'Select the Tenant with the HTTP_HOST environment variable.'
            )
            ->addArgument('id', InputArgument::REQUIRED, 'The token id, as shown by gisclient:api-token:list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $store = $this->store ?? new PdoApiTokenStore(\GCApp::getDB());
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $token = $store->find($id);
        if ($token === null || !$store->delete($id)) {
            $errorOutput->writeln("<error>No token with id '{$id}' exists.</error>");

            return 1;
        }

        $output->writeln(
            "<info>Revoked token '{$id}' ('{$token->getName()}' of user '{$token->getUsername()}').</info>"
        );

        return 0;
    }
}
