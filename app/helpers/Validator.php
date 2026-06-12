<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Reusable validation rules.
 */
final class Validator
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    public function required(string $field, string $value, string $label): self
    {
        if (trim($value) === '') {
            $this->errors[$field] = sprintf('%s es requerido.', $label);
        }

        return $this;
    }

    public function email(string $field, string $value, string $label): self
    {
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = sprintf('%s no es valido.', $label);
        }

        return $this;
    }

    public function minLength(string $field, string $value, int $length, string $label): self
    {
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->errors[$field] = sprintf('%s debe tener al menos %d caracteres.', $label, $length);
        }

        return $this;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
