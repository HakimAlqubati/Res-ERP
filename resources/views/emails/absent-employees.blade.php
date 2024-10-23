<h1>Absent Employees on {{ $date }}</h1>
<p>The following employees were absent on {{ $date }}:</p>
<ul>
    @foreach ($absentEmployees as $employee)
        <li>
            {{ $employee->name ?? 'No name' }} 
            (Employee ID: {{ $employee->id ?? 'No ID' }})
        </li>
        <pre>{{ json_encode($employee) }}</pre> <!-- This will show the structure of the $employee object -->
    @endforeach
</ul>
