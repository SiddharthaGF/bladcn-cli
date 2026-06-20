<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Installer;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Registry\DependencyResolver;
use AiluraCode\Bladcn\Registry\Registry;
use RuntimeException;

final readonly class ComponentAssetManager
{
    private const CAROUSEL_JS = 'bladcn/carousel.js';

    public function __construct(
        private BladcnConfig $config,
        private Registry $registry,
        private DependencyResolver $dependencyResolver,
    ) {}

    /**
     * @param  list<string>  $componentNames
     * @return list<string> relative paths written or updated
     */
    public function publishForComponents(array $componentNames, bool $overwrite = false): array
    {
        $written = [];

        foreach ($this->dependencyResolver->collectCssAssets($componentNames) as $cssFile) {
            $relative = $this->publishCssAsset($cssFile, $overwrite);

            if ($relative !== null) {
                $written[] = $relative;
            }

            if ($this->appendCssImport($cssFile)) {
                $written[] = 'resources/css/'.$this->cssFile();
            }
        }

        foreach ($this->dependencyResolver->collectJsAssets($componentNames) as $jsFile) {
            $relative = $this->publishJsAsset($jsFile, $overwrite);

            if ($relative !== null) {
                $written[] = $relative;
            }

            if ($jsFile === self::CAROUSEL_JS && $this->enableCarouselInBladcnJs()) {
                $written[] = 'resources/js/bladcn.js';
            }
        }

        return array_values(array_unique($written));
    }

    /**
     * @param  list<string>  $removedComponents
     * @param  list<string>  $stillInstalled
     * @return list<string> relative paths removed or updated
     */
    public function removeOrphans(array $removedComponents, array $stillInstalled): array
    {
        $changed = [];

        foreach ($this->dependencyResolver->findOrphanCssAssets($removedComponents, $stillInstalled) as $cssFile) {
            $relative = 'resources/css/'.$cssFile;

            if ($this->removeCssImport($cssFile)) {
                $changed[] = 'resources/css/'.$this->cssFile();
            }

            if (is_file($this->config->projectRoot().'/'.$relative)) {
                unlink($this->config->projectRoot().'/'.$relative);
                $changed[] = $relative;
            }
        }

        foreach ($this->dependencyResolver->findOrphanJsAssets($removedComponents, $stillInstalled) as $jsFile) {
            $relative = 'resources/js/'.$jsFile;

            if ($jsFile === self::CAROUSEL_JS && $this->disableCarouselInBladcnJs()) {
                $changed[] = 'resources/js/bladcn.js';
            }

            if (is_file($this->config->projectRoot().'/'.$relative)) {
                unlink($this->config->projectRoot().'/'.$relative);
                $changed[] = $relative;
            }
        }

        return array_values(array_unique($changed));
    }

    private function publishCssAsset(string $cssFile, bool $overwrite): ?string
    {
        $source = $this->registry->root().'/resources/css/'.$cssFile;
        $relative = 'resources/css/'.$cssFile;
        $destination = $this->config->projectRoot().'/'.$relative;

        throw_unless(is_file($source), RuntimeException::class, 'CSS asset not found in registry: '.$cssFile);

        if (is_file($destination) && ! $overwrite) {
            return null;
        }

        $parent = dirname($destination);
        if (! is_dir($parent)) {
            mkdir($parent, 0775, true);
        }

        throw_unless(copy($source, $destination), RuntimeException::class, 'Could not copy CSS asset: '.$cssFile);

        return $relative;
    }

    private function publishJsAsset(string $jsFile, bool $overwrite): ?string
    {
        $source = $this->registry->root().'/resources/js/'.$jsFile;
        $relative = 'resources/js/'.$jsFile;
        $destination = $this->config->projectRoot().'/'.$relative;

        throw_unless(is_file($source), RuntimeException::class, 'JS asset not found in registry: '.$jsFile);

        if (is_file($destination) && ! $overwrite) {
            return null;
        }

        $parent = dirname($destination);
        if (! is_dir($parent)) {
            mkdir($parent, 0775, true);
        }

        throw_unless(copy($source, $destination), RuntimeException::class, 'Could not copy JS asset: '.$jsFile);

        return $relative;
    }

    private function appendCssImport(string $cssFile): bool
    {
        $cssPath = $this->config->projectRoot().'/resources/css/'.$this->cssFile();

        if (! is_file($cssPath)) {
            return false;
        }

        $content = (string) file_get_contents($cssPath);

        if (str_contains($content, $cssFile)) {
            return false;
        }

        $updated = mb_rtrim($content).PHP_EOL.'@import "./'.$cssFile.'";'.PHP_EOL;
        file_put_contents($cssPath, $updated);

        return true;
    }

    private function removeCssImport(string $cssFile): bool
    {
        $cssPath = $this->config->projectRoot().'/resources/css/'.$this->cssFile();

        if (! is_file($cssPath)) {
            return false;
        }

        $content = (string) file_get_contents($cssPath);
        $pattern = '/^[ \t]*@import\s+["\']\.\/'.preg_quote($cssFile, '/').'["\'];\s*\r?\n?/m';
        $updated = preg_replace($pattern, '', $content, -1, $count);

        if (! is_string($updated) || $count === 0) {
            return false;
        }

        file_put_contents($cssPath, $updated);

        return true;
    }

    private function enableCarouselInBladcnJs(): bool
    {
        $path = $this->config->projectRoot().'/resources/js/bladcn.js';

        if (! is_file($path)) {
            return false;
        }

        $content = (string) file_get_contents($path);

        if (str_contains($content, 'bladcn/carousel.js')) {
            return false;
        }

        $import = 'import { registerBladcnCarousel } from "./bladcn/carousel.js";'.PHP_EOL;
        $bootstrap = PHP_EOL.'registerBladcnCarousel();'.PHP_EOL;

        if (str_starts_with($content, '/**')) {
            $end = mb_strpos($content, '*/');
            $updated = $end !== false
                ? mb_substr($content, 0, $end + 2).PHP_EOL.$import.mb_substr($content, $end + 2)
                : $import.$content;
        } else {
            $updated = $import.$content;
        }

        $marker = 'bladcnRegister("bladcnScrollArea"';
        $position = mb_strpos($updated, $marker);

        if ($position === false) {
            $updated .= $bootstrap;
        } else {
            $updated = mb_substr($updated, 0, $position).$bootstrap.mb_substr($updated, $position);
        }

        file_put_contents($path, $updated);

        return true;
    }

    private function disableCarouselInBladcnJs(): bool
    {
        $path = $this->config->projectRoot().'/resources/js/bladcn.js';

        if (! is_file($path)) {
            return false;
        }

        $content = (string) file_get_contents($path);
        $updated = preg_replace(
            '/^import \{ registerBladcnCarousel \} from "\.\/bladcn\/carousel\.js";\s*\r?\n/m',
            '',
            $content,
            -1,
            $importCount,
        );

        if (! is_string($updated)) {
            return false;
        }

        $updated = preg_replace(
            '/^\s*registerBladcnCarousel\(\);\s*\r?\n/m',
            '',
            $updated,
            -1,
            $bootstrapCount,
        );

        if (! is_string($updated) || ($importCount === 0 && $bootstrapCount === 0)) {
            return false;
        }

        file_put_contents($path, $updated);

        return true;
    }

    private function cssFile(): string
    {
        return BladcnConfig::defaultCssFile();
    }
}
