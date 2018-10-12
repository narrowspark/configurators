<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Configurator;

use Composer\IO\IOInterface;
use Narrowspark\Automatic\Common\Configurator\AbstractConfigurator;
use Narrowspark\Automatic\Common\Contract\Package as PackageContract;
use Narrowspark\Automatic\Common\Traits\PhpFileMarkerTrait;
use Narrowspark\Automatic\Configurator\Traits\DumpTrait;

/**
 * @internal
 */
abstract class AbstractClassConfigurator extends AbstractConfigurator
{
    use DumpTrait;
    use PhpFileMarkerTrait;

    /**
     * The composer option name.
     *
     * @var string
     */
    protected static $optionName;

    /**
     * The output configure write message.
     *
     * @var string
     */
    protected static $configureOutputMessage;

    /**
     * The output unconfigure write message.
     *
     * @var string
     */
    protected static $unconfigureOutputMessage;

    /**
     * The config file name.
     *
     * @var string
     */
    protected static $configFileName;

    /**
     * Configure the space repeat.
     *
     * @var int
     */
    protected static $spaceMultiplication = 4;

    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return static::$optionName;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(PackageContract $package): void
    {
        $this->write(static::$configureOutputMessage);

        $sortedClasses = $this->getSortedClasses($package, static::$optionName);

        if (\count($sortedClasses) === 0) {
            $this->io->writeError('      - No configuration was found', true, IOInterface::VERY_VERBOSE);

            return;
        }

        foreach ($sortedClasses as $env => $classes) {
            $filePath = $this->getConfFile($env);

            if ($this->isFileMarked($package->getName(), $filePath)) {
                continue;
            }

            $this->dump(
                $filePath,
                $this->generateFileContent($package, $filePath, $classes, $env)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unconfigure(PackageContract $package): void
    {
        $this->write(static::$unconfigureOutputMessage);

        $sortedClasses = $this->getSortedClasses($package, static::$optionName);

        if (\count($sortedClasses) === 0) {
            return;
        }

        $envs = \array_keys($sortedClasses);

        /** @param string[] $contents */
        $contents = [];

        foreach ($envs as $env) {
            $filePath = $this->getConfFile($env);

            if (! $this->isFileMarked($package->getName(), $filePath)) {
                continue;
            }

            $contents[$env] = (string) \file_get_contents($filePath);

            \unlink($filePath);
        }

        foreach ($sortedClasses as $env => $data) {
            if (! isset($contents[$env])) {
                continue;
            }

            $contents[$env] = $this->replaceContent($contents[$env], $package);
        }

        foreach ($contents as $key => $content) {
            $this->dump($this->getConfFile((string) $key), $content);
        }
    }

    /**
     * Get service providers config file.
     *
     * @param string $type
     *
     * @return string
     */
    protected function getConfFile(string $type): string
    {
        $type = $type === 'global' ? '' : $type . \DIRECTORY_SEPARATOR;

        return self::expandTargetDir($this->options, '%CONFIG_DIR%' . \DIRECTORY_SEPARATOR . $type . static::$configFileName . '.php');
    }

    /**
     * Generate file content.
     *
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param string                                         $filePath
     * @param array                                          $classes
     * @param string                                         $env
     *
     * @return string
     */
    abstract protected function generateFileContent(
        PackageContract $package,
        string $filePath,
        array $classes,
        string $env
    ): string;

    /**
     * Replace a string in content.
     *
     * @param string                                         $content
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     *
     * @return string
     */
    protected function replaceContent(string $content, PackageContract $package): string
    {
        $count  = 0;
        $spaces = \str_repeat(' ', static::$spaceMultiplication);

        $replacedContent = \preg_replace(
            \sprintf('{%s/\*\* > %s \*\*\/.*%s\/\*\* %s < \*\*\/%s}s', $spaces, $package->getPrettyName(), $spaces, $package->getPrettyName(), "\n"),
            '',
            $content,
            -1,
            $count
        );

        if ($count === 0) {
            return $content;
        }

        return $replacedContent;
    }

    /**
     * Returns a sorted array of given classes, from package extra options.
     *
     * @param \Narrowspark\Automatic\Common\Contract\Package $package
     * @param string                                         $key
     *
     * @return array
     */
    abstract protected function getSortedClasses(PackageContract $package, string $key): array;
}
