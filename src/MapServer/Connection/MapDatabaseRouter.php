<?php

namespace GisClient\MapServer\Connection;

/**
 * Points MapServer's PostGIS layers at the read-only endpoint for OGC read
 * requests.
 *
 * The mapfile on disk always carries the primary. Routing happens at request
 * time instead, for two reasons: the decision depends on the request, and some
 * paths read the mapfile without going through PHP at all (the OLWFS branch of
 * ows.php proxies to the mapserv CGI). Keeping the primary as the on-disk
 * default means those paths stay correct with no special handling.
 *
 * Everything is inert unless MAP_DB_HOST or MAP_DB_NAME is set.
 */
class MapDatabaseRouter
{
    /**
     * True when this deployment declares a read-only endpoint for MapServer.
     */
    public static function isConfigured(): bool
    {
        return (defined('MAP_DB_ROUTING') && MAP_DB_ROUTING === true);
    }

    public static function readHost(): ?string
    {
        return defined('MAP_DB_HOST') && MAP_DB_HOST !== '' ? MAP_DB_HOST : null;
    }

    public static function readDatabase(): ?string
    {
        return defined('MAP_DB_NAME') && MAP_DB_NAME !== '' ? MAP_DB_NAME : null;
    }

    /**
     * Rewrite the endpoint of a single PostGIS connection string.
     */
    public static function route(string $connection): string
    {
        return ConnectionString::withEndpoint($connection, self::readHost(), self::readDatabase());
    }

    /**
     * Repoint every PostGIS layer of the map at the read-only endpoint.
     *
     * Applies to all layers, not only those carrying a SETROLE directive:
     * a layer without RLS would otherwise keep the static primary connection
     * baked into the mapfile.
     *
     * @return int number of layers repointed
     */
    public static function applyTo(\mapObj $map): int
    {
        if (!self::isConfigured()) {
            return 0;
        }

        $routed = 0;
        for ($i = 0; $i < $map->numlayers; $i++) {
            $layer = $map->getLayer($i);
            if ((int) $layer->connectiontype !== MS_POSTGIS) {
                continue;
            }

            $connection = (string) $layer->connection;
            if ($connection === '') {
                continue;
            }

            $rerouted = self::route($connection);
            if ($rerouted !== $connection) {
                $layer->set('connection', $rerouted);
                $routed++;
            }
        }

        if ($routed > 0) {
            print_debug(sprintf(
                'read-only routing: %d layer(s) -> host=%s dbname=%s',
                $routed,
                self::readHost() ?? '(unchanged)',
                self::readDatabase() ?? '(unchanged)'
            ), null, 'ows');
        }

        return $routed;
    }
}
