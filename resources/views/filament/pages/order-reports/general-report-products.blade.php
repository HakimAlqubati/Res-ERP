<x-filament::page>
    {{ $this->getTableFiltersForm() }}
    @if (isset($branch_id) && is_numeric($branch_id))
        <tables::table class="w-full text-sm text-left pretty  ">
            <thead>




                <tables::row class="header_report">
                    <th class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">
                        <p>{{ __('lang.general_report_of_products') }}</p>
                        <p>({{ isset($branch_id) && is_numeric($branch_id) ? \App\Models\Branch::find($branch_id)->name : __('lang.choose_branch') }})
                        </p>
                    </th>
                    <th class="no_border_right_left">
                        <p>{{ __('lang.start_date') . ': ' . $start_date }}</p>
                        <br>
                        <p>{{ __('lang.end_date') . ': ' . $end_date }}</p>
                    </th>
                    <th style="text-align: center; vertical-align: middle;"
                        class="{{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                        <img class="circle-image"
                            src="https://w7.pngwing.com/pngs/882/726/png-transparent-chef-cartoon-chef-photography-cooking-fictional-character-thumbnail.png"
                            alt="">
                    </th>
                </tables::row>
                <tables::row>
                    <th>{{ __('lang.category') }}</th>

                    <th>{{ __('lang.quantity') }}</th>
                    <th>{{ __('lang.price') }}</th>
                </tables::row>
            </thead>
            <tbody>

                @foreach ($report_data as $data)
                    
                    <tables::row>

                        <tables::cell>
                            <a target="_blank" href="{{ url($data?->url_report_details) }}"> {{ $data?->category }}</a>
                        </tables::cell>
                        <tables::cell> {{ $data?->quantity }} </tables::cell>
                        <tables::cell> {{ $data?->amount . ' ' . $data?->symbol }} </tables::cell>
                    </tables::row>
                @endforeach
                <tables::row>
                    <tables::cell> {{ __('lang.total') }} </tables::cell>
                    <tables::cell> {{ $total_quantity }} </tables::cell>
                    <tables::cell> {{ $total_price }} </tables::cell>
                </tables::row>
            </tbody>

        </tables::table>
    @else
        <div class="please_select_message_div" style="text-align: center;">

            <h1 class="please_select_message_text">{{ __('lang.please_select_branch') }}</h1>
        </div>
    @endif
</x-filament::page>
