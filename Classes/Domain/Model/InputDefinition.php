<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Moselwal Digitalagentur <info@moselwal.de>
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace Koh\AiWorkflows\Domain\Model;

final readonly class InputDefinition
{
    /**
     * @param list<string> $options
     */
    public function __construct(
        public string $name,
        public string $type,
        public string $label,
        public string $description = '',
        public bool $required = false,
        public mixed $default = null,
        public string $placeholder = '',
        public array $options = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $name, array $data): self
    {
        return new self(
            name: $name,
            type: (string) ($data['type'] ?? 'string'),
            label: (string) ($data['label'] ?? $name),
            description: (string) ($data['description'] ?? ''),
            required: (bool) ($data['required'] ?? false),
            default: $data['default'] ?? null,
            placeholder: (string) ($data['placeholder'] ?? ''),
            options: self::normalizeOptions($data['options'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'required' => $this->required,
            'default' => $this->default,
            'placeholder' => $this->placeholder,
            'options' => $this->options,
        ];
    }

    /**
     * @return list<string>
     */
    private static function normalizeOptions(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, 'is_string'));
    }
}
