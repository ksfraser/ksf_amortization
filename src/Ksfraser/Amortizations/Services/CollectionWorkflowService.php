<?php

namespace Ksfraser\Amortizations\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use Ksfraser\Amortizations\Repositories\DelinquencyRepository;

/**
 * CollectionWorkflowService
 *
 * Orchestrates collection actions and payment arrangements from delinquency
 * classification data already persisted through the delinquency repository.
 */
class CollectionWorkflowService
{
    private DelinquencyRepository $repository;

    public function __construct(DelinquencyRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create the next collection action for a delinquent loan.
     *
     * @return array<string, mixed>
     */
    public function createNextAction(int $loanId, ?string $assignedTo = null): array
    {
        $delinquency = $this->repository->getDelinquencyStatus($loanId);

        if ($delinquency === null) {
            throw new InvalidArgumentException('Delinquency status not found for loan');
        }

        $status = $delinquency['status'] ?? 'CURRENT';
        if ($status === 'CURRENT') {
            return [
                'created' => false,
                'reason' => 'Loan is current',
            ];
        }

        $action = $this->buildActionData($status, $delinquency, $assignedTo);
        $actionId = $this->repository->recordCollectionAction($loanId, $action);

        $updateData = [
            'last_action' => $action['description'],
            'last_action_date' => $action['action_date'],
            'next_action_date' => $action['due_date'],
        ];
        $this->repository->updateDelinquencyStatus($loanId, $updateData);

        $action['id'] = $actionId;
        $action['created'] = true;

        return $action;
    }

    /**
     * Offer a payment arrangement when the delinquency tier supports it.
     *
     * @return array<string, mixed>
     */
    public function createPaymentArrangement(int $loanId, array $terms = []): array
    {
        $delinquency = $this->repository->getDelinquencyStatus($loanId);

        if ($delinquency === null) {
            throw new InvalidArgumentException('Delinquency status not found for loan');
        }

        $status = $delinquency['status'] ?? 'CURRENT';
        if (!in_array($status, ['30_DAYS_PAST_DUE', '60_DAYS_PAST_DUE'], true)) {
            return [
                'created' => false,
                'reason' => 'Payment arrangement not available for this delinquency tier',
            ];
        }

        $startDate = new DateTimeImmutable($terms['start_date'] ?? 'today');
        $durationDays = (int) ($terms['duration_days'] ?? 30);
        $endDate = $startDate->modify(sprintf('+%d days', $durationDays));

        $arrangement = [
            'arrangement_type' => $terms['arrangement_type'] ?? 'payment_plan',
            'status' => 'active',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'modified_payment' => $terms['modified_payment'] ?? null,
            'modified_term' => $terms['modified_term'] ?? null,
            'description' => $terms['description'] ?? 'Temporary payment arrangement created from collections workflow',
            'created_date' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'created_by' => $terms['created_by'] ?? 'system',
        ];

        $arrangementId = $this->repository->savePaymentArrangement($loanId, $arrangement);

        return [
            'created' => true,
            'id' => $arrangementId,
            'arrangement' => $arrangement,
        ];
    }

    /**
     * Process all loans due for action.
     *
     * @return array<int, array<string, mixed>>
     */
    public function processDueActions(?string $assignedTo = null): array
    {
        $loans = $this->repository->getLoansDueForAction();
        $results = [];

        foreach ($loans as $loan) {
            if (!isset($loan['loan_id'])) {
                continue;
            }

            $results[] = $this->createNextAction((int) $loan['loan_id'], $assignedTo);
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $delinquency
     * @return array<string, mixed>
     */
    private function buildActionData(string $status, array $delinquency, ?string $assignedTo): array
    {
        $now = new DateTimeImmutable();
        $daysOverdue = (int) ($delinquency['days_overdue'] ?? 0);

        switch ($status) {
            case '30_DAYS_PAST_DUE':
                $type = 'courtesy_reminder';
                $description = 'Send courtesy reminder and offer payment arrangement';
                $daysUntilDue = 7;
                break;

            case '60_DAYS_PAST_DUE':
                $type = 'direct_contact';
                $description = 'Direct contact required and payment plan review';
                $daysUntilDue = 3;
                break;

            case '90_PLUS_DAYS_PAST_DUE':
                $type = $daysOverdue >= 120 ? 'external_collection_referral' : 'formal_collection_notice';
                $description = $daysOverdue >= 120
                    ? 'Refer delinquent loan to external collection agency'
                    : 'Initiate formal collection notice and attorney review';
                $daysUntilDue = 1;
                break;

            default:
                $type = 'manual_review';
                $description = 'Manual collections review required';
                $daysUntilDue = 5;
                break;
        }

        return [
            'action_type' => $type,
            'description' => $description,
            'action_date' => $now->format('Y-m-d H:i:s'),
            'due_date' => $now->modify(sprintf('+%d days', $daysUntilDue))->format('Y-m-d'),
            'completed_date' => null,
            'result' => 'pending',
            'notes' => sprintf('Auto-generated from delinquency status %s', $status),
            'assigned_to' => $assignedTo ?? 'collections_queue',
            'next_action' => $type,
        ];
    }
}
