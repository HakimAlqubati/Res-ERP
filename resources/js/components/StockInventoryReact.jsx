import React from 'react';
import { createRoot } from 'react-dom/client';

function StockInventoryReact() {
return <h1>Hello, React in Laravel with Vite! 🎉</h1>;
}

const root = createRoot(document.getElementById('stock-inventory-react'));
root.render(<StockInventoryReact />);