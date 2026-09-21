<?php

namespace GisClient\MapServer\Connection;

/**
 * Decides whether an OGC request may be served by a read-only replica.
 *
 * The classification is derived from the request itself — there is no
 * configurable list of operations to keep in sync. Only the OGC operations
 * that are read-only by definition are routed to the replica; everything
 * else, including anything unrecognised, stays on the primary.
 *
 * That default matters: a request wrongly sent to the primary only loses the
 * offload, while a write wrongly sent to a replica fails outright.
 */
class OgcRequestClassifier
{
    /**
     * OGC operations with no side effects, per service.
     *
     * @var array<string,array<int,string>>
     */
    private const READ_ONLY_OPERATIONS = [
        'wms' => ['getmap', 'getfeatureinfo', 'getcapabilities', 'getlegendgraphic', 'describelayer'],
        'wfs' => ['getfeature', 'describefeaturetype', 'getcapabilities'],
    ];

    /**
     * HTTP methods that can never be read-only, whatever the OGC parameters say.
     *
     * @var array<int,string>
     */
    private const WRITE_METHODS = ['PUT', 'DELETE', 'PATCH'];

    /**
     * @param string|null          $service OGC SERVICE parameter (wms/wfs)
     * @param string|null          $request OGC REQUEST parameter
     * @param string|null          $method  HTTP method
     * @param array<string,mixed>  $params  request parameters ($_REQUEST)
     * @param string|null          $uri     request URI, for the GC_EDITMODE marker
     */
    public static function isReadOnly(
        ?string $service,
        ?string $request,
        ?string $method,
        array $params = [],
        ?string $uri = null
    ): bool {
        $method = strtoupper((string) $method);

        if (in_array($method, self::WRITE_METHODS, true)) {
            return false;
        }

        // Editing requests are tunnelled through POST and identified by a
        // marker in the query string rather than by an OGC operation name.
        if ($uri !== null && strpos($uri, 'GC_EDITMODE=') !== false) {
            return false;
        }

        // OpenLayers WFS is proxied to the mapserv CGI and may carry a
        // transaction; it never qualifies.
        if (isset($params['gcRequestType']) && strtoupper((string) $params['gcRequestType']) === 'OLWFS') {
            return false;
        }

        $service = strtolower(trim((string) $service));
        $request = strtolower(trim((string) $request));

        if ($service === '' || $request === '') {
            return false;
        }

        if (!isset(self::READ_ONLY_OPERATIONS[$service])) {
            return false;
        }

        return in_array($request, self::READ_ONLY_OPERATIONS[$service], true);
    }

    /**
     * Same decision for a live request, reading the OGC parameters from the
     * mapscript request object and the transport details from the superglobals.
     *
     * @param array<string,mixed> $server
     * @param array<string,mixed> $params
     */
    public static function isReadOnlyRequest(\OwsrequestObj $request, array $server, array $params): bool
    {
        return self::isReadOnly(
            $request->getValueByName('service'),
            $request->getValueByName('request'),
            $server['REQUEST_METHOD'] ?? null,
            $params,
            $server['REQUEST_URI'] ?? null
        );
    }
}
