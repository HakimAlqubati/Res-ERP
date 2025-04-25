<x-filament::page>
    <x-filament::card>
        <h3 class="text-lg font-bold mb-4">Audit Change Details</h3>
        <table class="w-full text-sm">
            <thead>
                <tr>
                    <th class="text-left py-1 px-2">Field</th>
                    <th class="text-left py-1 px-2">Old Value</th>
                    <th class="text-left py-1 px-2">New Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record->old_values ?? [] as $field => $old)
                    <tr class="border-t">
                        <td class="px-2 py-1 font-semibold">{{ $field }}</td>
                        <td class="px-2 py-1 text-red-600">{{ $old }}</td>
                        <td class="px-2 py-1 text-green-600">{{ $record->new_values[$field] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::card>
</x-filament::page>
