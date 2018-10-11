<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Configurator;

use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use Narrowspark\Automatic\Configurator\Traits\GetSortedClassesTrait;

final class ProxyConfigurator extends AbstractClassConfigurator
{
    use GetSortedClassesTrait;

    /**
     * {@inheritdoc}
     */
    protected static $optionName = 'proxies';

    /**
     * {@inheritdoc}
     */
    protected static $configureOutputMessage = 'Enabling the package as a Narrowspark proxy';

    /**
     * {@inheritdoc}
     */
    protected static $unconfigureOutputMessage = 'Disable the package as a Narrowspark proxy';

    /**
     * {@inheritdoc}
     */
    protected static $configFileName = 'staticalproxy';

    /**
     * {@inheritdoc}
     */
    protected static $spaceMultiplication = 16;

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
            $content = '<?php' . \PHP_EOL . 'declare(strict_types=1);' . \PHP_EOL . \PHP_EOL . 'return [' . \PHP_EOL . '    \'viserio\' => [' . \PHP_EOL . '        \'staticalproxy\' => [' . \PHP_EOL . '            \'aliases\' => [' . \PHP_EOL . '            ],' . \PHP_EOL . '        ],' . \PHP_EOL . '    ],' . \PHP_EOL . '];';
        }

        if (\count($classes) !== 0) {
            $startPositionOfAliasesArray = \mb_strpos($content, '\'aliases\' => [') + \mb_strlen('\'aliases\' => [');
            $endPositionOfAliasesArray   = \mb_strpos($content, '            ],', $startPositionOfAliasesArray);

            $content = $this->doInsertStringBeforePosition(
                $content,
                $this->buildClassNamesContent($package, $classes, $env),
                (int) $endPositionOfAliasesArray
            );
        }

        return $content;
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
        $spaces  = \str_repeat(' ', static::$spaceMultiplication);

        foreach ($classes as $class) {
            $className = \explode('\\', $class);
            $className = \end($className);

            $content .= $spaces . '\'' . \str_replace('::class', '', $className) . '\' => ' . $class . ',' . \PHP_EOL;

            $this->write(\sprintf('Enabling [%s] as a %s proxy.', $class, $env));
        }

        return $this->markData($package->getName(), $content, 16);
    }
}
