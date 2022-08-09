<?php

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * GCService initialized correctly the pages used as a service within GisClient
 * Technically it is implemented as a Singleton.
 */
class GCService
{
    const SESSION_KEY_PREFIX = 'GISCLIENT/';
    
    /**
     * Session instance
     *
     * @var Session
     */
    private $session;
    
    private static $instance;
    
    private function __construct()
    {
    }
    
    /**
     * Get an instance of service. There is axactly one instance of this class
     * in the application context.
     *
     * @return GCService
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new GCService();
            self::$instance->setExceptionHandler();
        }
        return self::$instance;
    }
    
    private function setExceptionHandler()
    {
        $handler = function (Throwable $e) {
            print_debug($e->getMessage() . "\n" . $e->getTraceAsString(), null, 'service');
            header("HTTP/1.0 500 Internal Server Error");
            echo $e->getMessage();
            exit(1);
        };
        set_exception_handler($handler);
    }
        
    public function has($name)
    {
        return $this->session->has(self::SESSION_KEY_PREFIX.$name);
    }
    
    public function get($name, $default = null)
    {
        return $this->session->get(self::SESSION_KEY_PREFIX.$name, $default);
    }
    
    public function set($name, $value)
    {
        $this->session->set(self::SESSION_KEY_PREFIX.$name, $value);
    }
    
    public function remove($name)
    {
        $this->session->remove(self::SESSION_KEY_PREFIX.$name);
    }

    public function saveAndClose()
    {
        $this->session->save();
    }
    
    /**
     * Get session instance
     *
     * @return Session
     */
    public function getSession()
    {
        if (null === $this->session) {
            throw new \LogicException('Session has not been started yet.');
        }
        return $this->session;
    }
    
    public function startSession($allowTokenFromRequest = false)
    {
        // start the sessione (if not started)
        if (null === $this->session || !$this->session->isStarted()) {
            print_debug('start new session', null, 'system');

            // Get Symfony to interface with the existing session
            $sessionHandler = new PdoSessionHandler(\GCApp::getDB(), [
                'db_table' => 'gisclient_34.sessions'
            ]);
            $sessionStorage = new NativeSessionStorage([], $sessionHandler);
            if (defined('GC_SESSION_NAME')) {
                print_debug('set session name to ' . GC_SESSION_NAME, null, 'system');
                $sessionStorage->setName(GC_SESSION_NAME);
            }
            if ($allowTokenFromRequest && isset($_REQUEST['GC_SESSION_ID']) && !empty($_REQUEST['GC_SESSION_ID'])) {
                print_debug('set session id to ' . $_REQUEST['GC_SESSION_ID'], null, 'system');
                $sessionStorage->setId($_REQUEST['GC_SESSION_ID']);
            }
            $this->session = new Session($sessionStorage);
            $this->session->start();
        } else {
            print_debug('session already started', null, 'system');
        }
    }
}
