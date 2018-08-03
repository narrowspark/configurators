<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Test\Configurator;

use Composer\Composer;
use Composer\IO\NullIO;
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
     * @var \Composer\IO\NullIo
     */
    private $nullIo;

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
        $this->nullIo   = new NullIO();

        $dir = __DIR__ . '/ServiceProviderConfiguratorTest';

        $this->globalPath = $dir . '/serviceproviders.php';
        $this->localPath  = $dir . '/local/serviceproviders.php';

        $this->configurator = new ServiceProviderConfigurator($this->composer, $this->nullIo, ['config-dir' => $dir]);
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

        @\rmdir(__DIR__ . '/ServiceProviderConfiguratorTest');
    }

    public function testConfigureWithGlobalProvider(): void
    {
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    self::class => ['global'],
                ],
            ]
        );

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test', $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(self::class, $array[0]);
    }

    public function testConfigureWithGlobalAndLocalProvider(): void
    {
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    self::class => ['global', 'local'],
                ],
            ]
        );

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked('test', $this->globalPath));
        static::assertTrue($this->isFileMarked('test', $this->localPath));

        $array = include $this->globalPath;

        static::assertSame(self::class, $array[0]);

        $array = include $this->localPath;

        static::assertSame(self::class, $array[0]);
    }

    public function testSkipMarkedFiles(): void
    {
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    self::class => ['global'],
                ],
            ]
        );

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, $array[0]);

        $this->configurator->configure($package);

        static::assertFalse(isset($array[1]));
    }

    public function testUpdateAExistedFileWithGlobalProvider(): void
    {
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    self::class => ['global'],
                ],
            ]
        );

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, $array[0]);

        $package = new Package(
            'test2',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    Package::class => ['global'],
                ],
            ]
        );

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, $array[0]);
        static::assertSame(Package::class, $array[1]);
    }

    public function testUpdateAExistedFileWithGlobalAndLocalProvider(): void
    {
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    self::class => ['global', 'local'],
                ],
            ]
        );

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, $array[0]);

        $array = include $this->localPath;

        static::assertSame(self::class, $array[0]);

        $package = new Package(
            'test2',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    Package::class => ['global', 'local'],
                ],
            ]
        );

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(self::class, $array[0]);
        static::assertSame(Package::class, $array[1]);

        $array = include $this->localPath;

        static::assertSame(self::class, $array[0]);
        static::assertSame(Package::class, $array[1]);
    }

    public function testConfigureWithEmptyProvidersConfig(): void
    {
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                ],
            ]
        );

        $this->configurator->configure($package);

        static::assertFileNotExists($this->globalPath);
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
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    self::class => ['global'],
                ],
            ]
        );

        $this->configurator->configure($package);
        $this->configurator->unconfigure($package);

        $package = new Package(
            'test2',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    Package::class => ['global'],
                ],
            ]
        );

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertSame(Package::class, $array[0]);
        static::assertFalse(isset($array[1]));
    }

    public function testUnconfigureAndConfigureAgain(): void
    {
        $package = new Package(
            'test',
            __DIR__,
            [
                'version'   => '1',
                'url'       => 'example.local',
                'type'      => 'library',
                'operation' => 'i',
                'providers' => [
                    self::class    => ['global'],
                    Package::class => ['local'],
                ],
            ]
        );

        $this->configurator->configure($package);
        $this->configurator->unconfigure($package);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertFalse(isset($array[0], $array[1]));
    }
}
