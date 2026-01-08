<?php

namespace App\Services\SEO\Schema;

/**
 * Base Schema Builder
 * الفئة الأساسية لبناء الـ Structured Data
 */
abstract class SchemaBuilder
{
    protected array $data = [];

    /**
     * Create new instance
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * Set schema context
     */
    protected function setContext(): self
    {
        $this->data['@context'] = 'https://schema.org';
        return $this;
    }

    /**
     * Set schema type
     */
    protected function setType(string $type): self
    {
        $this->data['@type'] = $type;
        return $this;
    }

    /**
     * Get schema data as array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get schema as JSON
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Render as script tag
     */
    public function toScript(): string
    {
        return '<script type="application/ld+json">' . "\n" . $this->toJson() . "\n" . '</script>';
    }

    /**
     * Build the schema - to be implemented by child classes
     */
    abstract public function build(): self;
}
