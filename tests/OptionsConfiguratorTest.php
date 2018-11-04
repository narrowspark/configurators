<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Test\Configurator;

use Composer\Composer;
use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Package;
use Narrowspark\Automatic\Configurator\OptionsConfigurator;
use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;

/**
 * @internal
 */
final class OptionsConfiguratorTest extends MockeryTestCase
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\IOInterface|\Mockery\MockInterface
     */
    private $ioMock;

    /**
     * @var \Narrowspark\Automatic\Configurator\ProxyConfigurator
     */
    private $configurator;

    /**
     * @var string
     */
    private $dir;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->composer = new Composer();
        $this->ioMock   = $this->mock(IOInterface::class);

        $this->dir = __DIR__ . '/OptionsConfiguratorTest';

        $this->configurator = new OptionsConfigurator($this->composer, $this->ioMock, ['config-dir' => $this->dir]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        @\rmdir($this->dir . \DIRECTORY_SEPARATOR . 'packages');
        @\rmdir($this->dir);
    }

    public function testGetName(): void
    {
        $this->assertSame('options', OptionsConfigurator::getName());
    }

    public function testConfigure(): void
    {
        $this->arrangeWriteMessage();

        $this->configurator->configure($this->arrangePackage());

        $filePath = $this->dir . \DIRECTORY_SEPARATOR . 'packages' . \DIRECTORY_SEPARATOR . 'bar.php';

        $this->assertFileExists($filePath);

        $config = require $filePath;

        $this->assertSame(
            $config,
            [
                'test'  => 'foo',
                'multi' => [
                    'test'  => 'bar',
                    'class' => self::class,
                ],
                'class'     => self::class,
                self::class => true,
            ]
        );
    }

    public function testConfigureWithEmpty(): void
    {
        $this->arrangeWriteMessage();
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - No configuration was found', true, IOInterface::VERY_VERBOSE);

        $package = new Package('test/bar', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'options' => [],
            ],
        ]);

        $this->configurator->configure($package);
    }

    public function testUnConfigure(): void
    {
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Removing package configuration'], true, IOInterface::VERBOSE);

        $this->configurator->unconfigure($this->arrangePackage());

        $filePath = $this->dir . \DIRECTORY_SEPARATOR . 'packages' . \DIRECTORY_SEPARATOR . 'bar.php';

        $this->assertFileNotExists($filePath);
    }

    /**
     * {@inheritdoc}
     */
    protected function allowMockingNonExistentMethods(bool $allow = false): void
    {
        parent::allowMockingNonExistentMethods(true);
    }

    /**
     * @throws \Exception
     *
     * @return \Narrowspark\Automatic\Common\Package
     */
    private function arrangePackage(): Package
    {
        $package = new Package('test/bar', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'options' => [
                    'test'  => 'foo',
                    'multi' => [
                        'test'  => 'bar',
                        'class' => self::class,
                    ],
                    'class'     => self::class,
                    self::class => true,
                ],
            ],
        ]);

        return $package;
    }

    private function arrangeWriteMessage(): void
    {
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Writing package configuration'], true, IOInterface::VERBOSE);
    }
}
