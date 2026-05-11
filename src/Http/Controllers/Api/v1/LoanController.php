<?php

namespace App\Http\Controllers\Api\v1;

use App\Api\ApiResponse;
use App\Api\Validation\RequestValidator;
use App\Domain\Loan\Services\LoanOriginationService;
use App\Domain\Loan\Services\AmortizationCalculator;
use App\Http\Controllers\ApiController;
use Decimal\Decimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 2: Loan API Controller
 * RESTful endpoints for loan lifecycle management
 */
class LoanController extends ApiController
{
    public function __construct(
        private LoanOriginationService $loanService,
        private AmortizationCalculator $amortizationCalculator
    ) {
        parent::__construct();
        $this->middleware('auth:sanctum');
    }

    /**
     * POST /api/v1/loans
     * Initiate new loan application
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create_loan');

        // Validate input
        $validated = RequestValidator::validateLoanCreation($request->all());

        // Create loan
        $loan = $this->loanService->initiateLoanApplication(
            $validated['borrower_id'],
            new LoanRequest(
                $validated['loan_type'],
                $validated['purpose'] ?? '',
                new Decimal($validated['amount']),
                $validated['term_months'],
                new Decimal($validated['interest_rate'])
            ),
            $this->getUser()->id
        );

        $this->logActivity('loan_created', ['loan_id' => $loan->getId()]);

        return ApiResponse::created([
            'id' => $loan->getId(),
            'loan_number' => $loan->getLoanNumber(),
            'status' => $loan->getStatus()->value,
            'stage' => $loan->getStage()->value,
        ]);
    }

    /**
     * GET /api/v1/loans/{id}
     * Retrieve loan details
     */
    public function show(int $id): JsonResponse
    {
        $loan = $this->loanRepository->findOrFail($id);

        // Authorization: user can only view own loans or staff can view any
        if ($this->getUser()->isBorrower() && $loan->getBorrowerId() !== $this->getUser()->borrower_id) {
            return ApiResponse::forbidden('Cannot view other borrower loans');
        }

        return ApiResponse::success([
            'id' => $loan->getId(),
            'loan_number' => $loan->getLoanNumber(),
            'borrower_id' => $loan->getBorrowerId(),
            'loan_type' => $loan->getLoanType(),
            'original_amount' => (string) $loan->getOriginalAmount(),
            'current_balance' => (string) $loan->getCurrentBalance(),
            'interest_rate' => (string) $loan->getInterestRate(),
            'term_months' => $loan->getTermMonths(),
            'monthly_payment' => (string) $loan->getMonthlyPayment(),
            'status' => $loan->getStatus()->value,
            'stage' => $loan->getStage()->value,
            'next_due_date' => $loan->getNextDueDate()?->format('Y-m-d'),
            'maturity_date' => $loan->getMaturityDate()?->format('Y-m-d'),
            'days_past_due' => $loan->getDaysPastDue(),
            'past_due_amount' => (string) $loan->getPastDueAmount(),
        ]);
    }

    /**
     * POST /api/v1/loans/{id}/submit
     * Submit loan for underwriting
     */
    public function submit(int $id, Request $request): JsonResponse
    {
        $this->authorize('approve_loan');

        $loan = $this->loanRepository->findOrFail($id);

        $loan = $this->loanService->submitForUnderwriting(
            $loan,
            $request->has('origination_fee') ? new Decimal($request->input('origination_fee')) : null,
            $request->has('insurance_amount') ? new Decimal($request->input('insurance_amount')) : null
        );

        $this->logActivity('loan_submitted', ['loan_id' => $loan->getId()]);

        return ApiResponse::success([
            'id' => $loan->getId(),
            'status' => 'submitted for underwriting',
            'stage' => $loan->getStage()->value,
        ]);
    }

    /**
     * POST /api/v1/loans/{id}/approve
     * Approve loan and set pricing
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        $this->authorize('approve_loan');

        $validated = $request->validate([
            'approved_rate' => 'required|numeric|min:0|max:25',
            'credit_score' => 'nullable|integer|min:300|max:850',
            'ltv_ratio' => 'nullable|numeric|min:0|max:100',
        ]);

        $loan = $this->loanRepository->findOrFail($id);

        $loan = $this->loanService->underwriteAndApprove(
            $loan,
            $this->getUser()->id,
            new UnderwritingDecision(
                'approved',
                new Decimal($validated['approved_rate']),
                $validated['credit_score'] ?? 700,
                new Decimal($validated['ltv_ratio'] ?? 80)
            )
        );

        $this->logActivity('loan_approved', ['loan_id' => $loan->getId()]);

        return ApiResponse::success([
            'id' => $loan->getId(),
            'status' => 'approved',
            'monthly_payment' => (string) $loan->getMonthlyPayment(),
        ]);
    }

    /**
     * POST /api/v1/loans/{id}/fund
     * Fund loan and generate amortization schedule
     */
    public function fund(int $id): JsonResponse
    {
        $this->authorize('approve_loan');

        $loan = $this->loanRepository->findOrFail($id);

        $loan = $this->loanService->fundLoan($loan, now());

        $this->logActivity('loan_funded', ['loan_id' => $loan->getId()]);

        return ApiResponse::success([
            'id' => $loan->getId(),
            'status' => 'funded',
            'funded_date' => $loan->getFundingDate()?->format('Y-m-d'),
            'first_payment_date' => $loan->getFirstPaymentDate()?->format('Y-m-d'),
        ]);
    }

    /**
     * GET /api/v1/loans/{id}/schedule
     * Get amortization schedule
     */
    public function getSchedule(int $id): JsonResponse
    {
        $loan = $this->loanRepository->findOrFail($id);

        $schedule = $this->amortizationCalculator->generateSchedule(
            $loan->getOriginalAmount(),
            $loan->getInterestRate(),
            $loan->getTermMonths(),
            $loan->getFirstPaymentDate() ?? now()
        );

        return ApiResponse::paginated(
            $schedule,
            count($schedule),
            10,
            $request->input('page', 1)
        );
    }

    /**
     * GET /api/v1/loans
     * List loans (paginated, filterable)
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->loanRepository->query();

        // Filter by status
        if ($request->has('status')) {
            $query = $query->where('status', $request->input('status'));
        }

        // Filter by stage
        if ($request->has('stage')) {
            $query = $query->where('stage', $request->input('stage'));
        }

        // Borrower can only see own loans
        if ($this->getUser()->isBorrower()) {
            $query = $query->where('borrower_id', $this->getUser()->borrower_id);
        }

        $loans = $query->paginate($request->input('per_page', 15));

        return ApiResponse::paginated(
            $loans->items(),
            $loans->total(),
            $loans->perPage(),
            $loans->currentPage()
        );
    }
}
