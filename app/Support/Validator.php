<?php

namespace App\Support;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array<string,string> $rules  ['email' => 'required|email', 'age' => 'int|min:18']
     */
    public function rules(array $rules): self
    {
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                match ($name) {
                    'required' => $this->checkRequired($field, $value),
                    'string'   => $this->checkString($field, $value),
                    'int'      => $this->checkInt($field, $value),
                    'email'    => $this->checkEmail($field, $value),
                    'min'      => $this->checkMin($field, $value, (int)$param),
                    'max'      => $this->checkMax($field, $value, (int)$param),
                    'in'       => $this->checkIn($field, $value, explode(',', (string)$param)),
                    'bool'     => $this->checkBool($field, $value),
                    'array'    => $this->checkArray($field, $value),
                    default    => null,
                };
            }
        }
        return $this;
    }

    public function validate(): array
    {
        if ($this->errors !== []) {
            throw HttpException::validation($this->errors);
        }
        return $this->data;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    // --- Правила ---

    private function checkRequired(string $f, mixed $v): void
    {
        if ($v === null || $v === '' || $v === []) {
            $this->addErrors($f, 'required');
        }
    }

    private function checkString(string $f, mixed $v): void
    {
        if ($v !== null && !is_string($v)) {
            $this->addErrors($f, 'must be a string');
        }
    }

    private function checkInt(string $f, mixed $v): void
    {
        if ($v !== null && filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->addErrors($f, 'must be an integer');
        }
    }

    private function checkEmail(string $f, mixed $v): void
    {
        if ($v !== null && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->addErrors($f, 'must be a valid email');
        }
    }

    private function checkMin(string $f, mixed $v, int $min): void
    {
        if (is_string($v) && mb_strlen($v) < $min) {
            $this->addErrors($f, "must be at least $min characters");
        } elseif (is_numeric($v) && $v < $min) {
            $this->addErrors($f, "must be at least $min");
        }
    }

    private function checkMax(string $f, mixed $v, int $max): void
    {
        if (is_string($v) && mb_strlen($v) > $max) {
            $this->addErrors($f, "must be at most $max characters");
        } elseif (is_numeric($v) && $v > $max) {
            $this->addErrors($f, "must be at most $max");
        }
    }

    private function checkIn(string $f, mixed $v, array $allowed): void
    {
        if ($v !== null && !in_array((string)$v, $allowed, true)) {
            $this->addErrors($f, 'must be one of: ' . implode(', ', $allowed));
        }
    }

    private function checkBool(string $f, mixed $v): void
    {
        if ($v !== null && !in_array($v, [true, false, 0, 1, '0', '1'], true)) {
            $this->addErrors($f, 'must be boolean');
        }
    }

    private function checkArray(string $f, mixed $v): void
    {
        if ($v !== null && !is_array($v)) {
            $this->addErrors($f, 'must be an array');
        }
    }

    private function addErrors(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}