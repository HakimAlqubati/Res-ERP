<table>
    <thead>
        <tr>
            <th>{{ 'ID' }}</th>
            <th>{{ 'name' }}</th>
            <th>{{ 'description' }}</th>
            <th>{{ 'code' }}</th>
            
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->code }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
