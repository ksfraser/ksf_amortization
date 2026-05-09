<?php

namespace Ksfraser\Amortizations\Api;

/**
 * Response value object for collections API endpoints.
 */
class CollectionsResponse
{
    /** @var array */
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = array_merge(['success' => true, 'errors' => []], $data);
    }

    public function isSuccess(): bool
    {
        return (bool) $this->data['success'];
    }

    public function getErrors(): array
    {
        return (array) ($this->data['errors'] ?? []);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public static function error(string $message): self
    {
        return new self(['success' => false, 'errors' => [$message]]);
    }
}
