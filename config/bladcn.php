<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Component registry
    |--------------------------------------------------------------------------
    |
    | Local path, github:owner/repo, package:ailuracode/bladcn, or a GitHub URL.
    | Default: sibling ../bladcn-components (CSS, JS, Blade components).
    |
    */

    'registry' => env('BLADCN_REGISTRY', '../bladcn-components'),

    'registry_branch' => env('BLADCN_REGISTRY_BRANCH', 'main'),

    /*
    |--------------------------------------------------------------------------
    | Install paths
    |--------------------------------------------------------------------------
    */

    'components_path' => env('BLADCN_COMPONENTS_PATH', 'resources/views/components/ui'),

    'css_file' => env('BLADCN_CSS_FILE', 'app.css'),

    'theme_file' => env('BLADCN_THEME_FILE', 'bladcn-theme.css'),

];
