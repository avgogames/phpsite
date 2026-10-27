<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidDataHasNoErrors(): void
    {
        $errors = Validator::validate(
            ['email' => 'a@b.co', 'price' => '9.99', 'stock' => '3', 'slug' => 'm8-bolts', 'p' => 'x', 'c' => 'x'],
            ['email' => 'required|email', 'price' => 'money', 'stock' => 'int|min_value:0', 'slug' => 'slug', 'c' => 'same:p'],
        );
        self::assertSame([], $errors);
    }

    public function testReportsFirstFailingRulePerField(): void
    {
        $errors = Validator::validate(
            ['email' => 'nope', 'name' => '', 'stock' => '-1', 'slug' => 'Bad Slug', 'url' => 'javascript:alert(1)'],
            ['email' => 'required|email', 'name' => 'required|max:5', 'stock' => 'int|min_value:0', 'slug' => 'slug', 'url' => 'url'],
        );
        self::assertSame(['email', 'name', 'stock', 'slug', 'url'], array_keys($errors));
        self::assertStringContainsString('required', $errors['name']);
    }

    public function testOptionalEmptyFieldsSkipRules(): void
    {
        self::assertSame([], Validator::validate(['phone' => ''], ['phone' => 'max:3|int']));
    }
}
