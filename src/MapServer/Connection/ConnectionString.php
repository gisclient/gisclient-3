<?php

namespace GisClient\MapServer\Connection;

/**
 * Minimal libpq connection-string reader/writer.
 *
 * Mapfile CONNECTION values are passed verbatim to PQconnectdb, so they follow
 * libpq's keyword/value syntax: pairs separated by whitespace, values
 * optionally single-quoted with backslash escapes. Rewriting them with a plain
 * str_replace corrupts quoted values (a password containing a space, for
 * instance), hence this small parser.
 */
class ConnectionString
{
    /**
     * Replace the endpoint of a connection string, preserving every other
     * parameter — user, password, port, options — exactly as configured.
     *
     * Returns the input unchanged when it cannot be parsed, so a malformed
     * string keeps pointing wherever it pointed before.
     */
    public static function withEndpoint(string $connection, ?string $host, ?string $database): string
    {
        $pairs = self::parse($connection);
        if ($pairs === null) {
            return $connection;
        }

        $replacements = [];
        if ($host !== null && $host !== '') {
            $replacements['host'] = $host;
        }
        if ($database !== null && $database !== '') {
            $replacements['dbname'] = $database;
        }
        if ($replacements === []) {
            return $connection;
        }

        $out = [];
        $seen = [];
        foreach ($pairs as [$key, $value]) {
            // hostaddr takes precedence over host in libpq: leaving a stale one
            // behind would silently keep the connection on the old endpoint.
            if (isset($replacements['host']) && $key === 'hostaddr') {
                continue;
            }
            if (isset($replacements[$key])) {
                $value = $replacements[$key];
                $seen[$key] = true;
            }
            $out[] = $key . '=' . self::quote($value);
        }

        foreach ($replacements as $key => $value) {
            if (!isset($seen[$key])) {
                $out[] = $key . '=' . self::quote($value);
            }
        }

        return implode(' ', $out);
    }

    /**
     * Merge extra libpq parameters into a connection string, overriding any
     * key already present. Used to add endpoint-selection options such as
     * target_session_attrs and connect_timeout without touching credentials.
     *
     * Returns the input unchanged when either side cannot be parsed.
     */
    public static function withParams(string $connection, ?string $params): string
    {
        if ($params === null || trim($params) === '') {
            return $connection;
        }

        $extra = self::parse($params);
        $pairs = self::parse($connection);
        if ($extra === null || $pairs === null) {
            return $connection;
        }

        $overrides = [];
        foreach ($extra as [$key, $value]) {
            $overrides[$key] = $value;
        }

        $out = [];
        $seen = [];
        foreach ($pairs as [$key, $value]) {
            if (isset($overrides[$key])) {
                $value = $overrides[$key];
                $seen[$key] = true;
            }
            $out[] = $key . '=' . self::quote($value);
        }
        foreach ($overrides as $key => $value) {
            if (!isset($seen[$key])) {
                $out[] = $key . '=' . self::quote($value);
            }
        }

        return implode(' ', $out);
    }

    /**
     * Strip the password so a connection string can be logged.
     */
    public static function redact(string $connection): string
    {
        $pairs = self::parse($connection);
        if ($pairs === null) {
            return '(unparsable connection string)';
        }

        $out = [];
        foreach ($pairs as [$key, $value]) {
            $out[] = $key . '=' . ($key === 'password' ? '***' : self::quote($value));
        }

        return implode(' ', $out);
    }

    /**
     * @return array<int,array{0:string,1:string}>|null ordered key/value pairs, null when malformed
     */
    private static function parse(string $connection): ?array
    {
        $pairs = [];
        $i = 0;
        $length = strlen($connection);

        while ($i < $length) {
            while ($i < $length && ctype_space($connection[$i])) {
                $i++;
            }
            if ($i >= $length) {
                break;
            }

            $keyStart = $i;
            while ($i < $length && $connection[$i] !== '=' && !ctype_space($connection[$i])) {
                $i++;
            }
            $key = substr($connection, $keyStart, $i - $keyStart);
            if ($key === '') {
                return null;
            }

            while ($i < $length && ctype_space($connection[$i])) {
                $i++;
            }
            if ($i >= $length || $connection[$i] !== '=') {
                return null;
            }
            $i++;
            while ($i < $length && ctype_space($connection[$i])) {
                $i++;
            }

            $value = '';
            if ($i < $length && $connection[$i] === "'") {
                $i++;
                while ($i < $length && $connection[$i] !== "'") {
                    if ($connection[$i] === '\\' && $i + 1 < $length) {
                        $i++;
                    }
                    $value .= $connection[$i];
                    $i++;
                }
                if ($i >= $length) {
                    return null; // unterminated quote
                }
                $i++;
            } else {
                while ($i < $length && !ctype_space($connection[$i])) {
                    if ($connection[$i] === '\\' && $i + 1 < $length) {
                        $i++;
                    }
                    $value .= $connection[$i];
                    $i++;
                }
            }

            $pairs[] = [$key, $value];
        }

        return $pairs === [] ? null : $pairs;
    }

    private static function quote(string $value): string
    {
        if ($value === '' || preg_match('/[\s\'\\\\]/', $value)) {
            return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
        }

        return $value;
    }
}
