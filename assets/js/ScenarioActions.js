/**
 * ScenarioActions
 * 
 * Responsible for scenario management actions (view, delete, compare).
 * Handles navigation and user confirmations for scenario operations.
 * 
 * @class
 */
class ScenarioActions {
    constructor(options = {}) {
        this.viewHandler = options.viewHandler || this.defaultViewHandler;
        this.deleteHandler = options.deleteHandler || this.defaultDeleteHandler;
        this.compareHandler = options.compareHandler || this.defaultCompareHandler;
        this.confirmMessage = options.confirmMessage || 'Delete this scenario? This cannot be undone.';
    }

    /**
     * View a scenario
     * @param {string} scenarioId - ID of scenario to view
     */
    viewScenario(scenarioId) {
        this.viewHandler(scenarioId);
    }

    /**
     * Delete a scenario with confirmation
     * @param {string} scenarioId - ID of scenario to delete
     */
    deleteScenario(scenarioId) {
        if (confirm(this.confirmMessage)) {
            this.deleteHandler(scenarioId);
        }
    }

    /**
     * Compare two scenarios
     * @param {string} scenario1Id - ID of first scenario
     * @param {string} scenario2Id - ID of second scenario
     */
    compareScenarios(scenario1Id, scenario2Id) {
        this.compareHandler(scenario1Id, scenario2Id);
    }

    /**
     * Default view handler - navigate to scenario view page
     * @param {string} scenarioId
     */
    defaultViewHandler(scenarioId) {
        window.location.href = `?action=scenario&mode=view&scenario_id=${scenarioId}`;
    }

    /**
     * Default delete handler - navigate to delete action
     * @param {string} scenarioId
     */
    defaultDeleteHandler(scenarioId) {
        window.location.href = `?action=scenario&mode=delete&scenario_id=${scenarioId}`;
    }

    /**
     * Default compare handler - navigate to compare page
     * @param {string} scenario1Id
     * @param {string} scenario2Id
     */
    defaultCompareHandler(scenario1Id, scenario2Id) {
        window.location.href = `?action=scenario&mode=compare&s1=${scenario1Id}&s2=${scenario2Id}`;
    }

    /**
     * Make action handlers globally accessible
     */
    makeGlobalFunctions() {
        window.viewScenario = (id) => this.viewScenario(id);
        window.deleteScenario = (id) => this.deleteScenario(id);
        window.compareScenarios = (s1, s2) => this.compareScenarios(s1, s2);
    }
}
