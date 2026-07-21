// Alpine component for the budget-plan view's collapsible group tree.
//
// This is registered from app.js *before* Livewire.start() (which boots Alpine),
// because Alpine.data() must exist before Alpine initializes any matching x-data.
// A co-located MFC `<name>.js` can't do this — its run() executes per-mount, i.e.
// after Alpine has already tried to evaluate the component's own x-data (that's the
// "Undefined variable: budgetCollapse" race). Global Alpine registrations belong in
// the bundle entry; MFC .js is for per-instance $wire logic.
//
// All the JS Alpine's CSP evaluator can't parse (arrow fns, spreads, .filter/.some,
// array literals) lives here — a real bundled module, never eval'd — so the blade
// keeps only CSP-safe expressions (bare refs, method calls). Array config is passed
// via data-* attributes rather than array literals in Alpine expressions.
export function registerBudgetCollapse(Alpine) {
    Alpine.data('budgetCollapse', (persistKey) => ({
        // persisted list of collapsed group ids, scoped per plan + budget side
        collapsed: Alpine.$persist([]).as(persistKey),
        allGroupIds: [],

        init() {
            this.allGroupIds = JSON.parse(this.$el.dataset.groupIds || '[]');
        },

        toggle(id) {
            this.collapsed = this.collapsed.includes(id)
                ? this.collapsed.filter(existing => existing !== id)
                : [...this.collapsed, id];
        },

        // receives the row element ($el); reads its own ancestor ids from a data-* attr
        isHidden(row) {
            const ancestors = JSON.parse(row.dataset.ancestorIds || '[]');
            return ancestors.some(id => this.collapsed.includes(id));
        },

        collapseAll() { this.collapsed = [...this.allGroupIds]; },
        expandAll()   { this.collapsed = []; },
    }));
}
