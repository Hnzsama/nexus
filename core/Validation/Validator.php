<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Database\Connection;
use PDO;

/**
 * Class Validator
 * 
 * Engine validasi data yang tangguh dan fleksibel untuk Nexus MVC.
 * Mendukung berbagai macam aturan (required, email, min, max, unique, exists, numeric, confirmed, regex, dll.)
 * serta pesan error kustom dan label atribut lokal.
 */
class Validator
{
    /**
     * Data yang sedang divalidasi.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Kumpulan aturan validasi per field.
     *
     * @var array<string, array<int, string>>
     */
    protected array $rules = [];

    /**
     * Kumpulan pesan error yang dihasilkan.
     *
     * @var array<string, array<int, string>>
     */
    protected array $errors = [];

    /**
     * Data yang telah divalidasi dan lolos semua aturan.
     *
     * @var array<string, mixed>
     */
    protected array $validatedData = [];

    /**
     * Pesan kustom dari pengguna.
     *
     * @var array<string, string>
     */
    protected array $customMessages = [];

    /**
     * Nama label kustom untuk atribut.
     *
     * @var array<string, string>
     */
    protected array $customAttributes = [];

    /**
     * Pesan default untuk tiap aturan.
     *
     * @var array<string, string>
     */
    protected array $defaultMessages = [
        'required' => ':attribute wajib diisi.',
        'nullable' => '',
        'email' => ':attribute harus berupa alamat email yang valid.',
        'min' => ':attribute minimal terdiri dari :min karakter.',
        'min.numeric' => ':attribute minimal bernilai :min.',
        'max' => ':attribute maksimal terdiri dari :max karakter.',
        'max.numeric' => ':attribute maksimal bernilai :max.',
        'numeric' => ':attribute harus berupa angka.',
        'integer' => ':attribute harus berupa bilangan bulat.',
        'string' => ':attribute harus berupa teks string.',
        'alpha' => ':attribute hanya boleh berisi huruf.',
        'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
        'confirmed' => 'Konfirmasi :attribute tidak cocok.',
        'same' => ':attribute harus sama dengan :other.',
        'different' => ':attribute harus berbeda dengan :other.',
        'in' => ':attribute yang dipilih tidak valid.',
        'not_in' => ':attribute yang dipilih tidak valid.',
        'unique' => ':attribute sudah terdaftar di sistem.',
        'exists' => ':attribute yang dipilih tidak ditemukan dalam database.',
        'url' => ':attribute harus berupa URL yang valid.',
        'regex' => 'Format :attribute tidak valid.',
    ];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @param array<string, string> $customMessages
     * @param array<string, string> $customAttributes
     */
    public function __construct(
        array $data,
        array $rules,
        array $customMessages = [],
        array $customAttributes = []
    ) {
        $this->data = $data;
        $this->customMessages = $customMessages;
        $this->customAttributes = $customAttributes;
        $this->setRules($rules);
        $this->validate();
    }

    /**
     * Factory method untuk membuat instance baru secara chainable.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @param array<string, string> $customMessages
     * @param array<string, string> $customAttributes
     * @return static
     */
    public static function make(
        array $data,
        array $rules,
        array $customMessages = [],
        array $customAttributes = []
    ): static {
        return new static($data, $rules, $customMessages, $customAttributes);
    }

    /**
     * Normalisasi format aturan validasi.
     *
     * @param array<string, string|array<int, string>> $rules
     */
    protected function setRules(array $rules): void
    {
        $this->rules = [];

        foreach ($rules as $field => $ruleSet) {
            if (is_string($ruleSet)) {
                $this->rules[$field] = explode('|', $ruleSet);
            } else {
                $this->rules[$field] = (array) $ruleSet;
            }
        }
    }

    /**
     * Eksekusi seluruh aturan validasi.
     */
    protected function validate(): void
    {
        $this->errors = [];
        $this->validatedData = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->getValue($field);
            $isNullable = in_array('nullable', $fieldRules, true);
            $isEmpty = $value === null || $value === '' || (is_array($value) && empty($value));

            // Jika bernilai kosong dan field bersifat nullable, abaikan aturan lainnya
            if ($isNullable && $isEmpty) {
                $this->validatedData[$field] = null;
                continue;
            }

            $fieldHasError = false;

            foreach ($fieldRules as $ruleString) {
                $ruleString = trim($ruleString);
                if ($ruleString === '' || $ruleString === 'nullable') {
                    continue;
                }

                [$ruleName, $parameters] = $this->parseRule($ruleString);

                $passed = $this->validateRule($field, $value, $ruleName, $parameters, $fieldRules);

                if (!$passed) {
                    $this->addError($field, $ruleName, $parameters);
                    $fieldHasError = true;
                }
            }

            if (!$fieldHasError) {
                $this->validatedData[$field] = $value;
            }
        }
    }

    /**
     * Parsing nama aturan dan parameternya (misal: 'min:6' -> ['min', ['6']]).
     *
     * @return array{0: string, 1: array<int, string>}
     */
    protected function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
            return [strtolower(trim($name)), array_map('trim', $params)];
        }

        return [strtolower(trim($rule)), []];
    }

    /**
     * Evaluasi satu aturan validasi.
     *
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @param array<int, string> $params
     * @param array<int, string> $allFieldRules
     * @return bool
     */
    protected function validateRule(string $field, mixed $value, string $rule, array $params, array $allFieldRules): bool
    {
        $isEmpty = $value === null || $value === '' || (is_array($value) && empty($value));

        // Jika aturan bukan 'required', dan nilainya kosong, anggap lolos
        if ($rule !== 'required' && $isEmpty) {
            return true;
        }

        return match ($rule) {
            'required' => !$isEmpty,
            'email' => is_string($value) && (bool) filter_var($value, FILTER_VALIDATE_EMAIL),
            'min' => $this->validateMin($value, (int) ($params[0] ?? 0), $allFieldRules),
            'max' => $this->validateMax($value, (int) ($params[0] ?? 0), $allFieldRules),
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'string' => is_string($value),
            'alpha' => is_string($value) && preg_match('/^[a-zA-Z]+$/u', $value) === 1,
            'alpha_num', 'alphanumeric' => is_string($value) && preg_match('/^[a-zA-Z0-9]+$/u', $value) === 1,
            'confirmed' => $this->validateConfirmed($field, $value),
            'same' => $this->validateSame($value, $params[0] ?? ''),
            'different' => $this->validateDifferent($value, $params[0] ?? ''),
            'in' => in_array((string) $value, $params, true),
            'not_in' => !in_array((string) $value, $params, true),
            'unique' => $this->validateUnique($value, $params),
            'exists' => $this->validateExists($value, $params),
            'url' => is_string($value) && (bool) filter_var($value, FILTER_VALIDATE_URL),
            'regex' => is_string($value) && (bool) @preg_match($params[0] ?? '//', $value),
            default => true,
        };
    }

    /**
     * Validasi batas minimum (string length, numeric value, atau array count).
     */
    protected function validateMin(mixed $value, int $min, array $fieldRules): bool
    {
        $isNumeric = in_array('numeric', $fieldRules, true) || in_array('integer', $fieldRules, true);

        if ($isNumeric && is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return mb_strlen((string) $value) >= $min;
    }

    /**
     * Validasi batas maksimum (string length, numeric value, atau array count).
     */
    protected function validateMax(mixed $value, int $max, array $fieldRules): bool
    {
        $isNumeric = in_array('numeric', $fieldRules, true) || in_array('integer', $fieldRules, true);

        if ($isNumeric && is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return mb_strlen((string) $value) <= $max;
    }

    /**
     * Validasi konfirmasi (misal: password dan password_confirmation).
     */
    protected function validateConfirmed(string $field, mixed $value): bool
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);

        return $value === $confirmationValue;
    }

    /**
     * Validasi kesamaan dengan field lain.
     */
    protected function validateSame(mixed $value, string $otherField): bool
    {
        return $value === $this->getValue($otherField);
    }

    /**
     * Validasi perbedaan dengan field lain.
     */
    protected function validateDifferent(mixed $value, string $otherField): bool
    {
        return $value !== $this->getValue($otherField);
    }

    /**
     * Validasi keunikan data di database: unique:table,column[,ignore_id,id_column].
     *
     * @param mixed $value
     * @param array<int, string> $params
     * @return bool
     */
    protected function validateUnique(mixed $value, array $params): bool
    {
        if (empty($params[0]) || empty($params[1])) {
            return true;
        }

        $table = $params[0];
        $column = $params[1];
        $ignoreId = $params[2] ?? null;
        $idColumn = $params[3] ?? 'id';

        try {
            $pdo = Connection::getPdo();
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $bindings = [$value];

            if ($ignoreId !== null && $ignoreId !== '') {
                $sql .= " AND {$idColumn} != ?";
                $bindings[] = $ignoreId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            return ((int) $stmt->fetchColumn()) === 0;
        } catch (\Throwable) {
            // Jika tabel belum ada atau koneksi gagal, jangan blokir
            return true;
        }
    }

    /**
     * Validasi keberadaan data di database: exists:table,column.
     *
     * @param mixed $value
     * @param array<int, string> $params
     * @return bool
     */
    protected function validateExists(mixed $value, array $params): bool
    {
        if (empty($params[0]) || empty($params[1])) {
            return true;
        }

        $table = $params[0];
        $column = $params[1];

        try {
            $pdo = Connection::getPdo();
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$value]);

            return ((int) $stmt->fetchColumn()) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Tambahkan pesan error untuk field dan rule tertentu.
     *
     * @param string $field
     * @param string $rule
     * @param array<int, string> $params
     */
    protected function addError(string $field, string $rule, array $params): void
    {
        $attributeName = $this->getAttributeName($field);

        // Cari custom message spesifik field.rule, misal: 'email.unique'
        $key = "{$field}.{$rule}";
        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } else {
            $messageKey = $rule;
            if (in_array($rule, ['min', 'max'], true) && in_array('numeric', $this->rules[$field] ?? [], true)) {
                $messageKey .= '.numeric';
            }
            $message = $this->defaultMessages[$messageKey] ?? $this->defaultMessages[$rule] ?? ":attribute tidak valid.";
        }

        // Ganti placeholder :attribute
        $message = str_replace(':attribute', $attributeName, $message);

        // Ganti placeholder parameter seperti :min, :max, :other
        if ($rule === 'min' && isset($params[0])) {
            $message = str_replace(':min', $params[0], $message);
        } elseif ($rule === 'max' && isset($params[0])) {
            $message = str_replace(':max', $params[0], $message);
        } elseif (in_array($rule, ['same', 'different'], true) && isset($params[0])) {
            $otherLabel = $this->getAttributeName($params[0]);
            $message = str_replace(':other', $otherLabel, $message);
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Dapatkan nama label atribut yang ramah pengguna.
     */
    protected function getAttributeName(string $field): string
    {
        if (isset($this->customAttributes[$field])) {
            return $this->customAttributes[$field];
        }

        // Format nama field menjadi judul bersih (misal: 'email_address' -> 'Email Address')
        return ucwords(str_replace(['_', '-'], ' ', $field));
    }

    /**
     * Ambil nilai dari input data.
     */
    protected function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /**
     * Cek apakah validasi gagal.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Cek apakah validasi sukses.
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Dapatkan seluruh error yang terkumpul.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Dapatkan pesan error pertama untuk field tertentu atau global.
     */
    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors[0])) {
                return $fieldErrors[0];
            }
        }

        return null;
    }

    /**
     * Dapatkan data yang telah berhasil divalidasi.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validatedData;
    }
}
