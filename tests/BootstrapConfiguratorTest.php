<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Test\Configurator;

use Composer\Composer;
use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Package;
use Narrowspark\Automatic\Common\Traits\PhpFileMarkerTrait;
use Narrowspark\Automatic\Configurator\BootstrapConfigurator;
use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;

/**
 * @internal
 */
final class BootstrapConfiguratorTest extends MockeryTestCase
{
    use PhpFileMarkerTrait;

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\NullIo
     */
    private $ioMock;

    /**
     * @var \Narrowspark\Automatic\Configurator\BootstrapConfigurator
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
     * @var string
     */
    private $testingPath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->composer = new Composer();
        $this->ioMock   = $this->mock(IOInterface::class);

        $dir = __DIR__ . '/BootstrapConfiguratorTest';

        $this->globalPath   = $dir . '/bootstrap.php';
        $this->localPath    = $dir . '/local/bootstrap.php';
        $this->testingPath  = $dir . '/testing/bootstrap.php';

        $this->configurator = new BootstrapConfigurator($this->composer, $this->ioMock, ['config-dir' => $dir]);
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

        @\unlink($this->testingPath);
        @\rmdir(\dirname($this->testingPath));

        @\rmdir(__DIR__ . '/BootstrapConfiguratorTest');
    }

    public function testConfigureWithGlobalBootstrap(): void
    {
        $this->arrangeEnableMessage();

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\LoadEnvironmentVariables::class] as [\'global\'] bootstrapper in [global] environment', true, IOInterface::VERY_VERBOSE);

        $name = 'test/bootstrap';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [
                    'Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables' => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(['global'], $array['Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables']);
    }

    public function testConfigureWithEnvBasedBootstraps(): void
    {
        $this->arrangeEnableMessage();

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\ConfigureKernel::class] as [\'global\'] bootstrapper in [local] environment', true, IOInterface::VERY_VERBOSE);
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\ConfigureKernel::class] as [\'console\', \'http\'] bootstrapper in [testing] environment', true, IOInterface::VERY_VERBOSE);

        $name = 'test/bootstrap';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [
                    'Viserio\\Component\\Foundation\\Bootstrap\\ConfigureKernel' => [
                        [
                            'env'  => 'local',
                            'type' => ['global'],
                        ],
                        [
                            'env'  => 'testing',
                            'type' => ['console', 'http'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked($name, $this->localPath));

        $array = include $this->localPath;

        static::assertSame(['global'], $array['Viserio\\Component\\Foundation\\Bootstrap\\ConfigureKernel']);
        static::assertTrue($this->isFileMarked($name, $this->testingPath));

        $array = include $this->testingPath;

        static::assertSame(['console', 'http'], $array['Viserio\\Component\\Foundation\\Bootstrap\\ConfigureKernel']);
    }

    public function testConfigureWithEmpty(): void
    {
        $this->arrangeEnableMessage();

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - No configuration was found', true, IOInterface::VERY_VERBOSE);

        $package = new Package('test/bar', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [],
            ],
        ]);

        $this->configurator->configure($package);
    }

    public function testSkipMarkedFiles(): void
    {
        $this->arrangeEnableMessage();
        $this->arrangeEnableMessage(); // for the second configure call

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\LoadEnvironmentVariables::class] as [\'global\'] bootstrapper in [global] environment', true, IOInterface::VERY_VERBOSE);

        $package = new Package('test', '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [
                    'Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables' => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        $array = include $this->globalPath;

        static::assertCount(1, $array);

        $this->configurator->configure($package);

        static::assertCount(1, $array);
    }

    public function testConfigureWith2Packages(): void
    {
        $this->arrangeEnableMessage();

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\LoadEnvironmentVariables::class] as [\'global\'] bootstrapper in [global] environment', true, IOInterface::VERY_VERBOSE);

        $name = 'test/bootstrap';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [
                    'Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables' => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(['global'], $array['Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables']);

        $this->arrangeEnableMessage();

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\LoadEnvironmentVariables2::class] as [\'global\'] bootstrapper in [global] environment', true, IOInterface::VERY_VERBOSE);

        $name = 'test/bootstrap2';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [
                    'Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables2' => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(['global'], $array['Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables2']);
    }

    public function testUnconfigure(): void
    {
        $this->arrangeEnableMessage();

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\LoadEnvironmentVariables::class] as [\'global\'] bootstrapper in [global] environment', true, IOInterface::VERY_VERBOSE);

        $name = 'test/bootstrap';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [
                    'Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables' => ['global'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(['global'], $array['Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables']);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Disable package kernel bootstraps'], true, IOInterface::VERBOSE);

        $this->configurator->unconfigure($package);

        static::assertFalse($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        static::assertCount(0, $array);
    }

    public function testUnconfigureWithTwoTypes(): void
    {
        $this->arrangeEnableMessage();

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with('      - Enabling [\Viserio\Component\Foundation\Bootstrap\LoadEnvironmentVariables::class] as [\'console\', \'http\'] bootstrapper in [global] environment', true, IOInterface::VERY_VERBOSE);

        $name = 'test/bootstrap';

        $package = new Package($name, '^1.0.0');
        $package->setConfig([
            'configurators' => [
                'bootstrap' => [
                    'Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables' => ['console', 'http'],
                ],
            ],
        ]);

        $this->configurator->configure($package);

        static::assertTrue($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        static::assertSame(['console', 'http'], $array['Viserio\\Component\\Foundation\\Bootstrap\\LoadEnvironmentVariables']);

        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Disable package kernel bootstraps'], true, IOInterface::VERBOSE);

        $this->configurator->unconfigure($package);

        static::assertFalse($this->isFileMarked($name, $this->globalPath));

        $array = include $this->globalPath;

        static::assertCount(0, $array);
    }

    /**
     * {@inheritdoc}
     */
    protected function allowMockingNonExistentMethods(bool $allow = false): void
    {
        parent::allowMockingNonExistentMethods(true);
    }

    private function arrangeEnableMessage(): void
    {
        $this->ioMock->shouldReceive('writeError')
            ->once()
            ->with(['    - Enabling package kernel bootstraps'], true, IOInterface::VERBOSE);
    }
}
