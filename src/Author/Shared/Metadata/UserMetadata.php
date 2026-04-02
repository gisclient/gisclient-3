<?php

declare(strict_types=1);

namespace GisClient\Author\Shared\Metadata;

use GisClient\Author\Api\Dto\GroupDto;
use GisClient\Author\Api\Dto\UserDto;

class UserMetadata extends Metadata
{
    public function __construct()
    {
        parent::__construct('user', UserDto::class, 'username', 'string', 'users');

        $this
            ->requiredOnCreate(['username', 'password'])
            ->requiredOnPut(['username', 'password'])
            ->filterable(['username', 'email', 'attivato'])
            ->sortable(['username', 'cognome', 'nome'], 'username')
            ->addAttribute('nome', 'nome', 'string', true)
            ->addAttribute('cognome', 'cognome', 'string', true)
            ->addAttribute('email', 'email', 'string', true)
            ->addAttribute('attivato', 'attivato', 'int', true)
            ->addAttribute('data_scadenza', 'dataScadenza', 'string', true)
            // read-only: set by application on creation, not writable via API
            ->addAttribute('data_creazione', 'dataCreazione', 'string', true, true, false)
            // read-only: updated internally, not writable via API
            ->addAttribute('data_modifica', 'dataModifica', 'string', true, true, false)
            // read-only: updated by the auth layer on login, not writable via API
            ->addAttribute('ultimo_accesso', 'ultimoAccesso', 'string', true, true, false)
            ->addAttribute('userdata', 'userdata', 'string', true)
            // write-only: accepted as plaintext, stored as md5 hash; NEVER returned in responses
            ->addAttribute('password', 'password', 'string', false, false, true, 'enc_pwd', [], 'md5')
            // to-many relationship: group memberships via user_group junction table
            ->addRelationship('groups', 'groups', GroupDto::class, 'group', false, true, true, null, true, 'user_group', 'username', 'groupname');
    }
}
