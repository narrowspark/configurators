<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Configurator;

use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Contract\Configurator as ConfiguratorContract;
use Narrowspark\Automatic\Common\Contract\Package as PackageContract;

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
        if (\file_exists($filePath)) {
            $content = (string) \file_get_contents($filePath);

            \unlink($filePath);
        } else {
            $content = '<?php' . \PHP_EOL . 'declare(strict_types=1);' . \PHP_EOL . \PHP_EOL . 'return [' . \PHP_EOL . '];' . \PHP_EOL;
        }

        if (\count($classes) !== 0) {
            $content = $this->doInsertStringBeforePosition(
                $content,
                $this->buildClassNamesContent($package, $classes, $env),
                (int) \mb_strpos($content, '];')
            );
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    protected function replaceContent(array $data, $content): string
    {
        $spaces    = \str_repeat(' ', static::$spaceMultiplication);

        foreach ($data as $class => $types) {
            $content = \str_replace($spaces . $class . ' => [\'' . \implode('\', \'', $types) . '\'],' . \PHP_EOL, '', $content);
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
            $class = \mb_strpos($class, '::class') !== false ? $class : $class . '::class';
            $class = '\\' . \ltrim((string) $class, '\\');

            if (isset($values[0]) && \is_array($values[0])) {
                foreach ($values as $data) {
                    if (isset($data['env'], $data['type'])) {
                        $sortedClasses[$data['env']][$class] = (array) $data['type'];
                    }
                }
            } else {
                $sortedClasses['global'][$class] = (array) $values;
            }
        }

        return $sortedClasses;
    }

    /**
     * Builds a array value with class names.
     *
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param array                                          $classes
     * @param string                                         $env
     *
     * @return string
     */
    private function buildClassNamesContent(PackageContract $package, array $classes, string $env): string
    {
        $content = '';
        $types   = \array_values($classes);

        foreach ($classes as $class => $data) {
            $content .= '    ' . $class . ' => [\'' . \implode('\', \'', $data) . "']," . \PHP_EOL;

            $this->io->writeError(
                \sprintf('      - Enabling [%s] as [\'%s\'] bootstrapper in [%s] environment', $class, \implode('\', \'', $types[0]), $env),
                true,
                IOInterface::VERY_VERBOSE
            );
        }

        return $this->markData($package->getName(), $content);
    }
}
