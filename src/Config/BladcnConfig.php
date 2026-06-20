<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Config;

use AiluraCode\Bladcn\Support\Arr;
use AiluraCode\Bladcn\Support\PackagePath;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Env;
use RuntimeException;
use Throwable;

final readonly class BladcnConfig
{
    public const DEFAULT_REGISTRY = '../bladcn-components';

    public const DEFAULT_REGISTRY_URL = 'https://github.com/AiluraCode/bladcn-components';

    public const REGISTRY_ENV = 'BLADCN_REGISTRY';

    private const FILENAME = 'bladcn.json';

    /** @param list<string> $resolved */
    public function __construct(
        public string $componentsPath,
        public ?string $registryPath,
        public ?string $registryRepo,
        public string $registryBranch,
        public array $resolved,
        private string $projectRoot,
    ) {}

    public static function defaultRegistry(): string
    {
        return self::packageConfigString('registry') ?? self::envRegistry() ?? self::DEFAULT_REGISTRY;
    }

    public static function defaultRegistryForInit(string $projectRoot): string
    {
        $configured = self::defaultRegistry();

        if ($configured !== self::DEFAULT_REGISTRY) {
            return $configured;
        }

        $localRegistry = PackagePath::demoRegistryNear($projectRoot)
            ?? PackagePath::demoRegistryNear(PackagePath::root());

        if ($localRegistry !== null) {
            $nearProject = PackagePath::demoRegistryNear($projectRoot);

            if ($nearProject !== null) {
                return self::makeRelativePath($projectRoot, $nearProject);
            }

            return '../'.PackagePath::DEMO_REGISTRY_DIR;
        }

        return $configured;
    }

    public static function registryStringForJson(string $registry, string $projectRoot): string
    {
        $parsed = self::parseRegistry($registry, $projectRoot);

        if ($parsed['path'] !== null && is_dir($parsed['path'])) {
            $resolved = realpath($parsed['path']);

            return self::makeRelativePath($projectRoot, $resolved !== false ? $resolved : $parsed['path']);
        }

        if ($parsed['repo'] !== null) {
            return 'github:'.$parsed['repo'];
        }

        return $registry;
    }

    public static function defaultRegistryBranch(): string
    {
        return self::packageConfigString('registry_branch') ?? 'main';
    }

    public static function defaultComponentsPath(): string
    {
        return self::packageConfigString('components_path') ?? 'resources/views/components/ui';
    }

    public static function defaultCssFile(): string
    {
        return self::packageConfigString('css_file') ?? 'app.css';
    }

    public static function defaultThemeFile(): string
    {
        return self::packageConfigString('theme_file') ?? 'bladcn-theme.css';
    }

    public static function filename(): string
    {
        return self::FILENAME;
    }

    public static function load(string $projectRoot): self
    {
        $path = self::path($projectRoot);

        throw_unless(is_file($path), RuntimeException::class, 'bladcn.json not found. Run `bladcn init` from your Laravel project root.');

        $data = json_decode((string) file_get_contents($path), true);

        throw_unless(is_array($data), RuntimeException::class, 'Invalid bladcn.json.');

        /** @var array<string, mixed> $configData */
        $configData = $data;

        return self::fromArray($configData, $projectRoot);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $projectRoot): self
    {
        $componentsPath = is_string($data['componentsPath'] ?? null)
            ? $data['componentsPath']
            : 'resources/views/components/ui';

        $registry = $data['registry'] ?? null;
        $registryPath = null;
        $registryRepo = null;
        $registryBranch = 'main';

        if (is_string($registry)) {
            $parsed = self::parseRegistry($registry, $projectRoot);
            $registryPath = $parsed['path'];
            $registryRepo = $parsed['repo'];

            if ($parsed['branch'] !== null) {
                $registryBranch = $parsed['branch'];
            }
        }

        if (isset($data['registryBranch']) && is_string($data['registryBranch'])) {
            $registryBranch = $data['registryBranch'];
        }

        if (isset($data['registryPath']) && is_string($data['registryPath'])) {
            $registryPath = self::resolvePath($projectRoot, $data['registryPath']);
        }

        $resolved = [];
        if (isset($data['resolved']) && is_array($data['resolved'])) {
            $resolved = Arr::stringList($data['resolved']);
            sort($resolved);
        }

        return new self(
            componentsPath: $componentsPath,
            registryPath: $registryPath,
            registryRepo: $registryRepo,
            registryBranch: $registryBranch,
            resolved: $resolved,
            projectRoot: $projectRoot,
        );
    }

    public static function path(string $projectRoot): string
    {
        return mb_rtrim($projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.self::FILENAME;
    }

    public function projectRoot(): string
    {
        return $this->projectRoot;
    }

    public function componentsAbsolutePath(): string
    {
        return self::resolvePath($this->projectRoot, $this->componentsPath);
    }

    /** @param list<string> $resolved */
    public function withResolved(array $resolved): self
    {
        $resolved = array_values(array_unique($resolved));
        sort($resolved);

        return new self(
            componentsPath: $this->componentsPath,
            registryPath: $this->registryPath,
            registryRepo: $this->registryRepo,
            registryBranch: $this->registryBranch,
            resolved: $resolved,
            projectRoot: $this->projectRoot,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            '$schema' => './vendor/ailuracode/bladcn/resources/bladcn.schema.json',
            'componentsPath' => $this->componentsPath,
            'registryBranch' => $this->registryBranch,
            'resolved' => $this->resolved,
        ];

        if ($this->registryRepo !== null) {
            $data['registry'] = 'github:'.$this->registryRepo;
        } elseif ($this->registryPath !== null) {
            $data['registry'] = $this->makeRelative($this->projectRoot, $this->registryPath);
        }

        return $data;
    }

    public function save(): void
    {
        $path = self::path($this->projectRoot);
        $json = json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        throw_if($json === false, RuntimeException::class, 'Could not serialize bladcn.json.');

        file_put_contents($path, $json.PHP_EOL);
    }

    private static function envRegistry(): ?string
    {
        $value = Env::get(self::REGISTRY_ENV, getenv(self::REGISTRY_ENV));

        if (! is_string($value) || mb_trim($value) === '') {
            return null;
        }

        return mb_trim($value);
    }

    private static function packageConfigString(string $key): ?string
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            $app = app();
        } catch (Throwable) {
            return null;
        }

        if (! $app->bound('config')) {
            return null;
        }

        $value = $app->make(Repository::class)->get('bladcn.'.$key);

        if (! is_string($value) || mb_trim($value) === '') {
            return null;
        }

        return mb_trim($value);
    }

    /**
     * @return array{path: ?string, repo: ?string, branch: ?string}
     */
    private static function parseRegistry(string $registry, string $projectRoot): array
    {
        if (str_starts_with($registry, 'package:')) {
            $localRegistry = PackagePath::demoRegistryNear($projectRoot)
                ?? PackagePath::demoRegistryNear(PackagePath::root());

            if ($localRegistry !== null) {
                return ['path' => $localRegistry, 'repo' => null, 'branch' => null];
            }

            return [
                'path' => null,
                'repo' => PackagePath::DEFAULT_GITHUB_REGISTRY,
                'branch' => null,
            ];
        }

        if (str_starts_with($registry, 'github:')) {
            $repo = mb_trim(mb_substr($registry, mb_strlen('github:')));

            throw_if($repo === '', RuntimeException::class, 'Registry `github:` requires owner/repo.');

            return ['path' => null, 'repo' => $repo, 'branch' => null];
        }

        if (preg_match('#^https?://(?:www\.)?github\.com/([^/]+)/([^/]+?)(?:\.git)?(?:/.*)?$#', $registry, $matches) === 1) {
            $repo = $matches[1].'/'.$matches[2];
            $branch = null;

            if (preg_match('#/tree/([^/]+)#', $registry, $branchMatch) === 1) {
                $branch = $branchMatch[1];
            }

            return ['path' => null, 'repo' => $repo, 'branch' => $branch];
        }

        return [
            'path' => self::resolvePath($projectRoot, $registry),
            'repo' => null,
            'branch' => null,
        ];
    }

    private static function resolvePath(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        if ($path[0] === DIRECTORY_SEPARATOR || (mb_strlen($path) > 1 && $path[1] === ':')) {
            return $path;
        }

        return mb_rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
    }

    private static function makeRelativePath(string $base, string $absolute): string
    {
        $base = mb_rtrim(str_replace('\\', '/', $base), '/');
        $absolute = str_replace('\\', '/', $absolute);

        if (str_starts_with($absolute, $base.'/')) {
            return mb_substr($absolute, mb_strlen($base) + 1);
        }

        return $absolute;
    }

    private function makeRelative(string $base, string $absolute): string
    {
        return self::makeRelativePath($base, $absolute);
    }
}
