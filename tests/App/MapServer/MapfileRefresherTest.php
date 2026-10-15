<?php

use GisClient\MapServer\Writer\MapfileRefresher;
use GisClient\MapServer\Writer\OptimizedMapfileWriter;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../config/config.php';

/**
 * Runs the real refresh path the way an API Token request does: without a PHP session.
 * Any session access (GCService::has/get/set, GCAuthor::getLang(), ...) fails with an Error.
 */
class MapfileRefresherTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private $originalState = [];

    protected function setUp(): void
    {
        $this->originalState = [
            'session' => $this->swapProperty(GCService::instance(), 'session', null),
            'lang' => $this->swapProperty(null, 'lang', null, GCAuthor::class),
            'layerAuthorizationChecker' => $this->swapProperty(null, 'layerAuthorizationChecker', null, GCApp::class),
        ];
    }

    protected function tearDown(): void
    {
        $this->swapProperty(GCService::instance(), 'session', $this->originalState['session']);
        $this->swapProperty(null, 'lang', $this->originalState['lang'], GCAuthor::class);
        $this->swapProperty(null, 'layerAuthorizationChecker', $this->originalState['layerAuthorizationChecker'], GCApp::class);
    }

    public function testRefreshesTheTemporaryMapfilesOfAProjectWithoutASession(): void
    {
        $refresher = new MapfileRefresher(new OptimizedMapfileWriter());
        @unlink(ROOT_PATH . 'map/default/default.map');

        $refresher->refresh('default', null, false);

        $this->assertFileExists(ROOT_PATH . 'map/default/default.map');
        $this->assertSame(PHP_SESSION_NONE, session_status());
        $this->assertNull($this->readProperty(GCService::instance(), 'session'));
    }

    public function testRefreshesTheTemporaryMapfileOfOneMapsetWithoutASession(): void
    {
        $refresher = new MapfileRefresher(new OptimizedMapfileWriter());
        @unlink(ROOT_PATH . 'map/default/default.map');

        $refresher->refresh('default', 'default', false);

        $this->assertFileExists(ROOT_PATH . 'map/default/default.map');
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    /**
     * The branch MapfileRefresher takes when PROJECT_MAPFILE is enabled (a constant, so it is called directly)
     */
    public function testRefreshesTheTemporaryProjectMapfileWithoutASession(): void
    {
        @unlink(ROOT_PATH . 'map/default/default.map');

        \GCAuthor::refreshProjectMapfile('default', false);

        $this->assertSame([], \GCError::get());
        $this->assertFileExists(ROOT_PATH . 'map/default/default.map');
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testRejectsAnUnknownProject(): void
    {
        $refresher = new MapfileRefresher(new OptimizedMapfileWriter());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Project 'refresher_unknown_project' does not exist.");

        $refresher->refresh('refresher_unknown_project', null, false);
    }

    /**
     * @return mixed the previous value
     */
    private function swapProperty(?object $object, string $property, $value, ?string $class = null)
    {
        $reflection = new ReflectionProperty($class ?? ($object !== null ? get_class($object) : self::class), $property);
        $reflection->setAccessible(true);
        $previous = $reflection->getValue($object);
        $reflection->setValue($object, $value);

        return $previous;
    }

    private function readProperty(object $object, string $property)
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
