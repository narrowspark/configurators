<?php

declare(strict_types=1);

namespace Narrowspark\Automatic\Configurator;

use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Configurator\AbstractConfigurator;
use Narrowspark\Automatic\Common\Contract\Configurator as ConfiguratorContract;
use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use Narrowspark\Automatic\Configurator\Traits\DumpTrait;
use const DIRECTORY_SEPARATOR;
use function array_keys;
use function class_exists;
use function count;
use function end;
use function explode;
use function gettype;
use function implode;
use function interface_exists;
use function is_int;
use function is_string;
use function ltrim;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strpos;
use function strtolower;
use function var_export;

final class OptionsConfigurator extends AbstractConfigurator
{
    use DumpTrait;

    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'options';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(PackageContract $package): void
    {
        $this->write('Writing package configuration');

        $options = (array) $package->getConfig(ConfiguratorContract::TYPE, self::getName());

        if (count($options) === 0) {
            $this->io->writeError('      - No configuration was found', true, IOInterface::VERY_VERBOSE);

            return;
        }

        foreach (array_keys($options) as $env) {
            $content = '<?php' . "\n\n" . 'declare(strict_types=1);' . "\n\n" . 'return ';
            $content .= $this->print($options[$env]) . ';' . "\n";

            $this->dump(
                $this->getConfigFilePath($package, $env),
                $content
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unconfigure(PackageContract $package): void
    {
        $this->write('Removing package configuration');

        $options = (array) $package->getConfig(ConfiguratorContract::TYPE, self::getName());

        foreach (array_keys($options) as $env) {
            $this->filesystem->remove($this->getConfigFilePath($package, $env));
        }
    }

    /**
     * Returns a pretty php array for saving or output.
     *
     * @param array $data
     * @param int   $indentLevel
     *
     * @return string
     */
    private function print(array $data, int $indentLevel = 1): string
    {
        $indent = str_repeat(' ', $indentLevel * 4);
        $entries = [];

        foreach ($data as $key => $value) {
            if (! is_int($key)) {
                if ($this->isClass($key)) {
                    $class = str_replace('\\\\', '\\', $key);
                    $class = sprintf('%s::class', $class);

                    $key = strpos($class, '\\') === 0 ? $class : '\\' . $class;
                } else {
                    $key = sprintf("'%s'", $key);
                }
            }

            $entries[] = sprintf(
                '%s%s%s,',
                $indent,
                sprintf('%s => ', $key),
                $this->createValue($value, $indentLevel)
            );
        }

        $outerIndent = str_repeat(' ', ($indentLevel - 1) * 4);

        return sprintf('[' . "\n" . '%s' . "\n" . '%s]', implode("\n", $entries), $outerIndent);
    }

    /**
     * Create the right value.
     *
     * @param mixed $value
     * @param int   $indentLevel
     *
     * @return string
     */
    private function createValue($value, int $indentLevel): string
    {
        $type = gettype($value);

        if ($type === 'array') {
            return $this->print($value, $indentLevel + 1);
        }

        if ($this->isClass($value)) {
            $class = str_replace('\\\\', '\\', $value);
            $class = sprintf('%s::class', $class);

            return strpos($class, '\\') === 0 ? $class : '\\' . $class;
        }

        return var_export($value, true);
    }

    /**
     * Check if entry is a class.
     *
     * @param mixed $key
     *
     * @return bool
     */
    private function isClass($key): bool
    {
        if (! is_string($key)) {
            return false;
        }

        $key = ltrim($key, '\\');
        $firstChar = $key[0];

        return (class_exists($key) || interface_exists($key)) && strtolower($firstChar) !== $firstChar;
    }

    /**
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param string                                         $env
     *
     * @return string
     */
    private function getConfigFilePath(PackageContract $package, string $env): string
    {
        $explode = explode('/', $package->getName());
        $envFolder = $env === 'global' ? '' : $env . DIRECTORY_SEPARATOR;

        return self::expandTargetDir($this->options, '%CONFIG_DIR%' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . $envFolder . end($explode) . '.php');
    }
}
