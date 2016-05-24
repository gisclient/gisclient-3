<?php

class GCLog
{
    private $db;
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    public function log($user, $action, $info = '')
    {
        $sql = "INSERT INTO ".DB_SCHEMA.".logs (log_user, log_action, log_info) VALUES (:user,:action,:info)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(
            array(
                ':user' => $user,
                ':action' => $action,
                ':info' => $info
            )
        );
    }
}
