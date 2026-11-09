<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data, $rules);
        $validator->validate();
        return $validator;
    }

    public function validate(): bool
    {
        $this->errors = [];
        $db = Database::getInstance();

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            $isRequired = in_array('required', $fieldRules, true);
            if (!$isRequired && ($value === null || $value === '')) {
                continue; // Skip validation if field is optional and empty
            }

            foreach ($fieldRules as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || trim((string)$value) === '') {
                            $this->addError($field, "Kolom {$field} wajib diisi.");
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "Format email {$field} tidak valid.");
                        }
                        break;

                    case 'numeric':
                        if (!is_numeric($value)) {
                            $this->addError($field, "Kolom {$field} harus berupa angka.");
                        }
                        break;

                    case 'integer':
                        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->addError($field, "Kolom {$field} harus berupa bilangan bulat.");
                        }
                        break;

                    case 'min':
                        $min = (float)$params[0];
                        if (is_numeric($value) && (float)$value < $min) {
                            $this->addError($field, "Nilai {$field} minimal adalah {$min}.");
                        } elseif (is_string($value) && mb_strlen($value) < (int)$min) {
                            $this->addError($field, "Panjang {$field} minimal {$min} karakter.");
                        }
                        break;

                    case 'max':
                        $max = (float)$params[0];
                        if (is_numeric($value) && (float)$value > $max) {
                            $this->addError($field, "Nilai {$field} maksimal adalah {$max}.");
                        } elseif (is_string($value) && mb_strlen($value) > (int)$max) {
                            $this->addError($field, "Panjang {$field} maksimal {$max} karakter.");
                        }
                        break;

                    case 'in':
                        if (!in_array((string)$value, $params, true)) {
                            $allowed = implode(', ', $params);
                            $this->addError($field, "Pilihan {$field} tidak valid (hanya: {$allowed}).");
                        }
                        break;

                    case 'unique':
                        $table = $params[0] ?? '';
                        $column = $params[1] ?? $field;
                        $ignoreId = $params[2] ?? null;

                        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
                        $queryParams = [':val' => $value];
                        if ($ignoreId !== null && $ignoreId !== '') {
                            $sql .= " AND id != :ignore_id";
                            $queryParams[':ignore_id'] = $ignoreId;
                        }

                        $count = (int)$db->fetchColumn($sql, $queryParams);
                        if ($count > 0) {
                            $this->addError($field, "{$field} sudah digunakan, silakan pilih yang lain.");
                        }
                        break;

                    case 'confirmed':
                        $confirmField = $field . '_confirmation';
                        if (!isset($this->data[$confirmField]) || $value !== $this->data[$confirmField]) {
                            $this->addError($field, "Konfirmasi {$field} tidak cocok.");
                        }
                        break;

                    case 'date':
                        if (!strtotime((string)$value)) {
                            $this->addError($field, "Format tanggal {$field} tidak valid.");
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(?string $field = null): ?string
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        foreach ($this->errors as $errList) {
            if (!empty($errList[0])) {
                return $errList[0];
            }
        }
        return null;
    }
}
