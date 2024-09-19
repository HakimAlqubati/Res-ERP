<!-- resources/views/filament/modals/service-request-comments-modal.blade.php -->

<div>
    <x-filament::modal>
        <x-slot name="header">
            Comments for Service Request
        </x-slot>
        <x-slot name="body">
            <table class="table-auto w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-2">Comment</th>
                        <th class="px-4 py-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $comment)
                        <tr>
                            <td class="border px-4 py-2">{{ $comment->comment }}</td>
                            <td class="border px-4 py-2">{{ $comment->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-slot>
        <x-slot name="footer">
            <x-filament::button type="button" class="ml-4" data-dismiss="modal">
                Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
