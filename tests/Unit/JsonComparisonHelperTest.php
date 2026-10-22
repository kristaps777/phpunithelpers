<?php

namespace Kristapsv\Phpunithelpers\Tests;

use Kristapsv\Phpunithelpers\JsonComparisonHelper;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\JsonException;
use stdClass;

class JsonComparisonHelperTest extends TestCase
{
    public function testIdenticalArraysHaveNoDifferences(): void
    {
        $data = ['name' => 'John', 'age' => 30];

        $differences = JsonComparisonHelper::differences($data, $data);

        $this->assertEmpty($differences);
    }

    public function testIdenticalObjectsHaveNoDifferences(): void
    {
        $obj1 = new stdClass();
        $obj1->name = 'John';
        $obj1->age = 30;

        $obj2 = new stdClass();
        $obj2->name = 'John';
        $obj2->age = 30;

        $differences = JsonComparisonHelper::differences($obj1, $obj2);

        $this->assertEmpty($differences);
    }

    public function testDetectsMissingKeys(): void
    {
        $expected = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];
        $actual = ['name' => 'John', 'age' => 30];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('Missing key', $differences[0]);
        $this->assertStringContainsString('email', $differences[0]);
    }

    public function testDetectsExtraKeys(): void
    {
        $expected = ['name' => 'John', 'age' => 30];
        $actual = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('Unexpected key', $differences[0]);
        $this->assertStringContainsString('email', $differences[0]);
    }

    public function testDetectsValueMismatches(): void
    {
        $expected = ['name' => 'John', 'age' => 30];
        $actual = ['name' => 'Jane', 'age' => 30];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('Value mismatch', $differences[0]);
        $this->assertStringContainsString('John', $differences[0]);
        $this->assertStringContainsString('Jane', $differences[0]);
    }

    public function testDetectsTypeMismatches(): void
    {
        $expected = ['value' => 123];
        $actual = ['value' => '123'];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('mismatch', $differences[0]);
    }

    public function testHandlesNestedArrays(): void
    {
        $expected = [
            'user' => [
                'name' => 'John',
                'profile' => [
                    'age' => 30,
                    'city' => 'New York'
                ]
            ]
        ];

        $actual = [
            'user' => [
                'name' => 'John',
                'profile' => [
                    'age' => 31,
                    'city' => 'New York'
                ]
            ]
        ];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('root.user.profile.age', $differences[0]);
        $this->assertStringContainsString('30', $differences[0]);
        $this->assertStringContainsString('31', $differences[0]);
    }

    public function testHandlesNestedObjects(): void
    {
        $expected = new stdClass();
        $expected->user = new stdClass();
        $expected->user->name = 'John';
        $expected->user->age = 30;

        $actual = new stdClass();
        $actual->user = new stdClass();
        $actual->user->name = 'John';
        $actual->user->age = 31;

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('root.user.age', $differences[0]);
    }

    public function testHandlesNullValues(): void
    {
        $expected = ['name' => 'John', 'email' => null];
        $actual = ['name' => 'John', 'email' => 'john@example.com'];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('NULL', $differences[0]);
    }

    /**
     * @throws JsonException
     */
    public function testHandlesBooleanValues(): void
    {
        $expected = ['active' => true, 'verified' => false];
        $actual = ['active' => false, 'verified' => false];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('true', $differences[0]);
        $this->assertStringContainsString('false', $differences[0]);
    }

    /**
     * @throws JsonException
     */
    public function testReportsMultipleDifferences(): void
    {
        $expected = [
            'name' => 'John',
            'age' => 30,
            'email' => 'john@example.com'
        ];

        $actual = [
            'name' => 'Jane',
            'age' => 30,
            'phone' => '555-1234'
        ];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        // Should find: name mismatch, missing email, unexpected phone
        $this->assertCount(3, $differences);
    }

    /**
     * @throws JsonException
     */
    public function testAssertJsonMatchesPassesForIdenticalData(): void
    {
        $data = ['name' => 'John', 'age' => 30];

        // Should not throw
        JsonComparisonHelper::assertJsonMatches($data, $data);
    }

    /**
     * @throws JsonException
     */
    public function testAssertJsonMatchesFailsForDifferentData(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('JSON comparison failed');

        $expected = ['name' => 'John'];
        $actual = ['name' => 'Jane'];

        JsonComparisonHelper::assertJsonMatches($expected, $actual);
    }

    /**
     * @throws JsonException
     */
    public function testHandlesArraysWithNumericKeys(): void
    {
        $expected = ['apple', 'banana', 'cherry'];
        $actual = ['apple', 'orange', 'cherry'];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        $this->assertStringContainsString('banana', $differences[0]);
        $this->assertStringContainsString('orange', $differences[0]);
    }

    /**
     * @throws JsonException
     */
    public function testHandlesMixedArrayAndObject(): void
    {
        $expected = ['name' => 'John'];

        $actual = new stdClass();
        $actual->name = 'John';

        $differences = JsonComparisonHelper::differences($expected, $actual);

        // After normalization, these should be identical
        $this->assertEmpty($differences);
    }

    public function testTruncatesLongValuesInErrorMessages(): void
    {
        $longString = str_repeat('a', 200);
        $expected = ['text' => $longString];
        $actual = ['text' => 'short'];

        $differences = JsonComparisonHelper::differences($expected, $actual);

        $this->assertCount(1, $differences);
        // Should contain truncation indicator
        $this->assertStringContainsString('...', $differences[0]);
    }
}