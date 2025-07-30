{{-- resources/views/filament/pages/stock-inventory-react-page.blade.php --}}
<x-filament-panels::page>
    <div id="stock-inventory-react"></div>
    @viteReactRefresh
    @vite(['resources/js/app.js'])
</x-filament-panels::page>
