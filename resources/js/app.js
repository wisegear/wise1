import './bootstrap';

// Initialize Alpine.js for reactive components
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


// User menu dropdown toggle functionality
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


// Property dropdown toggle functionality (desktop)
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


// EPC dropdown toggle functionality (desktop) - mirrors Property dropdown logic
document.addEventListener('DOMContentLoaded', () => {
  const epcBtn  = document.getElementById('epcMenuButton');
  const epcMenu = document.getElementById('epcDropdown');

  if (!epcBtn || !epcMenu) return; // IDs missing in Blade

  const openMenu = () => {
    epcMenu.classList.remove('hidden');
    // Optional: smooth pop (only if your Blade has these classes available)
    epcMenu.classList.add('transition', 'duration-150', 'ease-out');
    requestAnimationFrame(() => {
      epcMenu.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
    });
    epcBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    // Optional animation out
    epcMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    const onEnd = (e) => {
      if (e.target !== epcMenu) return;
      epcMenu.classList.add('hidden');
      epcMenu.removeEventListener('transitionend', onEnd);
    };
    epcMenu.addEventListener('transitionend', onEnd);
    epcBtn.setAttribute('aria-expanded', 'false');
  };

  epcBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isHidden = epcMenu.classList.contains('hidden');
    isHidden ? openMenu() : closeMenu();
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    const open = !epcMenu.classList.contains('hidden');
    if (!open) return;
    if (!epcMenu.contains(e.target) && !epcBtn.contains(e.target)) closeMenu();
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });
});


// Mortgages dropdown toggle functionality (desktop) - mirrors EPC/Property logic
document.addEventListener('DOMContentLoaded', () => {
  const mortBtn  = document.getElementById('mortgagesMenuButton');
  const mortMenu = document.getElementById('mortgagesDropdown');

  if (!mortBtn || !mortMenu) return; // IDs missing in Blade

  const openMenu = () => {
    mortMenu.classList.remove('hidden');
    mortMenu.classList.add('transition', 'duration-150', 'ease-out');
    requestAnimationFrame(() => {
      mortMenu.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
    });
    mortBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    mortMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    const onEnd = (e) => {
      if (e.target !== mortMenu) return;
      mortMenu.classList.add('hidden');
      mortMenu.removeEventListener('transitionend', onEnd);
    };
    mortMenu.addEventListener('transitionend', onEnd);
    mortBtn.setAttribute('aria-expanded', 'false');
  };

  mortBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isHidden = mortMenu.classList.contains('hidden');
    isHidden ? openMenu() : closeMenu();
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    const open = !mortMenu.classList.contains('hidden');
    if (!open) return;
    if (!mortMenu.contains(e.target) && !mortBtn.contains(e.target)) closeMenu();
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });
});

// Calculators dropdown toggle functionality (desktop) - mirrors others
document.addEventListener('DOMContentLoaded', () => {
  const calcBtn  = document.getElementById('calculatorsMenuButton');
  const calcMenu = document.getElementById('calculatorsDropdown');

  if (!calcBtn || !calcMenu) return; // IDs missing in Blade

  const openMenu = () => {
    calcMenu.classList.remove('hidden');
    calcMenu.classList.add('transition', 'duration-150', 'ease-out');
    requestAnimationFrame(() => {
      calcMenu.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
    });
    calcBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    calcMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    const onEnd = (e) => {
      if (e.target !== calcMenu) return;
      calcMenu.classList.add('hidden');
      calcMenu.removeEventListener('transitionend', onEnd);
    };
    calcMenu.addEventListener('transitionend', onEnd);
    calcBtn.setAttribute('aria-expanded', 'false');
  };

  calcBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isHidden = calcMenu.classList.contains('hidden');
    isHidden ? openMenu() : closeMenu();
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    const open = !calcMenu.classList.contains('hidden');
    if (!open) return;
    if (!calcMenu.contains(e.target) && !calcBtn.contains(e.target)) closeMenu();
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });
});


// Mobile navigation toggles
document.addEventListener('DOMContentLoaded', () => {
  // Mobile main nav toggle
  const mobileToggle = document.getElementById('mobileNavToggle');
  const mobileNav = document.getElementById('mobileNav');

  if (mobileToggle && mobileNav) {
    mobileToggle.addEventListener('click', () => {
      const isHidden = mobileNav.classList.contains('hidden');
      mobileNav.classList.toggle('hidden', !isHidden);
      mobileToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });
  }

  // Mobile property submenu toggle
  const propBtn = document.getElementById('mobilePropertyBtn');
  const propMenu = document.getElementById('mobilePropertyMenu');

  if (propBtn && propMenu) {
    propBtn.addEventListener('click', () => {
      const isHidden = propMenu.classList.contains('hidden');
      propMenu.classList.toggle('hidden', !isHidden);
    });
  }

  // Mobile EPC submenu toggle - mirrors Property submenu logic
  const epcBtn = document.getElementById('mobileEpcBtn');
  const epcMenu = document.getElementById('mobileEpcMenu');

  if (epcBtn && epcMenu) {
    epcBtn.addEventListener('click', () => {
      const isHidden = epcMenu.classList.contains('hidden');
      epcMenu.classList.toggle('hidden', !isHidden);
    });
  }

  // Mobile Mortgages submenu toggle - mirrors other mobile submenu logic
  const mortMobileBtn = document.getElementById('mobileMortgagesBtn');
  const mortMobileMenu = document.getElementById('mobileMortgagesMenu');

  if (mortMobileBtn && mortMobileMenu) {
    mortMobileBtn.addEventListener('click', () => {
      const isHidden = mortMobileMenu.classList.contains('hidden');
      mortMobileMenu.classList.toggle('hidden', !isHidden);
    });
  }

  // Mobile Calculators submenu toggle
  const calcMobileBtn = document.getElementById('mobileCalculatorsBtn');
  const calcMobileMenu = document.getElementById('mobileCalculatorsMenu');

  if (calcMobileBtn && calcMobileMenu) {
    calcMobileBtn.addEventListener('click', () => {
      const isHidden = calcMobileMenu.classList.contains('hidden');
      calcMobileMenu.classList.toggle('hidden', !isHidden);
    });
  }
});

// Mortgage Calculator value input transform.

document.addEventListener("DOMContentLoaded", () => {
  const amountInput = document.getElementById("amount");

  if (amountInput) {
    amountInput.addEventListener("input", (e) => {
      let value = e.target.value.replace(/,/g, ""); // remove existing commas
      if (!isNaN(value) && value.length > 0) {
        e.target.value = parseInt(value, 10).toLocaleString("en-GB");
      }
    });
  }
});