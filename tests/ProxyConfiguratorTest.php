<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Test\Configurator;

use Composer\Composer;
use Composer\IO\NullIO;
use Narrowspark\Automatic\Common\Package;
use Narrowspark\Automatic\Common\Traits\PhpFileMarkerTrait;
use Narrowspark\Automatic\Configurator\ProxyConfigurator;
use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;

/**
 * @internal
 */
final class ProxyConfiguratorTest extends MockeryTestCase
{
    use PhpFileMarkerTrait;

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\NullIo
     */
    private $nullIo;

    /**
     * @var \Narrowspark\Automatic\Configurator\ProxyConfigurator
     */
    private $configurator;

    /**
     * @var string
     */
    private $globalPath;

    /**
     * @var string
     */
    private $localPath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->composer = new Composer();
        $this->nullIo   = new NullIO();

        $dir = __DIR__ . '/ProxyConfiguratorTest';

        $this->globalPath = $dir . '/staticalproxy.php';
        $this->localPath  = $dir . '/local/staticalproxy.php';

        $this->configurator = new ProxyConfigurator($this->composer, $this->nullIo, ['config-dir' => $dir]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (\is_file($this->globalPath)) {
            \unlink($this->globalPath);
        }

        if (\is_file($this->localPath)) {
            \unlink($this->localPath);
            \rmdir(\dirname($this->localPath));
        }

        @\rmdir(__DIR__ . '/ProxyConfiguratorTest');
    }

    public function testConfigureWithGlobalProxy(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                self::class => ['global'],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test', $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(self::class, $array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']);
    }

    public function testConfigureWithGlobalAndLocalProxy(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                self::class => ['global', 'local'],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test', $this->globalPath));
        static::assertTrue($this->isFileMarked('test', $this->localPath));

        $array = include $this->globalPath;

        static::assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $array = include $this->localPath;

        static::assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));
    }

    public function testSkipMarkedFiles(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                self::class => ['global'],
            ],
        ]);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $this->configurator->configure($package);

        static::assertCount(1, $array['viserio']['staticalproxy']['aliases']);
    }

    public function testUpdateExistedFileWithGlobalProxy(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                self::class => ['global'],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test', $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(self::class, $array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']);

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                Package::class => ['global'],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test2', $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(self::class, $array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']);
        static::assertSame(Package::class, $array['viserio']['staticalproxy']['aliases']['Package']);
    }

    public function testUpdateAExistedFileWithGlobalAndLocalProxy(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                self::class => ['global', 'local'],
            ],
        ]);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $array = include $this->localPath;

        static::assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                Package::class => ['global', 'local'],
            ],
        ]);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));
        static::assertSame(Package::class, \end($array['viserio']['staticalproxy']['aliases']));

        $array = include $this->localPath;

        static::assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));
        static::assertSame(Package::class, \end($array['viserio']['staticalproxy']['aliases']));
    }

    public function testConfigureWithEmptyProxiesConfig(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
            ],
        ]);

        $this->configurator->configure($package);

        static::assertFileNotExists($this->globalPath);
    }

    public function testUnconfigureWithGlobalProxies(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                self::class => ['global'],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test', $this->globalPath));

        $this->configurator->unconfigure($package);

        static::assertFalse($this->isFileMarked('test', $this->globalPath));

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                Package::class => ['global'],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test2', $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(Package::class, \reset($array['viserio']['staticalproxy']['aliases']));
        static::assertFalse(isset($array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']));
    }

    public function testUnconfigureAndConfigureAgain(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'proxies'   => [
                self::class    => ['global'],
                Package::class => ['local'],
            ],
        ]);

        $this->configurator->configure($package);
        $this->configurator->unconfigure($package);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertCount(1, $array['viserio']['staticalproxy']['aliases']);
    }
}
