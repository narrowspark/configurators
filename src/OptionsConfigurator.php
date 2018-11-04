<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Configurator;

use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Configurator\AbstractConfigurator;
use Narrowspark\Automatic\Common\Contract\Configurator as ConfiguratorContract;
use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use Narrowspark\Automatic\Configurator\Traits\DumpTrait;

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

        if (\count($options) === 0) {
            $this->io->writeError('      - No configuration was found', true, IOInterface::VERY_VERBOSE);

            return;
        }

        foreach (\array_keys($options) as $env) {
            $content = '<?php' . \PHP_EOL . 'declare(strict_types=1);' . \PHP_EOL . \PHP_EOL . 'return ';
            $content .= $this->print($options[$env]) . ';' . \PHP_EOL;

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

        if (\count($options) === 0) {
            return;
        }

        foreach (\array_keys($options) as $env) {
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
        $indent  = \str_repeat(' ', $indentLevel * 4);
        $entries = [];

        foreach ($data as $key => $value) {
            if (! \is_int($key)) {
                if ($this->isClass($key)) {
                    $class = \str_replace('\\\\', '\\', $key);
                    $class = \sprintf('%s::class', $class);

                    $key = \mb_strpos($class, '\\') === 0 ? $class : '\\' . $class;
                } else {
                    $key = \sprintf("'%s'", $key);
                }
            }

            $entries[] = \sprintf(
                '%s%s%s,',
                $indent,
                \sprintf('%s => ', $key),
                $this->createValue($value, $indentLevel)
            );
        }

        $outerIndent = \str_repeat(' ', ($indentLevel - 1) * 4);

        return \sprintf('[' . \PHP_EOL . '%s' . \PHP_EOL . '%s]', \implode(\PHP_EOL, $entries), $outerIndent);
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
        $type = \gettype($value);

        if ($type === 'array') {
            return $this->print($value, $indentLevel + 1);
        }

        if ($this->isClass($value)) {
            $class = \str_replace('\\\\', '\\', $value);
            $class = \sprintf('%s::class', $class);

            return \mb_strpos($class, '\\') === 0 ? $class : '\\' . $class;
        }

        return \var_export($value, true);
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
        if (! \is_string($key)) {
            return false;
        }

        $key       = \ltrim($key, '\\');
        $firstChar = \mb_substr($key, 0, 1);

        return (\class_exists($key) || \interface_exists($key)) && \mb_strtolower($firstChar) !== $firstChar;
    }

    /**
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param string                                         $env
     *
     * @return string
     */
    private function getConfigFilePath(PackageContract $package, string $env): string
    {
        $explode   = \explode('/', $package->getName());
        $envFolder = $env === 'global' ? '' : $env . \DIRECTORY_SEPARATOR;

        return self::expandTargetDir($this->options, '%CONFIG_DIR%' . \DIRECTORY_SEPARATOR . 'packages' . \DIRECTORY_SEPARATOR . $envFolder . \end($explode) . '.php');
    }
}
