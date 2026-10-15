<?php

namespace GisClient\Author\Command;

use GisClient\Author\Security\ApiToken\ApiTokenRegistrationException;
use GisClient\Author\Security\ApiToken\ApiTokenStoreInterface;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiTokenRegisterCommand extends Command
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
        $this->setName('gisclient:api-token:register')
            ->setDescription('Registers an API Token generated outside Author')
            ->setHelp(
                "Registers the id and the SHA-256 hash of an API Token generated outside Author (e.g. stored in Vault).\n"
                . "Author never receives the plaintext token. Select the Tenant with the HTTP_HOST environment variable."
            )
            ->addArgument('username', InputArgument::REQUIRED, 'The user the token authenticates as')
            ->addArgument('name', InputArgument::REQUIRED, 'A name for the token, unique per user (e.g. the client application)')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'The token id: 16 lowercase hex characters')
            ->addOption('hash', null, InputOption::VALUE_REQUIRED, 'The SHA-256 (hex) of the full token string');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = (string) $input->getArgument('username');
        $name = (string) $input->getArgument('name');
        $id = (string) $input->getOption('id');
        $hash = (string) $input->getOption('hash');
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        foreach ([
            'id' => $id,
            'hash' => $hash,
        ] as $option => $value) {
            if ($value === '') {
                $errorOutput->writeln("<error>The --{$option} option is required.</error>");

                return 1;
            }
        }

        $store = $this->store ?? new PdoApiTokenStore(\GCApp::getDB());
        try {
            $store->register($id, $username, $name, $hash);
        } catch (ApiTokenRegistrationException $e) {
            $errorOutput->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $output->writeln("<info>Registered token '{$name}' (id {$id}) for user '{$username}'.</info>");

        return 0;
    }
}
