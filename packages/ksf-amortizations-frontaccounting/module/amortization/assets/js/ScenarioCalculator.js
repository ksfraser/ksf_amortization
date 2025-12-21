/**
 * ScenarioCalculator
 * 
 * Responsible for real-time calculation previews as user inputs values.
 * Provides visual feedback on scenario impacts without server calls.
 * 
 * @class
 */
class ScenarioCalculator {
    constructor(options = {}) {
        this.monthlyPayment = options.monthlyPayment || 0;
        this.calculationInputs = options.calculationInputs || {
            extraMonthly: '#extraMonthly',
            lumpSumAmount: '#lumpSumAmount',
            lumpSumMonth: '#lumpSumMonth',
            skipPeriod: '#skipPeriod'
        };
        this.previewElements = options.previewElements || {
            newPaymentExtra: '#newPaymentExtra',
            estimatedSavingsExtra: '#estimatedSavingsExtra',
            lumpSumPreview: '#lumpSumPreview',
            lumpSumPeriodPreview: '#lumpSumPeriodPreview',
            estimatedMonthsSaved: '#estimatedMonthsSaved',
            skipPeriodPreview: '#skipPeriodPreview',
            additionalInterestSkip: '#additionalInterestSkip',
            extendedPayoffSkip: '#extendedPayoffSkip',
            targetPayoffPreview: '#targetPayoffPreview',
            requiredPaymentAccel: '#requiredPaymentAccel'
        };
        
        this.init();
    }

    /**
     * Initialize calculation event listeners
     */
    init() {
        this.attachExtraMonthlyListeners();
        this.attachLumpSumListeners();
        this.attachSkipPaymentListeners();
    }

    /**
     * Attach listeners for extra monthly payment calculations
     */
    attachExtraMonthlyListeners() {
        const input = document.querySelector(this.calculationInputs.extraMonthly);
        if (input) {
            input.addEventListener('input', (e) => this.updateExtraMonthlyPreview(e));
        }
    }

    /**
     * Update extra monthly payment preview
     * @param {Event} event
     */
    updateExtraMonthlyPreview(event) {
        const extra = parseFloat(event.target.value) || 0;
        const newPayment = this.monthlyPayment + extra;
        
        const previewElement = document.querySelector(this.previewElements.newPaymentExtra);
        if (previewElement) {
            previewElement.textContent = newPayment.toFixed(2);
        }
    }

    /**
     * Attach listeners for lump sum payment calculations
     */
    attachLumpSumListeners() {
        const amountInput = document.querySelector(this.calculationInputs.lumpSumAmount);
        if (amountInput) {
            amountInput.addEventListener('input', (e) => this.updateLumpSumPreview(e));
        }

        const monthInput = document.querySelector(this.calculationInputs.lumpSumMonth);
        if (monthInput) {
            monthInput.addEventListener('input', (e) => this.updateLumpSumMonthPreview(e));
        }
    }

    /**
     * Update lump sum amount preview
     * @param {Event} event
     */
    updateLumpSumPreview(event) {
        const previewElement = document.querySelector(this.previewElements.lumpSumPreview);
        if (previewElement) {
            previewElement.textContent = event.target.value || '--';
        }
    }

    /**
     * Update lump sum month preview
     * @param {Event} event
     */
    updateLumpSumMonthPreview(event) {
        const previewElement = document.querySelector(this.previewElements.lumpSumPeriodPreview);
        if (previewElement) {
            previewElement.textContent = event.target.value || '--';
        }
    }

    /**
     * Attach listeners for skip payment calculations
     */
    attachSkipPaymentListeners() {
        const input = document.querySelector(this.calculationInputs.skipPeriod);
        if (input) {
            input.addEventListener('input', (e) => this.updateSkipPaymentPreview(e));
        }
    }

    /**
     * Update skip payment preview
     * @param {Event} event
     */
    updateSkipPaymentPreview(event) {
        const period = event.target.value || '--';
        const previewElement = document.querySelector(this.previewElements.skipPeriodPreview);
        if (previewElement) {
            previewElement.textContent = period;
        }
    }

    /**
     * Format currency for display
     * @param {number} value
     * @returns {string}
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(value);
    }

    /**
     * Reset all previews
     */
    resetPreviews() {
        Object.values(this.previewElements).forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                element.textContent = '--';
            }
        });
    }
}
