# bladcn CLI

[shadcn/ui](https://ui.shadcn.com)-style CLI to install Blade + Alpine components in Laravel projects.

Copies components from [bladcn-components](https://github.com/AiluraCode/bladcn-components) (CSS, JS, Blade) and resolves dependencies from each `dependencies.json`.

### Registry layout (`bladcn-components`)

```
bladcn-components/
├── app/Providers/             # BladcnServiceProvider (bladcn init)
├── resources/
│   ├── js/                    # bladcn.js + carousel (import from app.js)
│   ├── css/                   # Theme and base CSS (bladcn init)
│   ├── views/components/ui/   # Blade components (bladcn add)
│   └── views/partials/        # bladcn-boot
```

The CLI package does not ship component assets; they live in **bladcn-components** (default `../bladcn-components`).

```bash
# .env
BLADCN_REGISTRY=../bladcn-components

# optional: publish config to customize defaults
php artisan vendor:publish --tag=bladcn-config
```

```bash
export BLADCN_REGISTRY=../bladcn-components
bladcn init
```

## Installation

```bash
composer require ailuracode/bladcn --dev
```

Or clone this repo and use the binary directly:

```bash
cd bladcn-cli
composer install
cp .env.example .env   # optional: local registry defaults
./bin/bladcn --version
```

## Usage

### With Artisan (recommended in Laravel)

```bash
# Full setup: bladcn.json + stubs (CSS, ServiceProvider, boot)
php artisan bladcn:init

# Useful options
php artisan bladcn:init --with-dark-mode --skip-prompts
php artisan bladcn:init --force

php artisan bladcn:list
php artisan bladcn:add accordion
php artisan bladcn:add --all
php artisan bladcn:add button --dry-run
```

### Standalone binary

From your Laravel app root:

```bash
# 1. Create bladcn.json and base assets
bladcn init

# 2. List available components
bladcn list

# 3. Add a component (and its dependencies)
bladcn add accordion

# 4. Add several at once
bladcn add dialog button card

# 5. Component only, no dependencies
bladcn add button --no-deps

# 6. Preview without copying
bladcn add drawer --dry-run

# 7. Install every component in the registry
bladcn add --all

# 8. Overwrite existing files
bladcn add button --overwrite
```

## Configuration (`bladcn.json`)

```json
{
  "$schema": "./vendor/ailuracode/bladcn/resources/bladcn.schema.json",
  "componentsPath": "resources/views/components/ui",
  "registry": "github:SiddharthaGF/Bladcn-demo",
  "registryBranch": "main",
  "resolved": ["accordion", "icon"]
}
```

The default registry points to [Bladcn-demo](https://github.com/SiddharthaGF/Bladcn-demo). You can change it using any of these formats:

```json
{
  "registry": "https://github.com/SiddharthaGF/Bladcn-demo"
}
```

```json
{
  "registry": "https://github.com/other-user/other-registry/tree/develop",
  "registryBranch": "develop"
}
```

### Local registry (development)

```bash
bladcn init --registry ../bladcn-components --force
```

You can also set a relative path in `registry`:

```json
{
  "registry": "../bladcn-components"
}
```

## How dependencies are resolved

Each component folder may include `dependencies.json`:

```json
{
  "dependencies": ["icon"],
  "composer": ["mallardduck/blade-lucide-icons"],
  "npm": []
}
```

When you run `bladcn add accordion`, the CLI:

1. Reads `accordion/dependencies.json`
2. Installs internal dependencies first (`icon`)
3. Runs `composer require` for packages listed in `composer` (if missing)
4. Copies the component folder to `componentsPath`
5. Does not copy `dependencies.json` to the target project
6. Updates `resolved` in `bladcn.json`

Same-group dependencies (internal sub-components) are not listed in `dependencies.json`; only external components are.

## Laravel project requirements

Copied components require the following in the host app (this CLI does not install them):

| Dependency | Purpose |
|---|---|
| `livewire/blaze` | `@blaze` directive |
| `mallardduck/blade-lucide-icons` | `<x-ui.icon>` |
| `app/Bladcn/Support/*` | `ClassResolver`, toast, as-child |
| `resources/css/app.css` | Tailwind 4 + shadcn tokens |
| `resources/js/bladcn.js` | Alpine helpers (`bladcnOnAlpine`, scroll-area, copy button) |
| `resources/js/bladcn/carousel.js` | Embla carousel registration |
| `resources/views/partials/bladcn-boot.blade.php` | Layout hook before `@stack('bladcn-scripts')` |
| `app/Providers/BladcnServiceProvider.php` | `@asChild` directive |

## Commands

| Artisan | Binary | Description |
|---|---|---|
| `bladcn:init` | `bladcn init` | Create `bladcn.json` and publish base stubs |
| `bladcn:list` | `bladcn list` | List registry components |
| `bladcn:add` | `bladcn add` | Install components and dependencies |
| `bladcn:remove` | `bladcn remove` | Remove components and orphan deps |

### `add` options

| Option | Description |
|---|---|
| `--all` | Install every component from the registry |
| `--no-deps` | Skip internal dependencies |
| `--no-external-deps` | Skip automatic `composer require` |
| `--overwrite` | Overwrite existing components |
| `--dry-run` | Preview without copying |

### `remove` options

| Option | Description |
|---|---|
| `--no-orphans` | Do not remove orphan internal dependencies |
| `--yes` | Remove orphans without prompting |
| `--dry-run` | Preview without deleting |

### `init` options

| Option | Description |
|---|---|
| `--with-dark-mode` | CSS theme with `.dark` variables |
| `--css-file=app.css` | Main CSS file to import the theme into |
| `--theme-file=bladcn-theme.css` | Theme file name |
| `--skip-prompts` | Skip interactive prompts |
| `--skip-assets` | Only `bladcn.json`, no stubs |
| `--force` | Overwrite existing files |

## Code quality

Aligned with [laravel-starter-kit](https://github.com/nunomaduro/laravel-starter-kit): Laravel Pint (strict preset), Larastan (max level + bleedingEdge), Rector with `rector-laravel`.

```bash
composer lint         # rector + pint (apply changes)
composer test         # phpunit + lint check + phpstan
composer ci           # alias for test
composer test:unit    # phpunit
composer test:lint    # pint --test + rector --dry-run
composer test:types   # phpstan (Larastan, max level)
composer pint         # format code
composer pint:check   # check format without modifying
composer phpstan      # static analysis
composer rector       # apply refactorings
composer rector:check # suggested refactorings (dry-run)
```

## License

MIT
