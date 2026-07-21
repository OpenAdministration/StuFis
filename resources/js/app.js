// CSP-safe build: uses Alpine's restricted evaluator instead of `new Function()`,
// so x-data/@click expressions don't need `unsafe-eval` under our enforcing CSP.
// The livewire.csp_safe config only swaps Livewire's *route-served* asset, which
// this app doesn't use (it bundles Livewire via Vite), so the swap must happen here.
import {Livewire, Alpine} from '../../vendor/livewire/livewire/dist/livewire.csp.esm';
import '@fontsource-variable/inter';
import {registerBudgetCollapse} from '../views/pages/budget-plan/⚡plan-view/budget-collapse';

// Register global Alpine components BEFORE Livewire.start() boots Alpine.
registerBudgetCollapse(Alpine);

Livewire.start()
