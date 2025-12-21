/**
 * ScenarioTabs
 * 
 * Responsible for managing tab switching and visibility in the scenario builder.
 * Handles active state management and content display toggling.
 * 
 * @class
 */
class ScenarioTabs {
    constructor(options = {}) {
        this.tabButtonSelector = options.tabButtonSelector || '.tab-button';
        this.tabContentSelector = options.tabContentSelector || '.tab-content';
        this.activeClass = options.activeClass || 'active';
        this.tabDataAttribute = options.tabDataAttribute || 'data-tab';
        
        this.init();
    }

    /**
     * Initialize tab event listeners
     */
    init() {
        this.attachEventListeners();
    }

    /**
     * Attach click handlers to all tab buttons
     */
    attachEventListeners() {
        document.querySelectorAll(this.tabButtonSelector).forEach(button => {
            button.addEventListener('click', (e) => this.handleTabClick(e));
        });
    }

    /**
     * Handle tab button click event
     * @param {Event} event 
     */
    handleTabClick(event) {
        const tabName = event.target.dataset[this.tabDataAttribute.replace('data-', '')];
        if (tabName) {
            this.switchTab(tabName);
        }
    }

    /**
     * Switch to specified tab
     * @param {string} tabName - Name of the tab to switch to
     */
    switchTab(tabName) {
        // Hide all tab content
        document.querySelectorAll(this.tabContentSelector).forEach(tab => {
            tab.classList.remove(this.activeClass);
        });

        // Deactivate all tab buttons
        document.querySelectorAll(this.tabButtonSelector).forEach(btn => {
            btn.classList.remove(this.activeClass);
        });

        // Show selected tab content
        const tabContent = document.getElementById(`${tabName}-tab`);
        if (tabContent) {
            tabContent.classList.add(this.activeClass);
        }

        // Activate selected tab button
        const activeButton = document.querySelector(
            `${this.tabButtonSelector}[data-tab="${tabName}"]`
        );
        if (activeButton) {
            activeButton.classList.add(this.activeClass);
        }
    }

    /**
     * Get currently active tab name
     * @returns {string|null}
     */
    getActiveTab() {
        const activeButton = document.querySelector(
            `${this.tabButtonSelector}.${this.activeClass}`
        );
        return activeButton ? activeButton.dataset.tab : null;
    }
}
