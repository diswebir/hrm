<?php
/**
 * اعتبارسنجی ورودی‌ها
 */
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    /** قانون: required|email|numeric|min:3|max:50|in:a,b|date|jalali|regex:/x/ */
    public function check(string $field, string $rules, ?string $label = null): self
    {
        $label = $label ?? $field;
        $value = $this->data[$field] ?? null;
        $isEmpty = $value === null || $value === '';

        foreach (explode('|', $rules) as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

            if ($name !== 'required' && $isEmpty) continue;

            switch ($name) {
                case 'required':
                    if ($isEmpty) {
                        $this->errors[$field][] = "«{$label}» الزامی است.";
                    }
                    break;
                case 'email':
                    if (!filter_var((string)$value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field][] = "«{$label}» باید ایمیل معتبر باشد.";
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        $this->errors[$field][] = "«{$label}» باید عدد باشد.";
                    }
                    break;
                case 'integer':
                    if (!preg_match('/^-?\d+$/', (string)$value)) {
                        $this->errors[$field][] = "«{$label}» باید عدد صحیح باشد.";
                    }
                    break;
                case 'min':
                    if (is_numeric($value) && !is_string($this->data[$field] ?? null)) {
                        if ((float)$value < (float)$arg) {
                            $this->errors[$field][] = "«{$label}» نباید کمتر از {$arg} باشد.";
                        }
                    } elseif (mb_strlen((string)$value) < (int)$arg) {
                        $this->errors[$field][] = "«{$label}» باید حداقل {$arg} نویسه باشد.";
                    }
                    break;
                case 'max':
                    if (is_numeric($value) && !is_string($this->data[$field] ?? null)) {
                        if ((float)$value > (float)$arg) {
                            $this->errors[$field][] = "«{$label}» نباید بیشتر از {$arg} باشد.";
                        }
                    } elseif (mb_strlen((string)$value) > (int)$arg) {
                        $this->errors[$field][] = "«{$label}» حداکثر {$arg} نویسه مجاز است.";
                    }
                    break;
                case 'in':
                    $allowed = explode(',', (string)$arg);
                    if (!in_array((string)$value, $allowed, true)) {
                        $this->errors[$field][] = "«{$label}» مقدار مجاز ندارد.";
                    }
                    break;
                case 'date':
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value) || !strtotime((string)$value)) {
                        $this->errors[$field][] = "«{$label}» باید تاریخ معتبر باشد (مثال: 1403/01/15).";
                    }
                    break;
                case 'jalali':
                    if (!\App\Core\Jalali::validJalali((string)$value)) {
                        $this->errors[$field][] = "«{$label}» تاریخ شمسی معتبر نیست.";
                    }
                    break;
                case 'matches':
                    if ((string)$value !== (string)($this->data[$arg] ?? '')) {
                        $this->errors[$field][] = "«{$label}» با مقدار مقابل یکسان نیست.";
                    }
                    break;
                case 'regex':
                    if (!preg_match($arg, (string)$value)) {
                        $this->errors[$field][] = "«{$label}» فرمت نامعتبر دارد.";
                    }
                    break;
                case 'unique':
                    // unique:table,column[,id]
                    $parts = explode(',', (string)$arg);
                    $table = $parts[0];
                    $col = $parts[1] ?? 'id';
                    $excludeId = isset($parts[2]) ? (int)$parts[2] : 0;
                    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$col} = ?";
                    $params = [$value];
                    if ($excludeId) {
                        $sql .= ' AND id != ?';
                        $params[] = $excludeId;
                    }
                    if ((int)DB::val($sql, $params) > 0) {
                        $this->errors[$field][] = "«{$label}» تکراری است.";
                    }
                    break;
            }
        }
        return $this;
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

    public function firstErrors(): array
    {
        $out = [];
        foreach ($this->errors as $f => $list) {
            $out[$f] = $list[0];
        }
        return $out;
    }

    public function allMessages(): string
    {
        $all = [];
        foreach ($this->errors as $list) {
            foreach ($list as $m) $all[] = $m;
        }
        return implode(' ', $all);
    }
}
