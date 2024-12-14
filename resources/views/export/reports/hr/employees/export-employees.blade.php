<table>
    <thead>
        <tr>
            <th>{{ 'ID' }}</th>
            <th>{{ 'Employee No' }}</th>
            <th>{{ 'Name' }}</th>
            <th>{{ 'Branch' }}</th>
            <th>{{ 'Job Title' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->employee_no }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->branch?->name }}</td>
                <td>{{ $item->job_title }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
