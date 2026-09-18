import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['CalibriWeb', 'Calibri', 'Segoe UI', 'sans-serif'],
                display: ['Bitter', 'Georgia', 'serif'],
            },
            colors: {
                brand: {
                    50: '#F2F8FC',
                    100: '#E2F1F8',
                    400: '#1D93C9',
                    600: '#027CB5',
                    800: '#01608D',
                    darkfg: '#3FA7D6',
                    darkhover: '#63BBE3',
                    darktint: '#0B2C3D',
                },
                ink: {
                    50: '#F6F8FA',
                    100: '#EDF1F4',
                    200: '#DEE5EA',
                    300: '#C3CDD4',
                    400: '#94A3AC',
                    500: '#6B7C88',
                    700: '#4A5C68',
                    900: '#0F1A21',
                },
                dark: {
                    bg: '#0E1519',
                    surface1: '#141E24',
                    surface2: '#1B272E',
                    border: '#2A3A44',
                    borderfield: '#3B4D58',
                    text1: '#E6EDF1',
                    text2: '#9FB1BC',
                    text3: '#6E818D',
                },
                status: {
                    'green-fg': '#1E7A5F',
                    'green-bg': '#E3F2EC',
                    'green-border': '#B9DDCF',
                    'green-fg-dark': '#6FD3AC',
                    'green-bg-dark': '#12302A',
                    'green-border-dark': '#1E4C41',
                    'amber-fg': '#A76A12',
                    'amber-bg': '#FAEFDD',
                    'amber-border': '#EBD3A9',
                    'amber-fg-dark': '#E0AC5C',
                    'amber-bg-dark': '#33260F',
                    'amber-border-dark': '#574118',
                    'red-fg': '#AE3A2F',
                    'red-bg': '#F8E6E4',
                    'red-border': '#E3B4AE',
                    'red-fg-dark': '#E88D82',
                    'red-bg-dark': '#341D1A',
                    'red-border-dark': '#5A312C',
                    'blue-fg': '#01608D',
                    'blue-bg': '#E2F1F8',
                    'blue-border': '#B5D9EA',
                    'blue-fg-dark': '#63BBE3',
                    'blue-bg-dark': '#0B2C3D',
                    'blue-border-dark': '#14455D',
                    'gray-fg': '#4A5C68',
                    'gray-bg': '#EDF1F4',
                    'gray-border': '#DEE5EA',
                    'gray-fg-dark': '#9FB1BC',
                    'gray-bg-dark': '#1B272E',
                    'gray-border-dark': '#2A3A44',
                },
            },
        },
    },

    plugins: [forms],
};
