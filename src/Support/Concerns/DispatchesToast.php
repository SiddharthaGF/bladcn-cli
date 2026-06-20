<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support\Concerns;

use AiluraCode\Bladcn\Support\Toast;

trait DispatchesToast
{
    /**
     * @param  array{
     *     description?: string,
     *     variant?: 'default'|'success'|'info'|'warning'|'destructive'|'loading',
     *     position?: string,
     *     duration?: int,
     * }  $options
     */
    protected function toast(string $title, array $options = []): void
    {
        $this->dispatch(Toast::LIVEWIRE_EVENT, ...Toast::make($title, $options));
    }

    /**
     * @param  array{
     *     description?: string,
     *     position?: string,
     *     duration?: int,
     * }  $options
     */
    protected function toastSuccess(string $title, array $options = []): void
    {
        $this->toast($title, [...$options, 'variant' => 'success']);
    }

    /**
     * @param  array{
     *     description?: string,
     *     position?: string,
     *     duration?: int,
     * }  $options
     */
    protected function toastInfo(string $title, array $options = []): void
    {
        $this->toast($title, [...$options, 'variant' => 'info']);
    }

    /**
     * @param  array{
     *     description?: string,
     *     position?: string,
     *     duration?: int,
     * }  $options
     */
    protected function toastWarning(string $title, array $options = []): void
    {
        $this->toast($title, [...$options, 'variant' => 'warning']);
    }

    /**
     * @param  array{
     *     description?: string,
     *     position?: string,
     *     duration?: int,
     * }  $options
     */
    protected function toastError(string $title, array $options = []): void
    {
        $this->toast($title, [...$options, 'variant' => 'destructive']);
    }
}
