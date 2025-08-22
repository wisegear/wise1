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

document.addEventListener('DOMContentLoaded', () => {
  const propBtn  = document.getElementById('propertyMenuButton');
  const propMenu = document.getElementById('propertyDropdown');

  if (!propBtn || !propMenu) return; // IDs missing in Blade

  const openMenu = () => {
    propMenu.classList.remove('hidden');
    // Optional: smooth pop (only if your Blade has these classes available)
    propMenu.classList.add('transition', 'duration-150', 'ease-out');
    requestAnimationFrame(() => {
      propMenu.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
    });
    propBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    // Optional animation out
    propMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    const onEnd = (e) => {
      if (e.target !== propMenu) return;
      propMenu.classList.add('hidden');
      propMenu.removeEventListener('transitionend', onEnd);
    };
    propMenu.addEventListener('transitionend', onEnd);
    propBtn.setAttribute('aria-expanded', 'false');
  };

  propBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isHidden = propMenu.classList.contains('hidden');
    isHidden ? openMenu() : closeMenu();
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    const open = !propMenu.classList.contains('hidden');
    if (!open) return;
    if (!propMenu.contains(e.target) && !propBtn.contains(e.target)) closeMenu();
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });
});




document.addEventListener('DOMContentLoaded', () => {
  // Mobile main toggle
  const mobileToggle = document.getElementById('mobileNavToggle');
  const mobileNav = document.getElementById('mobileNav');

  if (mobileToggle && mobileNav) {
    mobileToggle.addEventListener('click', () => {
      const isHidden = mobileNav.classList.contains('hidden');
      mobileNav.classList.toggle('hidden', !isHidden);
      mobileToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });
  }

  // Mobile property submenu
  const propBtn = document.getElementById('mobilePropertyBtn');
  const propMenu = document.getElementById('mobilePropertyMenu');

  if (propBtn && propMenu) {
    propBtn.addEventListener('click', () => {
      const isHidden = propMenu.classList.contains('hidden');
      propMenu.classList.toggle('hidden', !isHidden);
    });
  }
});