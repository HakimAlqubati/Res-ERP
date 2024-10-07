<table>
    <thead>
        <tr>
            <th>
                {{ 'Name' }}
            </th>
            <th>
                {{ 'Branch' }}
            </th>
            <th>
                {{ 'ID' }}
            </th>
            <th>
                {{ 'Job' }}
            </th>
            <th>
                {{ 'Basic salary' }}
            </th>
            <th>
                {{ 'House Allowance' }}
            </th>
            <th>
                {{ 'Absent' }}
            </th>
            <th>
                {{ 'Deducation manager' }}
            </th>
            <th>
                {{ 'Absent (Hours)' }}
            </th>
            <th>
                {{ 'CUT daywork' }}
            </th>
            <th>
                {{ 'OT (Days)' }}
            </th>
            <th>
                {{ 'OT (Hours)' }}
            </th>
            <th>
                {{ 'ُEPF' }}
            </th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $item)
            <tr>
                <td>{{ $item['employee_name'] }} </td>
                <td>{{ $item['branch'] }} </td>
                <td>{{ $item['employee_no'] }} </td>
                <td>{{ $item['job_title'] }} </td>
                <td>{{ $item['basic_salary'] }} </td>
                <td>
                    {{-- {{$item['basic_salary']}} --}}
                </td>
                <td>
                    {{-- {{$item['basic_salary']}} --}}
                </td>
                <td>
                    {{-- {{$item['total_deductions']}} --}}
                </td>
                <td>
                    {{-- {{$item['basic_salary']}} --}}
                </td>
                <td>
                    {{-- {{$item['basic_salary']}} --}}
                </td>
                <td>
                    {{-- {{$item['basic_salary']}} --}}
                </td>
                <td>
                    {{$item['overtime_hours']}}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
