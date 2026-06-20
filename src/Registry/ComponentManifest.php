<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Registry;

use AiluraCode\Bladcn\Support\Arr;

final readonly class ComponentManifest
{
    /**
     * @param  list<string>  $dependencies
     * @param  list<string>  $composer
     * @param  list<string>  $npm
     */
    public function __construct(
        public array $dependencies = [],
        public array $composer = [],
        public array $npm = [],
    ) {}

    public static function fromFile(string $path): self
    {
        if (! is_file($path)) {
            return new self;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            return new self;
        }

        return new self(
            dependencies: Arr::stringList($data['dependencies'] ?? []),
            composer: Arr::stringList($data['composer'] ?? []),
            npm: Arr::stringList($data['npm'] ?? []),
        );
    }
}
