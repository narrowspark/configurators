<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Configurator\Traits;

use Narrowspark\Automatic\Common\Contract\Package as PackageContract;

trait GetSortedClassesTrait
{
    /**
     * Returns a sorted array of given classes, from package extra options.
     *
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param string                                         $key
     *
     * @return array
     */
    protected function getSortedClasses(PackageContract $package, string $key): array
    {
        $sortedProviders = [];

        foreach ($package->getConfiguratorOptions($key) as $provider => $environments) {
            $class = \mb_strpos($provider, '::class') !== false ? $provider : $provider . '::class';

            foreach ($environments as $environment) {
                $sortedProviders[$environment][$class] = '\\' . \ltrim((string) $class, '\\');
            }
        }

        return $sortedProviders;
    }
}
