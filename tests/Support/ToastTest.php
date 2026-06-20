<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Support;

use AiluraCode\Bladcn\Support\Toast;
use Illuminate\Support\Facades\Session;
use Orchestra\Testbench\TestCase;

final class ToastTest extends TestCase
{
    public function test_from_session_returns_payload(): void
    {
        Toast::flash('Saved', ['variant' => 'success']);

        $this->assertSame([
            'title' => 'Saved',
            'variant' => 'success',
        ], Toast::fromSession());
    }

    public function test_from_session_returns_null_when_missing(): void
    {
        $this->assertNull(Toast::fromSession());
    }

    public function test_from_session_returns_null_for_non_array_value(): void
    {
        Session::put(Toast::SESSION_KEY, 'invalid');

        $this->assertNull(Toast::fromSession());
    }
}
