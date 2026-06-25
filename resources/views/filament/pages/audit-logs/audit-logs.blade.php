<x-filament::page>
    <x-filament::section>
        <x-slot name="heading">
            Audit Change Details
        </x-slot>

        @php
            $fields = array_unique(array_merge(
                array_keys($record->old_values ?? []),
                array_keys($record->new_values ?? [])
            ));
        @endphp

        <div class="fi-ta-content divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:bg-white/5 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">Field</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 border-s border-gray-200 dark:border-white/5">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">Old Value</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 border-s border-gray-200 dark:border-white/5">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">New Value</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($fields as $field)
                        @php
                            $old = $record->old_values[$field] ?? null;
                            $new = $record->new_values[$field] ?? null;
                        @endphp
                        <tr class="fi-ta-row [@media(hover:hover)]:hover:bg-gray-50 dark:[@media(hover:hover)]:hover:bg-white/5">
                            <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp flex flex-col justify-center px-3 py-4">
                                    <span class="text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $field }}
                                    </span>
                                </div>
                            </td>
                            <td class="fi-ta-cell p-0 border-s border-gray-200 dark:border-white/5 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp flex flex-col justify-center px-3 py-4">
                                    @if($old !== null && $old !== '')
                                        <div class="prose dark:prose-invert text-sm text-red-600 dark:text-red-400 break-words max-w-lg">
                                            {{ is_array($old) ? json_encode($old) : $old }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-sm">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="fi-ta-cell p-0 border-s border-gray-200 dark:border-white/5 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp flex flex-col justify-center px-3 py-4">
                                    @if($new !== null && $new !== '')
                                        <div class="prose dark:prose-invert text-sm text-green-600 dark:text-green-400 break-words max-w-lg">
                                            {{ is_array($new) ? json_encode($new) : $new }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-sm">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No changes recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament::page>
