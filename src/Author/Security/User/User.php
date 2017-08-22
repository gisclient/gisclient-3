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
     * Constructor
     * 
     * @param string $username
     * @param string $password
     * @param string $nome
     * @param string $cognome
     * @param array $roles
     * @param array $projects
     * @param array $groups 
     */
    public function __construct($username = null, $password = null, $nome = null, $cognome = null, array $roles = array(), array $projects = array(), array $groups = array())
    {
        $this->username = $username;
        $this->password = $password;
        $this->nome = $nome;
        $this->cognome = $cognome;
        $this->roles = $roles;
        $this->projects = $projects;
        $this->groups = $groups;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getNome()
    {
        return $this->nome;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCognome()
    {
        return $this->cognome;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getProjects()
    {
        return $this->projects;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        return $this->groups;
    }
    
    /**
     * {@inheritdoc}
     */
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
