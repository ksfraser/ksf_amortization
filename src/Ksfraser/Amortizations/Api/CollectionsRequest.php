<?php

namespace Ksfraser\Amortizations\Api;

/**
 * Request value object for collections API endpoints.
 *
 * Supported actions (set via $data['action']):
 *   trigger_action        - manually trigger next collection action for a loan
 *   process_due           - process all loans whose next_action_date is today or earlier
 *   create_arrangement    - creates a payment arrangement for a loan
 *   get_status            - retrieve delinquency status for a loan
 *   get_actions           - retrieve collection action history for a loan
 *   portfolio_statistics  - get portfolio-level delinquency statistics
 */
class CollectionsRequest
{
    /** @var array */
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getAction(): string
    {
        return (string) ($this->data['action'] ?? '');
    }

    public function getLoanId(): ?int
    {
        return isset($this->data['loan_id']) ? (int) $this->data['loan_id'] : null;
    }

    public function getAssignedTo(): ?string
    {
        return isset($this->data['assigned_to']) ? (string) $this->data['assigned_to'] : null;
    }

    public function getArrangementTerms(): array
    {
        return $this->data['arrangement_terms'] ?? [];
    }

    public function getLimit(): ?int
    {
        return isset($this->data['limit']) ? (int) $this->data['limit'] : null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
