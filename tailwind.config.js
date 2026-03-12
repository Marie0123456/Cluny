import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                indigo: {
                    50: '#e6e6f2',
                    100: '#c0c0d9',
                    200: '#8080b3',
                    300: '#4d4d99',
                    400: '#262680',
                    500: '#000080',
                    600: '#000080',
                    700: '#000066',
                    800: '#00004d',
                    900: '#000033',
                    950: '#00001a',
                },
            },
        },
    },

    plugins: [forms],
};
