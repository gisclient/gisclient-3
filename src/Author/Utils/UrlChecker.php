<?php

namespace GisClient\Author\Utils;

class UrlChecker
{

    /*private*/ const ALLOWED_SCHEMES = [
        "http",
        "https",
    ];

    /**
     * check url for Allowed schemes, and optionally limit to same host
     *
     * @param string $url
     * @param bool $limitToSameDomain
     * @return bool
     */
    public static function checkUrl($url, $limitToSameDomain = false)
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, self::ALLOWED_SCHEMES)) {
            error_log("Tried calling url '$url' - not allowed because of scheme.");
            throw new \RuntimeException(
                "Url to be called is not an allowed scheme. Allowed schemes are: " .
                implode(", ", self::ALLOWED_SCHEMES)
            );
        }

        if (!$limitToSameDomain) {
            return true;
        }

        $authorHost = mb_strtolower(parse_url(PUBLIC_URL, PHP_URL_HOST), "UTF-8");
        $urlHost = mb_strtolower(parse_url($url, PHP_URL_HOST), "UTF-8");

        if ($authorHost !== $urlHost) {
            error_log("Tried calling url '$url' - not allowed because of different host.");
            throw new \RuntimeException(
                "Url to be called is not an allowed host. Allowed host: " . $authorHost
            );
        }

        return true;
    }
}
