<?php

declare(strict_types=1);

namespace Narrowspark\Automatic\Configurator\Tests\Traits;

use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use Narrowspark\Automatic\Configurator\Traits\GetSortedClassesTrait;
use Narrowspark\TestingHelper\Phpunit\MockeryTestCase;

/**
 * @internal
 *
 * @small
 */
final class GetSortedClassesTraitTest extends MockeryTestCase
{
    use GetSortedClassesTrait;

    public function testGetSortedClasses(): void
    {
        $package = $this->mock(PackageContract::class);
        $package->shouldReceive('getConfig')
            ->once()
            ->with('configurators', 'providers')
            ->andReturn([
                self::class => ['global'],
                Configurator::class => ['global', 'test'],
            ]);

        $array = $this->getSortedClasses($package, 'providers');

        self::assertEquals(
            [
                'global' => [
                    self::class . '::class' => '\\' . self::class . '::class',
                    Configurator::class . '::class' => '\\' . Configurator::class . '::class',
                ],
                'test' => [
                    Configurator::class . '::class' => '\\' . Configurator::class . '::class',
                ],
            ],
            $array
        );
    }
}
