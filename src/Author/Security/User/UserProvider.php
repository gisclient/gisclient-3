<?php

namespace GisClient\Author\Security\User;

class UserProvider implements UserProviderInterface
{
    /**
     * Database
     *
     * @var \PDO
     */
    private $db;
    
    /**
     * Constructor
     *
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * Get list of roles
     *
     * @param string $userName
     * @return array
     */
    private function getRoles($userName)
    {
        $roles = array('ROLE_USER');
        if ($userName === SUPER_USER) {
            $roles[] = 'ROLE_ADMIN';
        }
        return $roles;
    }
    
    /**
     * Get list of projects
     *
     * @param string $userName
     * @return array
     */
    private function getProjects($userName)
    {
        $projects = array();
        
        $sql = '
            SELECT * FROM '.DB_SCHEMA.'.project_admin 
            WHERE username = :username
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            'username'=>$userName,
        ));
        foreach ($stmt as $project) {
            $projects[] = $project['project_name'];
        }
        
        return $projects;
    }
    
    /**
     * Get list of projects
     *
     * @param string $userName
     * @return array
     */
    private function getGroups($userName)
    {
        $groups = array();
        
        $sql = '
            SELECT groupname FROM '.DB_SCHEMA.'.user_group 
            WHERE username = :username
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            'username'=>$userName,
        ));
        foreach ($stmt as $group) {
            $groups[] = $group['groupname'];
        }
        
        return $groups;
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $sql = '
            SELECT
                username, enc_pwd AS password, nome, cognome
            FROM '.DB_SCHEMA.'.users
            WHERE username=:user
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            'user'=>$username
        ));
        // make a call to your webservice here
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // pretend it returns an array on success, false if there is no user
        if ($userData !== false) {
            $roles = $this->getRoles($userData['username']);
            $projects = $this->getProjects($userData['username']);
            $groups = $this->getGroups($userData['username']);
            
            return new User($userData['username'], $userData['password'], $userData['nome'], $userData['cognome'], $roles, $projects, $groups);
        }

        throw new \Exception(
            sprintf('Username "%s" does not exist.', $username)
        );
    }
}
