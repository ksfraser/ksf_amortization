<?php

namespace Ksfraser\Amortizations\Api;

use Ksfraser\Amortizations\Services\CollectionWorkflowService;
use Ksfraser\Amortizations\Repositories\DelinquencyRepository;

/**
 * HTTP-style controller for the collections workflow.
 *
 * Routes are determined by CollectionsRequest::getAction():
 *   trigger_action        POST  /collections/trigger
 *   process_due           POST  /collections/process-due
 *   create_arrangement    POST  /collections/arrangement
 *   get_status            GET   /collections/status/{loan_id}
 *   get_actions           GET   /collections/actions/{loan_id}
 *   portfolio_statistics  GET   /collections/portfolio
 */
class CollectionsController
{
    /** @var CollectionWorkflowService */
    private $workflowService;

    /** @var DelinquencyRepository */
    private $repository;

    public function __construct(CollectionWorkflowService $workflowService, DelinquencyRepository $repository)
    {
        $this->workflowService = $workflowService;
        $this->repository      = $repository;
    }

    /**
     * Dispatch a CollectionsRequest to the appropriate handler.
     */
    public function handle(CollectionsRequest $request): CollectionsResponse
    {
        switch ($request->getAction()) {
            case 'trigger_action':
                return $this->triggerAction($request);
            case 'process_due':
                return $this->processDue($request);
            case 'create_arrangement':
                return $this->createArrangement($request);
            case 'get_status':
                return $this->getStatus($request);
            case 'get_actions':
                return $this->getActions($request);
            case 'portfolio_statistics':
                return $this->portfolioStatistics();
            default:
                return CollectionsResponse::error('Unknown action: ' . $request->getAction());
        }
    }

    // ------------------------------------------------------------------
    // Handlers
    // ------------------------------------------------------------------

    private function triggerAction(CollectionsRequest $request): CollectionsResponse
    {
        $loanId = $request->getLoanId();
        if ($loanId === null) {
            return CollectionsResponse::error('loan_id is required');
        }

        $result = $this->workflowService->createNextAction($loanId, $request->getAssignedTo());
        return new CollectionsResponse(['action' => $result]);
    }

    private function processDue(CollectionsRequest $request): CollectionsResponse
    {
        $results = $this->workflowService->processDueActions($request->getAssignedTo());
        return new CollectionsResponse([
            'processed' => count($results),
            'results'   => $results,
        ]);
    }

    private function createArrangement(CollectionsRequest $request): CollectionsResponse
    {
        $loanId = $request->getLoanId();
        if ($loanId === null) {
            return CollectionsResponse::error('loan_id is required');
        }

        $terms = $request->getArrangementTerms();
        if (empty($terms)) {
            return CollectionsResponse::error('arrangement_terms are required');
        }

        $result = $this->workflowService->createPaymentArrangement($loanId, $terms);
        return new CollectionsResponse(['arrangement' => $result]);
    }

    private function getStatus(CollectionsRequest $request): CollectionsResponse
    {
        $loanId = $request->getLoanId();
        if ($loanId === null) {
            return CollectionsResponse::error('loan_id is required');
        }

        $status = $this->repository->getDelinquencyStatus($loanId);
        return new CollectionsResponse(['status' => $status]);
    }

    private function getActions(CollectionsRequest $request): CollectionsResponse
    {
        $loanId = $request->getLoanId();
        if ($loanId === null) {
            return CollectionsResponse::error('loan_id is required');
        }

        $actions = $this->repository->getCollectionActions($loanId, $request->getLimit());
        return new CollectionsResponse(['actions' => $actions]);
    }

    private function portfolioStatistics(): CollectionsResponse
    {
        $stats = $this->repository->getPortfolioStatistics();
        return new CollectionsResponse(['statistics' => $stats]);
    }
}
