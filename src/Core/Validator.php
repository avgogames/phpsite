<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small rule-based validator. Rules: required, email, min:N, max:N, int, min_value:N, money, slug, same:field, url, in:a,b.
 *
 * Usage: $errors = Validator::validate($data, ['email' => 'required|email|max:255']);
 */
final class Validator
{
    /** @return array<string, string> field => first error message */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? '';
            $value = is_string($value) ? trim($value) : $value;
            $label = ucfirst(str_replace('_', ' ', $field));

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                if ($name !== 'required' && ($value === '' || $value === null)) {
                    continue; // optional and empty: skip the remaining rules
                }

                $error = match ($name) {
                    'required' => ($value === '' || $value === null) ? "{$label} is required." : null,
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "{$label} must be a valid email address.",
                    'min' => mb_strlen((string) $value) < (int) $arg ? "{$label} must be at least {$arg} characters." : null,
                    'max' => mb_strlen((string) $value) > (int) $arg ? "{$label} must be at most {$arg} characters." : null,
                    'int' => filter_var($value, FILTER_VALIDATE_INT) === false ? "{$label} must be a whole number." : null,
                    'min_value' => (int) $value < (int) $arg ? "{$label} must be at least {$arg}." : null,
                    'money' => preg_match('/^\d{1,7}(\.\d{1,2})?$/', (string) $value) ? null : "{$label} must be a price like 12.99.",
                    'slug' => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value) ? null : "{$label} may contain only lowercase letters, numbers and dashes.",
                    'same' => $value !== ($data[$arg] ?? null) ? "{$label} does not match." : null,
                    'url' => filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', (string) $value) ? null : "{$label} must be a valid http(s) URL.",
                    'in' => in_array($value, explode(',', (string) $arg), true) ? null : "{$label} is not a valid choice.",
                    default => throw new \InvalidArgumentException("Unknown validation rule: {$name}"),
                };

                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }
}
