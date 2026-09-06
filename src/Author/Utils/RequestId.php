<?php

namespace GisClient\Author\Utils;

use Exception;

/**
 * Id di correlazione della richiesta corrente.
 *
 * Una singola immagine di mappa mette in fila piu' richieste HTTP dentro
 * questa stessa applicazione: download.php chiama gcWMSMerge.php, che a sua
 * volta fa disegnare a MapServer una ows.php per ogni gruppo WMS. Nei log
 * quelle righe sono indistinguibili da quelle di ogni altro utente, e
 * l'unico modo di metterle in relazione e' l'orario — che smette di
 * funzionare esattamente quando serve, cioe' quando c'e' concorrenza.
 *
 * L'id risolve questo. Viaggia in due modi diversi a seconda dell'hop:
 *
 *   header X-Request-Id   fra le richieste che partono da codice nostro
 *                         (download.php -> gcWMSMerge.php)
 *   query string          verso ows.php, perche' quell'URL lo costruisce
 *                         MapServer e non possiamo aggiungerci header
 *                         (vedi GC_REQUEST_ID in gcWMSMerge.php)
 *
 * Se la richiesta in ingresso porta gia' un X-Request-Id valido viene
 * riusato: cosi' un proxy a monte, o in futuro il backend che chiama
 * download.php, possono imporre il proprio id e la correlazione si estende
 * oltre i confini di GisClient senza altre modifiche qui.
 *
 * @author Analisi latenza previewmap, 2026-09
 */
class RequestId
{
    /**
     * Header usato per trasportare l'id fra le applicazioni
     */
    public const HEADER = 'X-Request-Id';

    /**
     * Parametro usato dove non si possono aggiungere header (MapServer)
     */
    public const QUERY_PARAM = 'GC_REQUEST_ID';

    /**
     * @var string|null
     */
    private static $id = null;

    /**
     * Id della richiesta corrente, creato al primo accesso.
     *
     * @return string
     */
    public static function get()
    {
        if (self::$id === null) {
            $incoming = '';
            if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
                $incoming = (string) $_SERVER['HTTP_X_REQUEST_ID'];
            } elseif (isset($_REQUEST[self::QUERY_PARAM])) {
                // ricevuto in query string, es. ows.php chiamata da MapServer
                $incoming = (string) $_REQUEST[self::QUERY_PARAM];
            }

            self::$id = self::isValid($incoming) ? $incoming : self::generate();
        }

        return self::$id;
    }

    /**
     * Header pronto per CURLOPT_HTTPHEADER.
     *
     * @return string
     */
    public static function asCurlHeader()
    {
        return self::HEADER . ': ' . self::get();
    }

    /**
     * Frammento di query string, gia' codificato, senza separatore iniziale.
     *
     * @return string
     */
    public static function asQueryFragment()
    {
        return self::QUERY_PARAM . '=' . rawurlencode(self::get());
    }

    /**
     * Forza un id specifico, per i processi che non nascono da una richiesta
     * HTTP e per i test.
     *
     * @param string $id
     */
    public static function set($id)
    {
        self::$id = self::isValid($id) ? $id : self::generate();
    }

    /**
     * Un id che arriva da fuori finisce in un URL e in righe di log: senza
     * questo controllo si potrebbero iniettare separatori di query string o
     * falsificare righe di log.
     *
     * @param string $id
     * @return boolean
     */
    private static function isValid($id)
    {
        return $id !== ''
            && strlen($id) <= 64
            && preg_match('/^[A-Za-z0-9._-]+$/', $id) === 1;
    }

    /**
     * @return string
     */
    private static function generate()
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (Exception $e) {
            // non deve mai impedire di servire la richiesta
            return substr(md5(uniqid('', true)), 0, 16);
        }
    }
}
