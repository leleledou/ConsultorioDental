import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Tokens de marca: ver docs/frontend/DESIGN.md
const brand = {
    50: '#E6F8F9',
    100: '#BFEFF1',
    200: '#8FE0E4',
    300: '#55CDD3',
    400: '#22C0C8',
    500: '#00B8C0', // turquesa oficial del logo: acentos e iconos, no texto
    600: '#008F96',
    700: '#007F86', // botones y enlaces (contraste AA con blanco)
    800: '#00666C',
    900: '#0B3C40', // turquesa profundo
};

/** @type {import('tailwindcss').Config} */
export default {
    // El rediseño es claro. Con 'class' (y sin aplicar nunca la clase) las
    // variantes dark: de las vistas aún no rediseñadas no siguen al sistema operativo.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand,
                // Las vistas de los módulos usan indigo: se remapea a la familia turquesa
                // para que se vean coherentes hasta que se rediseñen.
                indigo: {
                    50: brand[50],
                    100: brand[100],
                    200: brand[200],
                    300: brand[300],
                    400: brand[400],
                    500: '#00969D',
                    600: brand[700],
                    700: brand[800],
                    800: brand[900],
                    900: '#092F32',
                },
                ink: { DEFAULT: '#1F2933', soft: '#52606D', mute: '#7B8794' },
                surface: '#F6F9FA',
                line: '#E1E8EB',
                marca: '#909191', // gris del logo: decorativo, no para texto
            },
            boxShadow: {
                card: '0 1px 2px rgb(11 60 64 / 0.05), 0 6px 20px -8px rgb(11 60 64 / 0.10)',
                pop: '0 12px 32px -8px rgb(11 60 64 / 0.22)',
            },
        },
    },

    plugins: [forms],
};
