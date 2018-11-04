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

    public function testGetName(): void
    {
        $this->assertSame('proxies', ProxyConfigurator::getName());
    }

    public function testConfigureWithGlobalProxy(): void
    {
        $name = 'test/proxy';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']);
    }

    public function testConfigureWithGlobalAndLocalProxy(): void
    {
        $name = 'test/proxy';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    self::class => ['global', 'local'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked($name, $this->globalPath));
        $this->assertTrue($this->isFileMarked($name, $this->localPath));

        $array = include $this->globalPath;

        $this->assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $array = include $this->localPath;

        $this->assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));
    }

    public function testSkipMarkedFiles(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $this->configurator->configure($package);

        $this->assertCount(1, $array['viserio']['staticalproxy']['aliases']);
    }

    public function testUpdateExistedFileWithGlobalProxy(): void
    {
        $name = 'test/proxy';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']);

        $name = 'test/proxy2';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    Package::class => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']);
        $this->assertSame(Package::class, $array['viserio']['staticalproxy']['aliases']['Package']);
    }

    public function testUpdateAExistedFileWithGlobalAndLocalProxy(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    self::class => ['global', 'local'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $array = include $this->localPath;

        $this->assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    Package::class => ['global', 'local'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));
        $this->assertSame(Package::class, \end($array['viserio']['staticalproxy']['aliases']));

        $array = include $this->localPath;

        $this->assertSame(self::class, \reset($array['viserio']['staticalproxy']['aliases']));
        $this->assertSame(Package::class, \end($array['viserio']['staticalproxy']['aliases']));
    }

    public function testConfigureWithEmptyProxiesConfig(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $this->assertFileNotExists($this->globalPath);
    }

    public function testUnconfigureWithGlobalProxies(): void
    {
        $name = 'test/proxy';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked($name, $this->globalPath));

        $this->configurator->unconfigure($package);

        $this->assertFalse($this->isFileMarked($name, $this->globalPath));

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    Package::class => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked('test2', $this->globalPath));

        $array = include $this->globalPath;

        $this->assertSame(Package::class, \reset($array['viserio']['staticalproxy']['aliases']));
        $this->assertFalse(isset($array['viserio']['staticalproxy']['aliases']['ProxyConfiguratorTest']));
    }

    public function testUnconfigureAndConfigureAgain(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'proxies'   => [
                    self::class    => ['global'],
                    Package::class => ['local'],
                ],
            ],
        ]);

        $this->configurator->configure($package);
        $this->configurator->unconfigure($package);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertCount(1, $array['viserio']['staticalproxy']['aliases']);
    }
}
