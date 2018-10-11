<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Configurator;

use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use Narrowspark\Automatic\Configurator\Traits\GetSortedClassesTrait;

final class ServiceProviderConfigurator extends AbstractClassConfigurator
{
    use GetSortedClassesTrait;

    /**
     * {@inheritdoc}
     */
    protected static $optionName = 'providers';

    /**
     * {@inheritdoc}
     */
    protected static $configureOutputMessage = 'Enabling the package as a Narrowspark service provider';

    /**
     * {@inheritdoc}
     */
    protected static $unconfigureOutputMessage = 'Disable the package as a Narrowspark service provider';

    /**
     * {@inheritdoc}
     */
    protected static $configFileName = 'serviceproviders';

    /**
     * {@inheritdoc}
     */
    protected function generateFileContent(
        PackageContract $package,
        string $filePath,
        array $classes,
        string $type
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
                $this->buildClassNamesContent($package, $classes, $type),
                (int) \mb_strpos($content, '];')
            );
        }

        return $content;
    }

    /**
     * Builds a array value with class names.
     *
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param array                                          $classes
     * @param string                                         $type
     *
     * @return string
     */
    private function buildClassNamesContent(PackageContract $package, array $classes, string $type): string
    {
        $content = '';

        foreach ($classes as $class) {
            $content .= '    ' . $class . ',' . \PHP_EOL;

            $this->write(\sprintf('Enabling [%s] as a %s service provider.', $class, $type));
        }

        return $this->markData($package->getName(), $content);
    }
}
