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
                sans: ['Inter', 'Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                arena: {
                    navy: '#0B1F3A',
                    ink: '#152A4A',
                    lime: '#A8E63D',
                    limeDark: '#7CC41A',
                    mist: '#F4F7F2',
                },
            },
        },
    },

    plugins: [forms],
};
