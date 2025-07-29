import React from 'react';
import { createRoot } from 'react-dom/client';

function HelloReact() {
return <div>
    <h1>hi</h1>
    <h2>hi</h2>
    Hello, React in Laravel with Vite! 🎉
    </div>;
}

const root = createRoot(document.getElementById('react-app'));
root.render(<HelloReact />);