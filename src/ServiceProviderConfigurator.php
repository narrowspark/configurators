<?php

declare(strict_types=1);

namespace Narrowspark\Automatic\Configurator;

use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use Narrowspark\Automatic\Configurator\Traits\GetSortedClassesTrait;
use function count;
use function file_exists;
use function file_get_contents;
use function sprintf;
use function strpos;
use function unlink;

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
        if (file_exists($filePath)) {
            $content = (string) file_get_contents($filePath);

            unlink($filePath);
        } else {
            $content = '<?php' . "\n\n" . 'declare(strict_types=1);' . "\n\n" . 'return [' . "\n" . '];' . "\n";
        }

        if (count($classes) !== 0) {
            $content = $this->doInsertStringBeforePosition(
                $content,
                $this->buildClassNamesContent($package, $classes, $type),
                (int) strpos($content, '];')
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
            $content .= '    ' . $class . ',' . "\n";

            $this->io->writeError(sprintf('        - Enabling [%s] as a %s service provider.', $class, $type), true, IOInterface::DEBUG);
        }

        return $this->markData($package->getName(), $content);
    }
}
