<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Support;

use AiluraCode\Bladcn\Support\ClassResolver;
use AiluraCode\Bladcn\Tests\BladcnTestCase;

final class ClassResolverTest extends BladcnTestCase
{
    public function test_add_builds_class_string(): void
    {
        $classes = (new ClassResolver())
            ->add('px-2')
            ->add('py-1')
            ->add(null);

        $this->assertSame('px-2 py-1', (string) $classes);
    }

    public function test_as_child_escapes_class_attribute(): void
    {
        $resolver = new ClassResolver;

        $this->assertSame(
            'foo&quot; bar',
            $resolver->asChild(['class' => 'foo" bar']),
        );
    }
}
