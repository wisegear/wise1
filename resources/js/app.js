import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

       document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('userMenuButton');
        const dropdown = document.getElementById('userDropdown');

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!button.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Toggle dropdown on button click
        button.addEventListener('click', function (e) {
            e.preventDefault();
            dropdown.classList.toggle('hidden');
        });
    });