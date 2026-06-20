<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Support;

use AiluraCode\Bladcn\Support\EnvFile;
use AiluraCode\Bladcn\Tests\BladcnTestCase;

final class EnvFileTest extends BladcnTestCase
{
    public function test_load_sets_variables_from_file(): void
    {
        $path = $this->tempDir().'/test.env';
        file_put_contents($path, "BLADCN_REGISTRY=../local-registry\n# comment\nBLADCN_CSS_FILE=custom.css\n");

        EnvFile::load($path);

        $this->assertSame('../local-registry', getenv('BLADCN_REGISTRY'));
        $this->assertSame('custom.css', getenv('BLADCN_CSS_FILE'));

        putenv('BLADCN_REGISTRY');
        putenv('BLADCN_CSS_FILE');
    }

    public function test_load_does_not_override_existing_variables(): void
    {
        putenv('BLADCN_REGISTRY=existing');

        $path = $this->tempDir().'/test.env';
        file_put_contents($path, 'BLADCN_REGISTRY=from-file');

        EnvFile::load($path);

        $this->assertSame('existing', getenv('BLADCN_REGISTRY'));

        putenv('BLADCN_REGISTRY');
    }
}
