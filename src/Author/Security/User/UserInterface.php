<?php

namespace GisClient\Author\Security\User;

interface UserInterface
{
    /**
     * Return the username
     *
     * @return string
     */
    public function getUsername();
    
    /**
     * Return the password
     *
     * @return string
     */
    public function getPassword();
    
    /**
     * Return the "nome"
     *
     * @return string
     */
    public function getNome();
    
    /**
     * Return the "cognome"
     *
     * @return string
     */
    public function getCognome();
    
    /**
     * Return list of roles
     *
     * @return array
     */
    public function getRoles();
    
    /**
     * Return list of projects
     *
     * @return array
     */
    public function getProjects();
    
    /**
     * Return list of groups
     *
     * @return array
     */
    public function getGroups();
    
    /**
     * Check if user is a administrator or optional the administrator of the project
     *
     * @param string $project
     * @return boolean
     */
    public function isAdmin($project = null);
}
