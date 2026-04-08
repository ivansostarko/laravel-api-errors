<?php

namespace LaravelApiErrors\Support;

use LaravelApiErrors\Contracts\ApiErrorCode;

class ErrorCodeRegistry
{
    /** @var array<string, ApiErrorCode> */
    protected array $codes = [];

    /** @var array<class-string> */
    protected array $enums = [];

    /**
     * Register an entire backed enum class.
     */
    public function register(string $enumClass): void
    {
        if (in_array($enumClass, $this->enums, true)) {
            return;
        }

        if (! enum_exists($enumClass) || ! is_subclass_of($enumClass, ApiErrorCode::class)) {
            throw new \InvalidArgumentException(
                "[$enumClass] must be a backed enum implementing " . ApiErrorCode::class
            );
        }

        foreach ($enumClass::cases() as $case) {
            $code = $case->code();

            if (isset($this->codes[$code])) {
                $existing = get_class($this->codes[$code]) . '::' . $this->codes[$code]->name;
                $new      = $enumClass . '::' . $case->name;
                throw new \LogicException(
                    "Duplicate API error code [{$code}] registered by [{$existing}] and [{$new}]."
                );
            }

            $this->codes[$code] = $case;
        }

        $this->enums[] = $enumClass;
    }

    /**
     * Resolve a code string back to its enum case.
     */
    public function resolve(string $code): ?ApiErrorCode
    {
        return $this->codes[$code] ?? null;
    }

    /**
     * Get all registered codes.
     *
     * @return array<string, ApiErrorCode>
     */
    public function all(): array
    {
        return $this->codes;
    }

    /**
     * Get all codes for a given domain.
     *
     * @return array<string, ApiErrorCode>
     */
    public function domain(string $domain): array
    {
        return array_filter($this->codes, fn (ApiErrorCode $c) => $c->domain() === $domain);
    }

    /**
     * Return all registered enum class names.
     *
     * @return array<class-string>
     */
    public function enums(): array
    {
        return $this->enums;
    }

    /**
     * Group all codes by domain.
     *
     * @return array<string, array<string, ApiErrorCode>>
     */
    public function groupedByDomain(): array
    {
        $grouped = [];
        foreach ($this->codes as $code => $case) {
            $grouped[$case->domain()][$code] = $case;
        }
        ksort($grouped);

        return $grouped;
    }
}
