import '../css/app.css';
import './bootstrap';

import { ChakraProvider } from '@chakra-ui/react';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { system } from './theme';

const appName = import.meta.env.VITE_APP_NAME || 'Main Event Archive';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <ChakraProvider value={system}>
                <App {...props} />
            </ChakraProvider>,
        );
    },
    progress: {
        color: '#D4AF37',
    },
});
