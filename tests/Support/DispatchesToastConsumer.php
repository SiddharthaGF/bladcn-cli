<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Support;

use AiluraCode\Bladcn\Support\Concerns\DispatchesToast;

/** @internal */
final class DispatchesToastConsumer
{
    use DispatchesToast;

    public function dispatch(): void {}
}
