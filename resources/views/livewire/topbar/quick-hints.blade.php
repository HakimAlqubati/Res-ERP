<div 
    x-data 
    x-tooltip.raw="
            Orders: {{ $ordersToday }} · Purchases: {{ $purchasesToday }} · GRN: {{ $grnToday }}
    "
>
    <x-filament::icon-button 
        icon="heroicon-o-light-bulb" 
        label="Todays Snapshot"
        size="sm"
        color="warning"
        class="shadow-xl shadow-yellow-500/80 
               drop-shadow-[0_0_15px_rgba(250,204,21,1)] 
               drop-shadow-[0_0_30px_rgba(253,224,71,0.9)]" 
    />
</div>
