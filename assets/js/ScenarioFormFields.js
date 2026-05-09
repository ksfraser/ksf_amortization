/**
 * ScenarioFormFields
 * 
 * Responsible for managing form field visibility and state based on scenario type.
 * Shows/hides appropriate configuration sections and manages field dependencies.
 * 
 * @class
 */
class ScenarioFormFields {
    constructor(options = {}) {
        this.scenarioTypeSelector = options.scenarioTypeSelector || '#scenarioType';
        this.fieldMappings = {
            'extra_monthly': '#extra-monthly-section',
            'lump_sum': '#lump-sum-section',
            'skip_payment': '#skip-payment-section',
            'acceleration': '#acceleration-section',
            'custom': '#custom-section'
        };
        
        this.init();
    }

    /**
     * Initialize field event listeners
     */
    init() {
        const typeSelect = document.querySelector(this.scenarioTypeSelector);
        if (typeSelect) {
            typeSelect.addEventListener('change', (e) => this.handleTypeChange(e));
        }
    }

    /**
     * Handle scenario type change
     * @param {Event} event
     */
    handleTypeChange(event) {
        const selectedType = event.target.value;
        this.updateFieldVisibility(selectedType);
    }

    /**
     * Update field visibility based on selected type
     * @param {string} type - Scenario type selected
     */
    updateFieldVisibility(type) {
        // Hide all sections
        Object.values(this.fieldMappings).forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                element.style.display = 'none';
            }
        });

        // Show selected section
        if (this.fieldMappings[type]) {
            const element = document.querySelector(this.fieldMappings[type]);
            if (element) {
                element.style.display = 'block';
            }
        }
    }

    /**
     * Get current scenario type
     * @returns {string}
     */
    getCurrentType() {
        const typeSelect = document.querySelector(this.scenarioTypeSelector);
        return typeSelect ? typeSelect.value : '';
    }

    /**
     * Reset all fields
     */
    resetFields() {
        Object.values(this.fieldMappings).forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                element.style.display = 'none';
            }
        });

        const typeSelect = document.querySelector(this.scenarioTypeSelector);
        if (typeSelect) {
            typeSelect.value = '';
        }
    }
}
