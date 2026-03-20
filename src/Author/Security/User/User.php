<?php

namespace GisClient\Author\Security\User;

class User implements UserInterface
{
    /**
     * Username
     *
     * @var string
     */
    private $username;
    
    /**
     * Password
     *
     * @var string
     */
    private $password;
    
    /**
     * Nome
     *
     * @var string
     */
    private $nome;
    
    /**
     * Cognome
     *
     * @var string
     */
    private $cognome;
    
    /**
     * List of user roles
     *
     * @var array
     */
    private $roles;
    
    /**
     * List of user projects
     *
     * @var array
     */
    private $projects;
    
    /**
     * List of user groups
     *
     * @var array
     */
    private $groups;
    
    /**
     * Extra attributes
     *
     * @var array
     */
    private $extras;
    
    /**
     * Constructor
     *
     * @param string $username
     * @param string $password
     * @param string $nome
     * @param string $cognome
     */
    public function __construct(
        $username = null,
        $password = null,
        $nome = null,
        $cognome = null,
        array $roles = [],
        array $projects = [],
        array $groups = [],
        array $extras = []
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->nome = $nome;
        $this->cognome = $cognome;
        $this->roles = $roles;
        $this->projects = $projects;
        $this->groups = $groups;
        $this->extras = $extras;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function getPassword()
    {
        return $this->password;
    }
    
    public function getNome()
    {
        return $this->nome;
    }
    
    public function getCognome()
    {
        return $this->cognome;
    }
    
    public function getRoles()
    {
        return $this->roles;
    }
    
    public function getProjects()
    {
        return $this->projects;
    }
    
    public function getGroups()
    {
        return $this->groups;
    }
    
    /**
     * Get extra attributes
     *
     * @return array
     */
    public function getExtras()
    {
        return $this->extras;
    }
    
    public function isAdmin($project = null)
    {
        if (null !== $project) {
            return in_array($project, $this->getProjects());
        } else {
            return in_array('ROLE_ADMIN', $this->getRoles());
        }
    }
    
    public function __toString()
    {
        return $this->getUsername();
    }
}
