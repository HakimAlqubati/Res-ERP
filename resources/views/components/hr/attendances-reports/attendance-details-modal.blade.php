<div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50" style="z-index: 9999;">
    <div class="bg-white p-6 rounded-lg shadow-lg" style="width: 90%; max-width: 700px; color: black;">
        <h2 class="text-xl font-bold mb-4 text-center" style="color: #333;">Attendance Details</h2>
        <table class="table table-striped table-bordered" style="color: #333;">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 30%;">Check-in</th>
                    <th style="width: 30%;">Check-out</th>
                    <th style="width: 30%;">Total Hours</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $attendances = [];
                    $totalMinutes = 0;
                    foreach ($modalData as $detail) {
                        if ($detail['check_type'] === 'checkin') {
                            $attendances[$detail['period_id']]['checkins'][] = $detail['check_time'];
                        } elseif ($detail['check_type'] === 'checkout') {
                            $attendances[$detail['period_id']]['checkouts'][] = $detail['check_time'];
                        }
                    }
                    foreach ($attendances as $index => $attendance) {
                        $maxRows = max(count($attendance['checkins'] ?? []), count($attendance['checkouts'] ?? []));
                        for ($i = 0; $i < $maxRows; $i++) {
                            $checkin = $attendance['checkins'][$i] ?? null;
                            $checkout = $attendance['checkouts'][$i] ?? null;
                            if ($checkin && $checkout) {
                                $checkinTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkin);
                                $checkoutTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkout);
                                if ($checkoutTime->greaterThan($checkinTime)) {
                                    $minutesDifference = $checkinTime->diffInMinutes($checkoutTime);
                                    $hours = intdiv($minutesDifference, 60);
                                    $minutes = $minutesDifference % 60;
                                    $attendances[$index]['total_hours'][$i] = "{$hours}h {$minutes}m";
                                    $totalMinutes += $minutesDifference;
                                } else {
                                    $checkoutTime->addDay();
                                    $minutesDifference = $checkinTime->diffInMinutes($checkoutTime);
                                    $hours = intdiv($minutesDifference, 60);
                                    $minutes = $minutesDifference % 60;
                                    $attendances[$index]['total_hours'][$i] = "{$hours}h {$minutes}m";
                                    $totalMinutes += $minutesDifference;
                                }
                            } else {
                                $attendances[$index]['total_hours'][$i] = '-';
                            }
                        }
                    }
                    $totalHours = intdiv($totalMinutes, 60);
                    $remainingMinutes = $totalMinutes % 60;
                @endphp
                @foreach ($attendances as $index => $attendance)
                    @php
                        $maxRows = max(count($attendance['checkins'] ?? []), count($attendance['checkouts'] ?? []));
                    @endphp
                    @for ($i = 0; $i < $maxRows; $i++)
                        <tr>
                            @if ($i == 0)
                                <td rowspan="{{ $maxRows }}">{{ $loop->iteration }}</td>
                            @endif
                            <td>{{ $attendance['checkins'][$i] ?? '-' }}</td>
                            <td>{{ $attendance['checkouts'][$i] ?? '-' }}</td>
                            <td>{{ $attendance['total_hours'][$i] }}</td>
                        </tr>
                    @endfor
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-weight-bold">Total Hours:</td>
                    <td class="font-weight-bold">{{ $totalHours }}h {{ $remainingMinutes }}m</td>
                </tr>
            </tfoot>
        </table>
        <div class="text-center mt-4">
            <button wire:click="$set('showDetailsModal', false)" class="btn btn-primary"
                style="width: 100%;color:black">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function showDetailsModal() {
        document.getElementById('details-modal').style.display = 'flex';
    }

    function hideDetailsModal() {
        document.getElementById('details-modal').style.display = 'none';
    }
</script>
