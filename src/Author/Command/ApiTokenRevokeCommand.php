<?php

namespace GisClient\Author\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiTokenRevokeCommand extends AbstractApiTokenCommand
{
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
        $store = $this->getStore();
        $errorOutput = $this->getErrorOutput($output);

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
