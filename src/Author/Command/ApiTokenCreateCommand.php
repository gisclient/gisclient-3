<?php

namespace GisClient\Author\Command;

use GisClient\Author\Security\ApiToken\ApiTokenGenerator;
use GisClient\Author\Security\ApiToken\ApiTokenRegistrationException;
use GisClient\Author\Security\ApiToken\ApiTokenStoreInterface;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiTokenCreateCommand extends Command
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
        $this->setName('gisclient:api-token:create')
            ->setDescription('Generates an API Token for a user and prints it once')
            ->setHelp(
                "Generates an API Token, stores only its hash and prints the token on standard output.\n"
                . "The token can't be shown again. Select the Tenant with the HTTP_HOST environment variable."
            )
            ->addArgument('username', InputArgument::REQUIRED, 'The user the token authenticates as')
            ->addArgument('name', InputArgument::REQUIRED, 'A name for the token, unique per user (e.g. the client application)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        $name = (string) $input->getArgument('name');
        $store = $this->store ?? new PdoApiTokenStore(\GCApp::getDB());
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $generated = (new ApiTokenGenerator())->generate();
        try {
            $store->register($generated->getId(), $username, $name, $generated->getHash());
        } catch (ApiTokenRegistrationException $e) {
            $errorOutput->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $errorOutput->writeln(
            "<info>Created token '{$name}' (id {$generated->getId()}) for user '{$username}'. "
            . "Store it now: it can't be shown again.</info>"
        );
        $output->writeln($generated->getToken());

        return 0;
    }
}
