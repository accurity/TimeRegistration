import Alpine from 'alpinejs';
import { initThemeToggle } from './theme';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', initThemeToggle);
