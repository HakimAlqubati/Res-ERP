 <x-filament::modal id="attendance-details" width="3xl">
     <x-slot name="heading">
         {{ __('Attendance Details') }}
         @if ($modalData['date'])
             – {{ $modalData['date'] }}
         @endif
     </x-slot>

     <div class="overflow-x-auto">
         @php
             $attendances = [];
             $totalMinutes = 0;

             // تجهيز بيانات الحضور
             foreach ($modalData['data'] as $detail) {
                 if ($detail['check_type'] === 'checkin') {
                     $attendances[$detail['period_id']]['checkins'][] = $detail['check_time'];
                 } elseif ($detail['check_type'] === 'checkout') {
                     $attendances[$detail['period_id']]['checkouts'][] = $detail['check_time'];
                 }
             }

             // حساب الساعات بين checkin و checkout
             foreach ($attendances as $index => $attendance) {
                 $maxRows = max(count($attendance['checkins'] ?? []), count($attendance['checkouts'] ?? []));
                 for ($i = 0; $i < $maxRows; $i++) {
                     $checkin = $attendance['checkins'][$i] ?? null;
                     $checkout = $attendance['checkouts'][$i] ?? null;

                     if ($checkin && $checkout) {
                         $checkinTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkin);
                         $checkoutTime = \Carbon\Carbon::createFromFormat('H:i:s', $checkout);

                         if (!$checkoutTime->greaterThanOrEqualTo($checkinTime)) {
                             $checkoutTime->addDay(); // لو الخروج بعد منتصف الليل
                         }

                         $minutesDifference = $checkinTime->diffInMinutes($checkoutTime);
                         $hours = intdiv($minutesDifference, 60);
                         $minutes = $minutesDifference % 60;

                         $attendances[$index]['total_hours'][$i] = "{$hours}h {$minutes}m";
                         $totalMinutes += $minutesDifference;
                     } else {
                         $attendances[$index]['total_hours'][$i] = '-';
                     }
                 }
             }

             $totalHours = intdiv($totalMinutes, 60);
             $remainingMinutes = $totalMinutes % 60;
         @endphp

         <table class="w-full border border-gray-400 border-collapse text-sm table table-striped table-bordered">
             <thead class="bg-gray-100">
                 <tr>
                     <th class="border border-gray-400 px-3 py-2 text-center w-12">#</th>
                     <th class="border border-gray-400 px-3 py-2 text-center w-1/3">{{ __('Check-in') }}</th>
                     <th class="border border-gray-400 px-3 py-2 text-center w-1/3">{{ __('Check-out') }}</th>
                     <th class="border border-gray-400 px-3 py-2 text-center w-1/3">{{ __('Total Hours') }}</th>
                 </tr>
             </thead>
             <tbody>
                 @foreach ($attendances as $index => $attendance)
                     @php $maxRows = max(count($attendance['checkins'] ?? []), count($attendance['checkouts'] ?? [])); @endphp
                     @for ($i = 0; $i < $maxRows; $i++)
                         <tr class="hover:bg-gray-50">
                             @if ($i === 0)
                                 <td class="border border-gray-400 px-3 py-2 text-center font-semibold"
                                     rowspan="{{ $maxRows }}">
                                     {{ $loop->iteration }}
                                 </td>
                             @endif
                             <td class="border border-gray-400 px-3 py-2 text-center">
                                 {{ $attendance['checkins'][$i] ?? '-' }}
                             </td>
                             <td class="border border-gray-400 px-3 py-2 text-center">
                                 {{ $attendance['checkouts'][$i] ?? '-' }}
                             </td>
                             <td class="border border-gray-400 px-3 py-2 text-center">
                                 {{ $attendance['total_hours'][$i] }}
                             </td>
                         </tr>
                     @endfor
                 @endforeach
             </tbody>
             <tfoot>
                 <tr class="bg-gray-50 font-bold">
                     <td colspan="3" class="border border-gray-400 px-3 py-2 text-right">
                         {{ __('Total Hours:') }}
                     </td>
                     <td class="border border-gray-400 px-3 py-2 text-center">
                         {{ $totalHours }}h {{ $remainingMinutes }}m
                     </td>
                 </tr>
             </tfoot>
         </table>
     </div>

     <x-slot name="footer">
         <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id: 'attendance-details' })">
             {{ __('Close') }}
         </x-filament::button>
     </x-slot>
 </x-filament::modal>
