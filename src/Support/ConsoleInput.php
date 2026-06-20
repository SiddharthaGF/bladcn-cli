<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ConsoleInput
{
    /** @return list<string> */
    public static function stringListArgument(InputInterface $input, string $name): array
    {
        return Arr::stringList($input->getArgument($name));
    }

    public static function boolOption(InputInterface $input, string $name): bool
    {
        return (bool) $input->getOption($name);
    }

    public static function stringOption(InputInterface $input, string $name, string $default = ''): string
    {
        $value = $input->getOption($name);

        if (! is_string($value) || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function nonEmptyAsk(SymfonyStyle $io, string $question, string $default): string
    {
        $answer = $io->ask($question, $default);

        if (! is_string($answer) || $answer === '') {
            return $default;
        }

        return $answer;
    }
}
