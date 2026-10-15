<?php

namespace GisClient\Author\Command;

use GisClient\Author\Security\ApiToken\ApiTokenStoreInterface;
use GisClient\Author\Security\ApiToken\PdoApiTokenStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiTokenListCommand extends Command
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
        $this->setName('gisclient:api-token:list')
            ->setDescription('Lists the API Tokens of the Tenant')
            ->setHelp(
                "Lists id, name, user and creation date of the API Tokens. Hashes and secrets are never shown.\n"
                . 'Select the Tenant with the HTTP_HOST environment variable.'
            )
            ->addArgument('username', InputArgument::OPTIONAL, 'Only list the tokens of this user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $username = $username === null ? null : (string) $username;
        $store = $this->store ?? new PdoApiTokenStore(\GCApp::getDB());

        $tokens = $store->list($username);
        if ($tokens === []) {
            $output->writeln($username === null ? 'No tokens found.' : "No tokens found for user '{$username}'.");

            return 0;
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Username', 'Created at']);
        foreach ($tokens as $token) {
            $table->addRow([$token->getId(), $token->getName(), $token->getUsername(), $token->getCreatedAt()]);
        }
        $table->render();

        return 0;
    }
}
