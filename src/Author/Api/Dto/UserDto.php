<?php

declare(strict_types=1);

namespace GisClient\Author\Api\Dto;

class UserDto extends JsonApiDto
{
    public ?string $id = null;

    public ?string $nome = null;

    public ?string $cognome = null;

    public ?string $email = null;

    public ?int $attivato = null;

    public ?string $dataScadenza = null;

    // Read-only: set by the DB on insert, never accepted from the API
    public ?string $dataCreazione = null;

    // Read-only: updated by the DB on modification, never accepted from the API
    public ?string $dataModifica = null;

    // Read-only: updated by the auth layer on login, never accepted from the API
    public ?string $ultimoAccesso = null;

    public ?string $userdata = null;

    // Write-only: accepted as plaintext, stored as md5(enc_pwd). Never returned in responses.
    public ?string $password = null;

    /**
     * To-many relationship: group memberships via user_group junction table.
     * Serialized as relationships.groups.data in JSON:API responses.
     *
     * @var array<int,string>|null
     */
    public ?array $groups = null;
}
