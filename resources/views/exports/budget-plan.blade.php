@php
    use App\Models\BudgetItem;
    use Cknow\Money\Money;

    // planned/booked/committed are pre-rolled-up on every node by BudgetPlanMeasures::annotate()

    // decimal() → plain number so the EUR column format applies in the sheet
    $decimal = static fn (Money $money) => (float) $money->formatByDecimal();

    // Σ a column over the root rows as Money (not float) so currency totals don't drift
    $total = static fn ($roots, callable $value) => $decimal(
        $roots->reduce(static fn (Money $carry, BudgetItem $item) => $carry->add($value($item)), Money::EUR(0))
    );

    $sections = [
        __('budget-plan.view.summary.income') => $income,
        __('budget-plan.view.summary.expense') => $expense,
    ];
@endphp
<html>
<table>
    <tbody>
    <tr height="30">
        <td colspan="5"><strong>{{ __('budget-plan.view.headline') }} · {{ $plan->label() }}</strong></td>
    </tr>
    @if($plan->fiscalYear)
        <tr>
            <td>{{ __('budget-plan.fiscal-year') }}</td>
            <td>{{ $plan->fiscalYear->label() }}</td>
        </tr>
    @endif
    <tr>
        <td>{{ __('budget-plan.view.state') }}</td>
        <td>{{ $plan->state->label() }}</td>
    </tr>
    <tr><td colspan="5"></td></tr>

    @foreach($sections as $sectionTitle => $items)
        <tr height="30">
            <td colspan="5"><b>{{ $sectionTitle }}</b></td>
        </tr>
        <tr>
            <td><b>{{ __('budget-plan.budget-shortname') }}</b></td>
            <td><b>{{ __('budget-plan.budget-longname') }}</b></td>
            <td align="right"><b>{{ __('budget-plan.view.col.planned') }}</b></td>
            <td align="right"><b>{{ __('budget-plan.view.col.booked') }}</b></td>
            <td align="right"><b>{{ __('budget-plan.view.col.committed') }}</b></td>
        </tr>
        @foreach($items as $item)
            <tr>
                <td>{{ $item->short_name }}</td>
                <td>{!! str_repeat('&nbsp;&nbsp;&nbsp;', $item->depth) !!}@if($item->is_group)<b>{{ $item->name }}</b>@else{{ $item->name }}@endif</td>
                <td align="right">@if($item->is_group)<b>{{ $decimal($item->planned) }}</b>@else{{ $decimal($item->planned) }}@endif</td>
                <td align="right">@if($item->is_group)<b>{{ $decimal($item->booked) }}</b>@else{{ $decimal($item->booked) }}@endif</td>
                <td align="right">@if($item->is_group)<b>{{ $decimal($item->committed) }}</b>@else{{ $decimal($item->committed) }}@endif</td>
            </tr>
        @endforeach
        @php($roots = $items->whereNull('parent_id'))
        <tr>
            <td><b>{{ $sectionTitle }}</b></td>
            <td><b>{{ __('budget-plan.export.total') }}</b></td>
            <td align="right"><b>{{ $total($roots, fn ($item) => $item->planned) }}</b></td>
            <td align="right"><b>{{ $total($roots, fn ($item) => $item->booked) }}</b></td>
            <td align="right"><b>{{ $total($roots, fn ($item) => $item->committed) }}</b></td>
        </tr>
        <tr><td colspan="5"></td></tr>
        <tr><td colspan="5"></td></tr>
    @endforeach
    </tbody>
</table>
</html>
