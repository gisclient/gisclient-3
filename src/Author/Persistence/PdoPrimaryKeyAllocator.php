<?php

namespace GisClient\Author\Persistence;

/**
 * Allocates integer primary keys through the DB function new_pkey().
 *
 * Without a start value new_pkey() searches the lowest free id with an anti-join over
 * the whole table, so calling it for every row of a bulk insert (project copy/import)
 * grows with rows x table size. Only the first allocation per table keeps that
 * gap-filling behaviour; following ones continue above the highest id seen, where
 * new_pkey(start) is an index lookup that still skips ids taken by other writers.
 */
class PdoPrimaryKeyAllocator implements PrimaryKeyAllocator
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * Next id to try, keyed by "schema.table.primaryKey".
     *
     * @var array<string,int>
     */
    private $nextIds = [];

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function allocate(string $schema, string $table, string $primaryKey): int
    {
        $key = $schema . '.' . $table . '.' . $primaryKey;

        if (!isset($this->nextIds[$key])) {
            $id = $this->newPkey($schema, $table, $primaryKey, null);
            $this->nextIds[$key] = max($id, $this->maxId($schema, $table, $primaryKey)) + 1;

            return $id;
        }

        $id = $this->newPkey($schema, $table, $primaryKey, $this->nextIds[$key]);
        $this->nextIds[$key] = $id + 1;

        return $id;
    }

    private function newPkey(string $schema, string $table, string $primaryKey, ?int $start): int
    {
        $params = [
            ':scm' => $schema,
            ':tbl' => $table,
            ':pkey' => $primaryKey,
        ];
        $sql = 'SELECT ' . DB_SCHEMA . '.new_pkey(:scm, :tbl, :pkey)';
        if ($start !== null) {
            $params[':start'] = $start;
            $sql = 'SELECT ' . DB_SCHEMA . '.new_pkey(:scm, :tbl, :pkey, :start)';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function maxId(string $schema, string $table, string $primaryKey): int
    {
        $stmt = $this->db->query(sprintf('SELECT COALESCE(MAX(%s), 0) FROM %s.%s', $primaryKey, $schema, $table));

        return (int) $stmt->fetchColumn();
    }
}
