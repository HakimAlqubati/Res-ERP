<div class="grid grid-cols-1 gap-4">
    @forelse($periods as $period)
        <div class="bg-white rounded-2xl shadow border border-gray-200 p-5 transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    {{ $period->name }}
                </h3>
                <span class="text-sm text-gray-500">
                    #{{ $period->id }}
                </span>
            </div>

            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <div class="flex items-center gap-2">
                    <!-- <x-heroicon-o-clock class="w-5 h-5 text-blue-500" /> -->
                    <span>{{ $period->start_at }} → {{ $period->end_at }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- <x-heroicon-o-calendar-days class="w-5 h-5 text-green-500" /> -->
                    <span>
                        {{ implode(', ', array_map('ucfirst', json_decode($period->pivot->period_days ?? '[]'))) }}
                    </span>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 italic py-6">
            No work periods assigned to this employee.
        </div>
    @endforelse
</div>
