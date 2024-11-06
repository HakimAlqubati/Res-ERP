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
            
            @foreach ($deducationTypes as $deducationId => $deducationName)
                <th>{{ $deducationName }}</th>
            @endforeach
            <th>{{'Other deducation'}}</th>
            
            @foreach ($allowanceTypes as $allowanceId => $allowanceName)
                <th>{{ $allowanceName }}</th>
            @endforeach
           <th>{{'Other allowances'}}</th>
           <th>{{'Bonus'}}</th>
           <th>{{'Net salary'}}</th>
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
                
                @foreach ($deducationTypes as $deducationId => $deducationName)
                    <td>{{ $item['res_deducation'][$deducationId] }}</td>
                @endforeach
                <td> {{$item['res_specific_deducation']}} </td>

                @foreach ($allowanceTypes as $allowanceId => $allowanceName)
                <td>{{ $item['res_allowances'][$allowanceId] }}</td>
                @endforeach
                <td> {{$item['res_specific_allowances']}} </td>
                <td>{{$item['bonus']}}</td>
               <th> {{$item['net_salary']}} </th>
            </tr>
        @endforeach
    </tbody>
</table>
