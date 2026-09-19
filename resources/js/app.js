import Alpine from 'alpinejs';
import { initThemeToggle } from './theme';
import { timeEntryCalendar, timeEntryDay } from './time-entry-calendar';

window.Alpine = Alpine;

Alpine.data('timeEntryCalendar', timeEntryCalendar);
Alpine.data('timeEntryDay', timeEntryDay);

Alpine.start();

document.addEventListener('DOMContentLoaded', initThemeToggle);
