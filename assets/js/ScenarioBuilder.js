/**
 * ScenarioBuilder
 * 
 * Main orchestrator for the scenario builder interface.
 * Coordinates tabs, form fields, calculations, and user actions.
 * 
 * @class
 */
class ScenarioBuilder {
    constructor(options = {}) {
        this.monthlyPayment = options.monthlyPayment || 0;
        this.remainingMonths = options.remainingMonths || 0;
        
        // Initialize sub-modules
        this.tabs = new ScenarioTabs(options.tabs || {});
        this.formFields = new ScenarioFormFields(options.formFields || {});
        this.calculator = new ScenarioCalculator({
            monthlyPayment: this.monthlyPayment,
            ...options.calculator
        });
        this.actions = new ScenarioActions(options.actions || {});
        
        this.init();
    }

    /**
     * Initialize the scenario builder
     */
    init() {
        // Make actions globally accessible for inline onclick handlers
        this.actions.makeGlobalFunctions();
        
        // Attach form reset listener
        this.attachFormResetListener();
    }

    /**
     * Attach listener to form reset button
     */
    attachFormResetListener() {
        const form = document.getElementById('scenarioForm');
        if (form) {
            form.addEventListener('reset', () => {
                // Reset fields and previews after a short delay to allow form to reset
                setTimeout(() => {
                    this.formFields.resetFields();
                    this.calculator.resetPreviews();
                }, 0);
            });
        }
    }

    /**
     * Get current form state
     * @returns {Object}
     */
    getFormState() {
        return {
            scenarioType: this.formFields.getCurrentType(),
            activeTab: this.tabs.getActiveTab()
        };
    }

    /**
     * Destroy and cleanup
     */
    destroy() {
        this.tabs = null;
        this.formFields = null;
        this.calculator = null;
        this.actions = null;
    }
}
