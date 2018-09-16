<?php
declare(strict_types=1);
namespace Narrowspark\Automatic\Configurator\Traits;

trait DumpTrait
{
    /**
     * Dump file content.
     *
     * @param string $filePath
     * @param string $content
     *
     * @return void
     */
    protected function dump(string $filePath, string $content): void
    {
        $this->filesystem->dumpFile($filePath, $content);

        // @codeCoverageIgnoreStart
        if (\function_exists('opcache_invalidate')) {
            \opcache_invalidate($filePath);
        }
        // @codeCoverageIgnoreEnd
    }
}