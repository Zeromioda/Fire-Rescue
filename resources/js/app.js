import './bootstrap';

import Alpine from 'alpinejs';
import { initPasswordToggles } from './password-toggle';
import { initTableStacks } from './table-stack';

window.Alpine = Alpine;

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initPasswordToggles();
        initTableStacks();
    });
} else {
    initPasswordToggles();
    initTableStacks();
}

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-theme-toggle]')) return;
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
});
