<?php

namespace App\Validation;

class Validator
{
    private array $errors = [];

    /**
     * Check that all given fields exist in $data and are non-empty strings.
     */
    public function required(array $data, array $fields): self
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $this->errors[$field] = "{$field} is required";
            }
        }
        return $this;
    }

    public function email(array $data, string $field): self
    {
        if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$field} must be a valid email address";
        }
        return $this;
    }

    public function in(array $data, string $field, array $allowedValues): self
    {
        if (isset($data[$field]) && !in_array($data[$field], $allowedValues, true)) {
            $this->errors[$field] = "{$field} must be one of: " . implode(', ', $allowedValues);
        }
        return $this;
    }

    public function date(array $data, string $field): self
    {
        if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $data[$field]);
            if (!$d || $d->format('Y-m-d') !== $data[$field]) {
                $this->errors[$field] = "{$field} must be a valid date (YYYY-MM-DD)";
            }
        }
        return $this;
    }

    public function minLength(array $data, string $field, int $length): self
    {
        if (isset($data[$field]) && strlen((string) $data[$field]) < $length) {
            $this->errors[$field] = "{$field} must be at least {$length} characters";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}