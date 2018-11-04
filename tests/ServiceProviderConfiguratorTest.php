<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Test\Configurator;

use Composer\Composer;
use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Package;
use Narrowspark\Automatic\Common\Traits\PhpFileMarkerTrait;
use Narrowspark\Automatic\Configurator\ServiceProviderConfigurator;
use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;

/**
 * @internal
 */
final class ServiceProviderConfiguratorTest extends MockeryTestCase
{
    use PhpFileMarkerTrait;

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\IOInterface|\Mockery\MockInterface
     */
    private $ioMock;

    /**
     * @var \Narrowspark\Automatic\Configurator\ServiceProviderConfigurator
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
        $this->ioMock   = $this->mock(IOInterface::class);

        $dir = __DIR__ . '/ServiceProviderConfiguratorTest';

        $this->globalPath = $dir . '/serviceproviders.php';
        $this->localPath  = $dir . '/local/serviceproviders.php';

        $this->configurator = new ServiceProviderConfigurator($this->composer, $this->ioMock, ['config-dir' => $dir]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        @\unlink($this->globalPath);
        @\unlink($this->localPath);
        @\rmdir(\dirname($this->localPath));
        @\rmdir(__DIR__ . '/ServiceProviderConfiguratorTest');
    }

    public function testGetName(): void
    {
        $this->assertSame('providers', ServiceProviderConfigurator::getName());
    }

    public function testConfigureWithGlobalProvider(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a global service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked('test', $this->globalPath));

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array[0]);
    }

    public function testConfigureWithGlobalAndLocalProvider(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    self::class => ['global', 'local'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a global service provider.', true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a local service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $this->assertTrue($this->isFileMarked('test', $this->globalPath));
        $this->assertTrue($this->isFileMarked('test', $this->localPath));

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array[0]);

        $array = include $this->localPath;

        $this->assertSame(self::class, $array[0]);
    }

    public function testSkipMarkedFiles(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->twice()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a global service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array[0]);

        $this->configurator->configure($package);

        $this->assertFalse(isset($array[1]));
    }

    public function testUpdateAExistedFileWithGlobalProvider(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a global service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array[0]);

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    Package::class => ['global'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Common\Package::class] as a global service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array[0]);
        $this->assertSame(Package::class, $array[1]);
    }

    public function testUpdateAExistedFileWithGlobalAndLocalProvider(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    self::class => ['global', 'local'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a global service provider.', true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a local service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array[0]);

        $array = include $this->localPath;

        $this->assertSame(self::class, $array[0]);

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    Package::class => ['global', 'local'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Common\Package::class] as a global service provider.', true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Common\Package::class] as a local service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(self::class, $array[0]);
        $this->assertSame(Package::class, $array[1]);

        $array = include $this->localPath;

        $this->assertSame(self::class, $array[0]);
        $this->assertSame(Package::class, $array[1]);
    }

    public function testConfigureWithEmptyProvidersConfig(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - No configuration was found', true, IOInterface::VERY_VERBOSE);

        $this->configurator->configure($package);

        $this->assertFileNotExists($this->globalPath);
    }

    /**
     * @FIXME Find a good way to remove providers from other required packages
     */
//    public function testConfigureRemoveAProviderFromAOtherPackageOnlyIfPackageIsRequired(): void
//    {
//        $package = new Package(
//            'test',
//            __DIR__,
//            [
//                'version'   => '1',
//                'requires'  => [],
//                'providers' => [
//                    self::class => ['global']
//                ],
//            ]
//        );
//
//        $this->configurator->configure($package);
//
//        $package = new Package(
//            'test2',
//            __DIR__,
//            [
//                'version'  => '1',
//                'requires' => [
//                    'test',
//                ],
//                'providers' => [
//                    Package::class => ['global'],
//                    'remove' => [
//                        self::class => ['global'],
//                    ],
//                ],
//            ]
//        );
//
//        $this->configurator->configure($package);
//
//        $array = include $this->globalPath;
//
//        self::assertSame(Package::class, $array[0]);
//        self::assertFalse(isset($array[1]));
//    }

    public function testUnconfigureWithGlobalProviders(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    self::class => ['global'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a global service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Disable the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);

        $this->configurator->unconfigure($package);

        $package = new Package('test2', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    Package::class => ['global'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('        - Enabling [\Narrowspark\Automatic\Common\Package::class] as a global service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertSame(Package::class, $array[0]);
        $this->assertFalse(isset($array[1]));
    }

    public function testUnconfigureAndConfigureAgain(): void
    {
        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'providers' => [
                    self::class    => ['global'],
                    Package::class => ['local'],
                ],
            ],
        ]);

        $this->ioMock->shouldReceive('writeError')
            ->twice()
            ->with(['    - Enabling the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->twice()
            ->with('        - Enabling [\Narrowspark\Automatic\Test\Configurator\ServiceProviderConfiguratorTest::class] as a global service provider.', true, IOInterface::VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->twice()
            ->with('        - Enabling [\Narrowspark\Automatic\Common\Package::class] as a local service provider.', true, IOInterface::VERBOSE);

        $this->configurator->configure($package);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Disable the package as a Narrowspark service provider'], true, IOInterface::VERBOSE);

        $this->configurator->unconfigure($package);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        $this->assertFalse(isset($array[0], $array[1]));
    }

    /**
     * {@inheritdoc}
     */
    protected function allowMockingNonExistentMethods($allow = false): void
    {
        parent::allowMockingNonExistentMethods(true);
    }
}
