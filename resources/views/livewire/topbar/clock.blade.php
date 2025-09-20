<div wire:poll.60000ms="refresh" class="flex justify-center items-center">
    <span title="Timezone: {{ config('app.timezone') }}"
        style="
            display:inline-block;
            padding: 3px 14px;
            border: 2px solid #0d7c66;
            border-top-right-radius: 10px;
            border-bottom-left-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 1px;
            color: #0d7c66;
            /* background: #fff; */
            /* تأثير الضوء */
              box-shadow: 0 0 4px rgba(13,124,102,0.6),
                        0 0 8px rgba(13,124,102,0.4);
            transition: box-shadow 0.3s ease-in-out;
        ">
        {{ $time }}
    </span>
</div>
