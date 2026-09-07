<?php

namespace GisClient\Author\Utils;

/**
 * Livello di debug di MapServer per la richiesta corrente.
 *
 * GC_MS_DEBUG_LEVEL e' una variabile d'ambiente: alzarla significa riavviare i
 * pod e farlo per tutto il traffico. Per capire quale layer rallenta una
 * previewmap specifica serve invece poterlo alzare per quella sola richiesta,
 * senza inondare i log di tutte le altre.
 *
 * L'interruttore per-richiesta e' GC_DEBUG in query string, e viaggia lungo la
 * catena come GC_REQUEST_ID: download.php -> gcWMSMerge.php -> ows.php.
 *
 * Puo' solo ALZARE il livello, mai abbassarlo: una richiesta non deve poter
 * silenziare gli errori.
 *
 * Ed e' disabilitato per impostazione predefinita. ows.php e' raggiungibile
 * pubblicamente: senza il gate, chiunque potrebbe chiedere il livello 5 su
 * ogni richiesta e riempire i log — oltre a farci scrivere dentro la SQL
 * generata. GC_DEBUG_ALLOW_REQUEST=1 abilita la possibilita' una volta sola;
 * da li' in poi si sceglie la singola richiesta senza altri riavvii.
 *
 * @author Analisi latenza previewmap, 2026-09
 */
class DebugLevel
{
    /**
     * Parametro che alza il livello per la sola richiesta corrente
     */
    public const QUERY_PARAM = 'GC_DEBUG';

    /**
     * Livello massimo di MapServer
     */
    public const MAX = 5;

    /**
     * Livello predefinito: solo errori
     */
    public const DEFAULT_LEVEL = 1;

    /**
     * Livello effettivo per questa richiesta.
     *
     * @return integer
     */
    public static function resolve()
    {
        $level = self::fromEnv();

        if (self::isRequestOverrideAllowed()) {
            $requested = self::fromRequest();
            if ($requested !== null && $requested > $level) {
                $level = $requested;
            }
        }

        return $level;
    }

    /**
     * Livello impostato dall'ambiente, che fa da pavimento.
     *
     * @return integer
     */
    public static function fromEnv()
    {
        $value = getenv('GC_MS_DEBUG_LEVEL');
        if ($value === false || trim((string) $value) === '') {
            return self::DEFAULT_LEVEL;
        }

        return self::clamp((int) $value);
    }

    /**
     * true se una richiesta puo' alzare il proprio livello.
     *
     * @return boolean
     */
    public static function isRequestOverrideAllowed()
    {
        $value = getenv('GC_DEBUG_ALLOW_REQUEST');

        return $value !== false && in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Frammento di query string per propagare la richiesta agli hop
     * successivi, gia' codificato e senza separatore iniziale. Stringa vuota
     * se non c'e' niente da propagare.
     *
     * @return string
     */
    public static function asQueryFragment()
    {
        if (!self::isRequestOverrideAllowed()) {
            return '';
        }

        $requested = self::fromRequest();
        if ($requested === null) {
            return '';
        }

        return self::QUERY_PARAM . '=' . $requested;
    }

    /**
     * Livello chiesto dalla richiesta, o null se assente o non valido.
     *
     * @return integer|null
     */
    private static function fromRequest()
    {
        if (!isset($_REQUEST[self::QUERY_PARAM])) {
            return null;
        }

        $value = $_REQUEST[self::QUERY_PARAM];
        if (!is_scalar($value) || !preg_match('/^\d+$/', (string) $value)) {
            return null;
        }

        return self::clamp((int) $value);
    }

    /**
     * @param integer $level
     * @return integer
     */
    private static function clamp($level)
    {
        if ($level < 0) {
            return 0;
        }

        return $level > self::MAX ? self::MAX : $level;
    }
}
