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
        return self::readHost() !== null;
    }

    public static function readHost(): ?string
    {
        return defined('MAP_DB_HOST') && MAP_DB_HOST !== '' ? MAP_DB_HOST : null;
    }

    /**
     * Rewrite the endpoint of a single PostGIS connection string.
     */
    /**
     * Extra libpq parameters applied to routed connections, e.g.
     *   target_session_attrs=prefer-standby connect_timeout=2
     * Combined with a comma-separated MAP_DB_HOST this lets libpq pick the
     * standby itself and fall back to the primary when none answers.
     */
    public static function connectionParams(): ?string
    {
        $params = [];

        // Il tetto sulla query viene prima, cosi' un deployment che valorizza
        // MAP_DB_CONN_PARAMS puo' sovrascriverlo di proposito invece di
        // perderlo per sbaglio.
        $timeout = self::statementTimeout();
        if ($timeout !== null) {
            $params[] = sprintf("options='-c statement_timeout=%d'", $timeout);
        }

        if (defined('MAP_DB_CONN_PARAMS') && MAP_DB_CONN_PARAMS !== '' && MAP_DB_CONN_PARAMS !== null) {
            $params[] = MAP_DB_CONN_PARAMS;
        }

        return $params === [] ? null : implode(' ', $params);
    }

    /**
     * Tetto per la singola query, in millisecondi, o null se disattivato.
     *
     * Nota: libpq tratta "options" come una stringa unica. Un deployment che
     * la valorizza per altro deve reinserirci anche statement_timeout, perche'
     * la chiave viene sostituita e non fusa.
     */
    public static function statementTimeout(): ?int
    {
        if (!defined('MAP_DB_STATEMENT_TIMEOUT')) {
            return null;
        }

        $timeout = (int) MAP_DB_STATEMENT_TIMEOUT;

        return $timeout > 0 ? $timeout : null;
    }

    /**
     * Rewrite the endpoint of a single PostGIS connection string.
     *
     * Only connections that currently point at the primary are moved: a
     * catalog naming another server does so deliberately, and the replica of
     * this cluster would not hold its database.
     */
    public static function route(string $connection): string
    {
        if (!self::isConfigured() || !self::pointsAtPrimary($connection)) {
            return $connection;
        }

        $routed = ConnectionString::withEndpoint($connection, self::readHost(), null);

        return ConnectionString::withParams($routed, self::connectionParams());
    }

    private static function pointsAtPrimary(string $connection): bool
    {
        $host = ConnectionString::valueOf($connection, 'host');

        // No host at all means the libpq default, i.e. the local primary.
        return $host === null || $host === '' || $host === DB_HOST;
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
                'read-only routing: %d layer(s) -> host=%s',
                $routed,
                self::readHost() ?? '(unchanged)'
            ), null, 'ows');
        }

        return $routed;
    }
}
