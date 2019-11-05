<?php

declare(strict_types=1);

namespace Narrowspark\Automatic\Configurator;

use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Contract\Configurator as ConfiguratorContract;
use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use function array_values;
use function count;
use function file_exists;
use function file_get_contents;
use function implode;
use function ltrim;
use function sprintf;
use function strpos;
use function unlink;

final class BootstrapConfigurator extends AbstractClassConfigurator
{
    /**
     * {@inheritdoc}
     */
    protected static $optionName = 'bootstrap';

    /**
     * {@inheritdoc}
     */
    protected static $configureOutputMessage = 'Enabling package kernel bootstraps';

    /**
     * {@inheritdoc}
     */
    protected static $unconfigureOutputMessage = 'Disable package kernel bootstraps';

    /**
     * {@inheritdoc}
     */
    protected static $configFileName = 'bootstrap';

    /**
     * {@inheritdoc}
     */
    protected function generateFileContent(
        PackageContract $package,
        string $filePath,
        array $classes,
        string $env
    ): string {
        if (file_exists($filePath)) {
            $content = (string) file_get_contents($filePath);

            unlink($filePath);
        } else {
            $content = '<?php' . "\n\n" . 'declare(strict_types=1);' . "\n\n" . 'return [' . "\n" . '];' . "\n";
        }

        if (count($classes) !== 0) {
            $content = $this->doInsertStringBeforePosition(
                $content,
                $this->buildClassNamesContent($package, $classes),
                (int) strpos($content, '];')
            );
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSortedClasses(PackageContract $package, string $key): array
    {
        $sortedClasses = [];

        foreach ((array) $package->getConfig(ConfiguratorContract::TYPE, self::getName()) as $class => $values) {
            $class = strpos($class, '::class') !== false ? $class : $class . '::class';
            $class = '\\' . ltrim((string) $class, '\\');

            $sortedClasses['global'][$class] = (array) $values;
        }

        return $sortedClasses;
    }

    /**
     * Builds a array value with class names.
     *
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param array                                          $classes
     *
     * @return string
     */
    private function buildClassNamesContent(PackageContract $package, array $classes): string
    {
        $content = '';
        $types = array_values($classes);

        foreach ($classes as $class => $data) {
            $content .= '    ' . $class . ' => [\'' . implode('\', \'', $data) . "'],\n";

            $this->io->writeError(
                sprintf('      - Enabling [%s] as [\'%s\'] bootstrapper', $class, implode('\', \'', $types[0])),
                true,
                IOInterface::VERY_VERBOSE
            );
        }

        return $this->markData($package->getName(), $content);
    }
}
