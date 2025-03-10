<?php

namespace GisClient\Author\Security\User;

use GisClient\Author\Security\User\User;
use GisClient\Author\Security\User\UserProviderInterface;

class R3UserProvider implements UserProviderInterface
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
     * @return array
     */
    private function getRoles()
    {
        $roles = ['ROLE_USER'];
        // TODO: ROLE_ADMIN if is SUPERADMIN of the application GISCLIENT!!

        return $roles;
    }

    /**
     * Get list of projects
     *
     * @return array
     */
    private function getProjects()
    {
        $projects = [];

        $sql = '
            SELECT * FROM ' . DB_SCHEMA . '.project
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        foreach ($stmt as $project) {
            $projects[] = $project['project_name'];
        }

        return $projects;
    }

    /**
     * Get list of groups
     *
     * @return array
     */
    private function getGroups()
    {
        $groups = [];

        $sql = '
            SELECT groupname FROM ' . DB_SCHEMA . '.groups WHERE groupname IN (?)
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['trees-users']);
        foreach ($stmt as $group) {
            $groups[] = $group['groupname'];
        }

        return $groups;
    }

    private function stringToOptions($text)
    {
        $result = [];
        $a = explode("\n", $text);
        foreach ($a as $value) {
            if ($value == '' || $value[0] == ';' || $value[0] == '#') {
                continue;
            }
            if (($p = strpos($value, '=')) === null) {
                $result[trim($value)] = null;
            } else {
                $val = trim(substr($value, $p + 1));
                if (strtoupper($val) == 'FALSE') {
                    $val = false;
                } elseif (strtoupper($val) == 'TRUE') {
                    $val = true;
                }
                $result[trim(substr($value, 0, $p))] = $val;
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $domain = null;
        if (strpos($username, '@') !== false) {
            [$username, $domain] = explode('@', $username);
        }

        $sql = "
            SELECT
                us_login as username, us_password AS password,
                us_name as nome, NULL as cognome,
                us_db_role_name,
                COALESCE(LOWER(as_type), 'md5') AS credentials_checker,
                as_data AS credentials_config,
                se_value AS object_types_using_geojson
            FROM auth.users
            LEFT JOIN auth.auth_settings USING(as_id)
            LEFT JOIN auth.settings ON (
                users.us_id =  settings.us_id AND
                se_param = 'R3Gis\GreenSpaces\Legacy\Infrastructure\FeatureFlag\ObjectTypesToReadFromHexagon'
            )
            WHERE us_login=:user AND us_status=:status
        ";
        $params = [
            'user' => $username,
            'status' => 'E'
        ];
        if ($domain !== null) {
            $sql .= " AND users.do_id=:domain";
            $params['domain'] = $domain;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);

        // pretend it returns an array on success, false if there is no user
        if ($userData !== false) {
            $roles = $this->getRoles();
            $projects = $this->getProjects();
            $groups = $this->getGroups();
            $extras = [
                'credentials_checker' => $userData['credentials_checker'],
                'credentials_config' => $this->stringToOptions($userData['credentials_config']),
                'us_db_role_name' => $userData['us_db_role_name'],
                'object_types_using_geojson' => json_decode($userData['object_types_using_geojson'], true)
            ];

            $user = new User(
                $userData['username'],
                $userData['password'],
                $userData['nome'],
                $userData['cognome'],
                $roles,
                $projects,
                $groups,
                $extras
            );

            return $user;
        }

        throw new \Exception(
            sprintf('Username "%s" does not exist.', $username)
        );
    }
}
