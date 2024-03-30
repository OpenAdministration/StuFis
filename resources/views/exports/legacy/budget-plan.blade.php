<html>

<table>
    <tbody>
    <tr height="30"><td><strong>Jahresabschluss</strong></td></tr>
    <tr>
        <td>von</td>
        <td>{{ $plan->von }}</td>
    </tr>
    <tr>
        <td>bis</td>
        <td>{{ $plan->bis }}</td>
    </tr>
    <tr><td colspan="5"></td></tr>
    <tr height="30">
        <td><b>Nr</b></td>
        <td><b>Name</b></td>
        <td align="center"><b>Ansatz</b></td>
        <td align="center"><b>Ist</b></td>
        <td align="center"><b>Differenz</b></td>
    </tr>
    <tr><td colspan="5"></td></tr>
    @php($rowNumber = 5)
    @foreach([$inGroup, $outGroup] as $groups)
        @php($groupRows = [])
        <tr>
            <td colspan="5"><b>{{ $loop->index === 0 ? "Einnahmen" : "Ausgaben" }}</b></td>
        </tr>
        @php($rowNumber++)
        @foreach($groups as $group)
            <tr>
                <td><b>{{ $group->type ? 'A' : 'E' }} {{ $loop->iteration }}</b></td>
                <td><b>{{ $group->gruppen_name }}</b></td>
                <td><b>=SUM(C{{ $rowNumber + 2 }}:C{{ $rowNumber + 1 + $group->budgetItems->count() }})</b></td>
                <td><b>=SUM(D{{ $rowNumber + 2 }}:D{{ $rowNumber + 1 + $group->budgetItems->count() }})</b></td>
                <td><b>=SUM(E{{ $rowNumber + 2 }}:E{{ $rowNumber + 1 + $group->budgetItems->count() }})</b></td>
            </tr>
            @php($rowNumber++)
            @php($groupRows[] = $rowNumber)
            @foreach($group->budgetItems as $item)
                <tr>
                    <td>{{ $item->titel_nr }}</td>
                    <td>{{ $item->titel_name }}</td>
                    <td>{{ $item->value }}</td>
                    <td>{{ $item->bookingSum() }}</td>
                    <td>{{ $item->bookingDiff() }}</td>
                </tr>
                @php($rowNumber++)
            @endforeach
            <tr><td colspan="5"></td></tr>
            @php($rowNumber++)
        @endforeach
        <tr>
            <td><b>{{ $loop->index === 0 ? "Einnahmen" : "Ausgaben" }}</b></td>
            <td><b>Summe</b></td>
            <td><b>{{ $exporter->sum('C', $groupRows) }}</b></td>
            <td><b>{{ $exporter->sum('D', $groupRows) }}</b></td>
            <td><b>{{ $exporter->sum('E', $groupRows) }}</b></td>
        </tr>
        <tr><td colspan="5"></td></tr>
        <tr><td colspan="5"></td></tr>
        @php($rowNumber+=3)
    @endforeach
    </tbody>
</table>
</html>
