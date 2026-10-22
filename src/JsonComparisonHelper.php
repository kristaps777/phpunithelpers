<?php

namespace Kristapsv\Phpunithelpers;

use PHPUnit\Framework\Assert;
use Safe\Exceptions\JsonException;
use stdClass;

use function Safe\json_encode;

class JsonComparisonHelper
{
    /**
     * @param array<string, mixed>|stdClass $expected
     * @param array<string, mixed>|stdClass $actual
     * @throws JsonException
     */
    public static function assertJsonMatches(array|stdClass $expected, array|stdClass $actual, string $path = 'root'): void
    {
        $result = self::differences($expected, $actual, $path);

        if (count($result) > 0) {
            $message = "JSON comparison failed:\n\n" . implode("\n", $result);
            Assert::fail($message);
        } else {
            Assert::assertCount(0, $result);
        }
    }

    /**
     * @return array<int, string>
     * @throws JsonException
     */
    public static function differences(mixed $expected, mixed $actual, string $path = 'root'): array
    {
        $differences = [];
        $expected = self::arrayOrScalar($expected);
        $actual = self::arrayOrScalar($actual);

        if (gettype($expected) !== gettype($actual)) {
            $differences[] = sprintf(
                'Type mismatch at \'%s\': expected %s, got %s',
                $path,
                gettype($expected),
                gettype($actual)
            );

            return $differences;
        }

        if (is_array($expected)) {
            $missingKeys = array_diff(array_keys($expected), array_keys($actual));
            $missingDiffs = collect($missingKeys)->map(fn ($key): string => sprintf(
                'Missing key at \'%s.%s\': expected value %s',
                $path,
                $key,
                self::formatted($expected[$key])
            ))->all();

            $extraKeys = array_diff(array_keys($actual), array_keys($expected));
            $extraDiffs = collect($extraKeys)->map(fn ($key): string => sprintf(
                'Unexpected key at \'%s.%s\': got value %s',
                $path,
                $key,
                self::formatted($actual[$key])
            ))->all();

            $commonKeys = array_intersect(array_keys($expected), array_keys($actual));
            $nestedDiffs = collect($commonKeys)->flatMap(function (string $key) use ($expected, $actual, $path): array {
                $newPath = $path . '.' . $key;

                return self::differences($expected[$key], $actual[$key], $newPath);
            })->all();

            $differences = array_merge($differences, $missingDiffs, $extraDiffs, $nestedDiffs);
        } elseif ($expected !== $actual) {
            $differences[] = sprintf(
                'Value mismatch at \'%s\': expected %s, got %s',
                $path,
                self::formatted($expected),
                self::formatted($actual)
            );
        }

        return $differences;
    }

    private static function arrayOrScalar(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($item): mixed => self::arrayOrScalar($item))->all();
        }

        return $value;
    }

    /**
     * @throws JsonException
     */
    private static function formatted(mixed $value, int $maxLength = 100): string
    {
        if (null === $value) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            if (strlen($value) > $maxLength) {
                return '"' . substr($value, 0, $maxLength) . '..."';
            }

            return '"' . $value . '"';
        }

        if (is_array($value) || is_object($value)) {
            $json = json_encode($value);
            if (strlen($json) > $maxLength) {
                return substr($json, 0, $maxLength) . '...';
            }

            return $json;
        }

        return (string) $value;
    }
}
