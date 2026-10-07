<?php

declare(strict_types=1);

namespace Noir\Core;

final class Validator
{
    private array $errors = [];
    private array $validated = [];

    private function __construct(
        private readonly array $data,
        private readonly array $rules
    ) {
        $this->validate();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            if (!empty($messages[0])) {
                return $messages[0];
            }
        }

        return null;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleDefinition) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($ruleDefinition) ? explode('|', $ruleDefinition) : $ruleDefinition;

            $hasRequired = in_array('required', $ruleList, true);

            if (($value === null || $value === '') && !$hasRequired) {
                // Optional field that is empty, skip further checks
                continue;
            }

            foreach ($ruleList as $ruleStr) {
                $ruleStr = trim($ruleStr);
                $params = [];
                if (str_contains($ruleStr, ':')) {
                    [$ruleName, $paramStr] = explode(':', $ruleStr, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $ruleStr;
                }

                $this->applyRule($field, $value, $ruleName, $params);
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, array $params): void
    {
        $humanField = ucwords(str_replace(['_', '-'], ' ', $field));

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "$humanField is required.");
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "$humanField must be a valid string.");
                }
                break;

            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->addError($field, "$humanField must be a valid number.");
                }
                break;

            case 'integer':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "$humanField must be a whole integer.");
                }
                break;

            case 'email':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addError($field, "$humanField must be a valid email address.");
                }
                break;

            case 'min':
                $min = (float)($params[0] ?? 0);
                if (is_numeric($value) && (float)$value < $min) {
                    $this->addError($field, "$humanField must be at least $min.");
                } elseif (is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, "$humanField must have at least $min characters.");
                }
                break;

            case 'max':
                $max = (float)($params[0] ?? 0);
                if (is_numeric($value) && (float)$value > $max) {
                    $this->addError($field, "$humanField cannot exceed $max.");
                } elseif (is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, "$humanField cannot exceed $max characters.");
                }
                break;

            case 'in':
                if ($value !== null && !in_array((string)$value, $params, true)) {
                    $this->addError($field, "The selected $humanField is invalid.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
