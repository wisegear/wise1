import './bootstrap';

// Initialize Alpine.js for reactive components
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// --- Global dropdown registry to ensure only one is open at a time ---
(function () {
  const registry = (window._navDropdowns = new Set());

  // Register a [button, menu] pair so we can close others when one opens
  window._registerDropdownPair = function (btn, menu) {
    if (!btn || !menu) return;
    registry.add({ btn, menu });
  };

  // Close all registered dropdowns except the provided menu
  window._closeAllDropdownsExcept = function (exceptMenu) {
    registry.forEach(({ btn, menu }) => {
      if (menu === exceptMenu) return;
      if (!menu.classList.contains('hidden')) {
        window._animateClose(menu);
      }
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  };

  // --- Shared animation helpers for dropdowns ---
  window._animateOpen = function (menuEl) {
    if (!menuEl) return;
    menuEl.classList.remove('hidden');
    menuEl.classList.add('transition', 'duration-150', 'ease-out');
    requestAnimationFrame(() => {
      menuEl.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
    });
  };

  window._animateClose = function (menuEl, cb) {
    if (!menuEl) return;
    if (menuEl.classList.contains('hidden')) { if (cb) cb(); return; }
    menuEl.classList.add('transition', 'duration-100', 'ease-in', 'opacity-0', 'scale-95', 'pointer-events-none');
    const onEnd = (e) => {
      if (e.target !== menuEl) return;
      menuEl.classList.add('hidden');
      menuEl.removeEventListener('transitionend', onEnd);
      if (cb) cb();
    };
    menuEl.addEventListener('transitionend', onEnd);
  };
})();

// User menu dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('userMenuButton');
    const dropdown = document.getElementById('userDropdown');

    if (!button || !dropdown) return;

    window._registerDropdownPair(button, dropdown);

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!button.contains(e.target) && !dropdown.contains(e.target)) {
            window._animateClose(dropdown);
        }
    });

    // Toggle dropdown on button click
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const willOpen = dropdown.classList.contains('hidden');
        if (willOpen) {
          window._closeAllDropdownsExcept(dropdown);
          window._animateOpen(dropdown);
        } else {
          window._animateClose(dropdown);
        }
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
});


// Property dropdown toggle functionality (desktop)
document.addEventListener('DOMContentLoaded', () => {
  const propBtn  = document.getElementById('propertyMenuButton');
  const propMenu = document.getElementById('propertyDropdown');

  if (!propBtn || !propMenu) return; // IDs missing in Blade

  window._registerDropdownPair(propBtn, propMenu);

  const openMenu = () => {
    window._closeAllDropdownsExcept(propMenu);
    window._animateOpen(propMenu);
    propBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    window._animateClose(propMenu, () => { /* no-op */ });
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

  window._registerDropdownPair(epcBtn, epcMenu);

  const openMenu = () => {
    window._closeAllDropdownsExcept(epcMenu);
    window._animateOpen(epcMenu);
    epcBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    window._animateClose(epcMenu, () => {});
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

  window._registerDropdownPair(mortBtn, mortMenu);

  const openMenu = () => {
    window._closeAllDropdownsExcept(mortMenu);
    window._animateOpen(mortMenu);
    mortBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    window._animateClose(mortMenu, () => {});
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

  window._registerDropdownPair(calcBtn, calcMenu);

  const openMenu = () => {
    window._closeAllDropdownsExcept(calcMenu);
    window._animateOpen(calcMenu);
    calcBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    window._animateClose(calcMenu, () => {});
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

// Economic Indicators dropdown toggle functionality (desktop)
document.addEventListener('DOMContentLoaded', () => {
  const ecoBtn  = document.getElementById('economicsMenuButton');
  const ecoMenu = document.getElementById('economicsDropdown');

  if (!ecoBtn || !ecoMenu) return;

  window._registerDropdownPair(ecoBtn, ecoMenu);

  const openMenu = () => {
    window._closeAllDropdownsExcept(ecoMenu);
    window._animateOpen(ecoMenu);
    ecoBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    window._animateClose(ecoMenu, () => {});
    ecoBtn.setAttribute('aria-expanded', 'false');
  };

  ecoBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isHidden = ecoMenu.classList.contains('hidden');
    isHidden ? openMenu() : closeMenu();
  });

  document.addEventListener('click', (e) => {
    const open = !ecoMenu.classList.contains('hidden');
    if (!open) return;
    if (!ecoMenu.contains(e.target) && !ecoBtn.contains(e.target)) closeMenu();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });
});

// Social Housing dropdown toggle functionality (desktop) - mirrors others
document.addEventListener('DOMContentLoaded', () => {
  const shBtn  = document.getElementById('socialHousingMenuButton');
  const shMenu = document.getElementById('socialHousingDropdown');

  if (!shBtn || !shMenu) return; // IDs missing in Blade

  window._registerDropdownPair(shBtn, shMenu);

  const openMenu = () => {
    window._closeAllDropdownsExcept(shMenu);
    window._animateOpen(shMenu);
    shBtn.setAttribute('aria-expanded', 'true');
  };

  const closeMenu = () => {
    window._animateClose(shMenu, () => {});
    shBtn.setAttribute('aria-expanded', 'false');
  };

  shBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isHidden = shMenu.classList.contains('hidden');
    isHidden ? openMenu() : closeMenu();
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    const open = !shMenu.classList.contains('hidden');
    if (!open) return;
    if (!shMenu.contains(e.target) && !shBtn.contains(e.target)) closeMenu();
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
    window._registerDropdownPair(propBtn, propMenu);
    propBtn.addEventListener('click', () => {
      const willOpen = propMenu.classList.contains('hidden');
      if (willOpen) { window._closeAllDropdownsExcept(propMenu); window._animateOpen(propMenu); }
      else { window._animateClose(propMenu); }
      propBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  }

  // Mobile EPC submenu toggle - mirrors Property submenu logic
  const epcBtn = document.getElementById('mobileEpcBtn');
  const epcMenu = document.getElementById('mobileEpcMenu');

  if (epcBtn && epcMenu) {
    window._registerDropdownPair(epcBtn, epcMenu);
    epcBtn.addEventListener('click', () => {
      const willOpen = epcMenu.classList.contains('hidden');
      if (willOpen) { window._closeAllDropdownsExcept(epcMenu); window._animateOpen(epcMenu); }
      else { window._animateClose(epcMenu); }
      epcBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  }

  // Mobile Mortgages submenu toggle - mirrors other mobile submenu logic
  const mortMobileBtn = document.getElementById('mobileMortgagesBtn');
  const mortMobileMenu = document.getElementById('mobileMortgagesMenu');

  if (mortMobileBtn && mortMobileMenu) {
    window._registerDropdownPair(mortMobileBtn, mortMobileMenu);
    mortMobileBtn.addEventListener('click', () => {
      const willOpen = mortMobileMenu.classList.contains('hidden');
      if (willOpen) { window._closeAllDropdownsExcept(mortMobileMenu); window._animateOpen(mortMobileMenu); }
      else { window._animateClose(mortMobileMenu); }
      mortMobileBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  }

  // Mobile Calculators submenu toggle
  const calcMobileBtn = document.getElementById('mobileCalculatorsBtn');
  const calcMobileMenu = document.getElementById('mobileCalculatorsMenu');

  if (calcMobileBtn && calcMobileMenu) {
    window._registerDropdownPair(calcMobileBtn, calcMobileMenu);
    calcMobileBtn.addEventListener('click', () => {
      const willOpen = calcMobileMenu.classList.contains('hidden');
      if (willOpen) { window._closeAllDropdownsExcept(calcMobileMenu); window._animateOpen(calcMobileMenu); }
      else { window._animateClose(calcMobileMenu); }
      calcMobileBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  }

  // Mobile Economic Indicators submenu toggle check
  const ecoMobileBtn = document.getElementById('mobileIndicatorsBtn');
  const ecoMobileMenu = document.getElementById('mobileIndicatorsMenu');

  if (ecoMobileBtn && ecoMobileMenu) {
    window._registerDropdownPair(ecoMobileBtn, ecoMobileMenu);
    ecoMobileBtn.addEventListener('click', () => {
      const willOpen = ecoMobileMenu.classList.contains('hidden');
      if (willOpen) { window._closeAllDropdownsExcept(ecoMobileMenu); window._animateOpen(ecoMobileMenu); }
      else { window._animateClose(ecoMobileMenu); }
      ecoMobileBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
  }

  // Mobile Social Housing submenu toggle
  const shMobileBtn = document.getElementById('mobileSocialHousingBtn');
  const shMobileMenu = document.getElementById('mobileSocialHousingMenu');

  if (shMobileBtn && shMobileMenu) {
    window._registerDropdownPair(shMobileBtn, shMobileMenu);
    shMobileBtn.addEventListener('click', () => {
      const willOpen = shMobileMenu.classList.contains('hidden');
      if (willOpen) { window._closeAllDropdownsExcept(shMobileMenu); window._animateOpen(shMobileMenu); }
      else { window._animateClose(shMobileMenu); }
      shMobileBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
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
