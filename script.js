/**
 * Radhe Fashion — Minimalist Ladies' Kurti & Ethnic Wear Boutique
 * Vanilla JavaScript (No frameworks, pure native ES6+)
 * 
 * Features:
 * 1. Dynamic copyright year
 * 2. Mobile navigation hamburger toggle
 * 3. Smooth section scroll & active link scrollspy
 * 4. Intersection Observer scroll-reveal animations
 * 5. Showcase collection preview modal with WhatsApp direct link
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // =========================================================================
  // 1. Dynamic Copyright Year
  // =========================================================================
  const currentYearEl = document.getElementById('currentYear');
  if (currentYearEl) {
    currentYearEl.textContent = new Date().getFullYear();
  }

  // =========================================================================
  // 2. Mobile Navigation Menu Toggle
  // =========================================================================
  const mobileToggleBtn = document.getElementById('mobileMenuToggle');
  const mobileNav = document.getElementById('mobileNav');
  const mobileLinks = document.querySelectorAll('.mobile-link');

  const toggleMobileMenu = () => {
    const isExpanded = mobileToggleBtn.getAttribute('aria-expanded') === 'true';
    const nextState = !isExpanded;

    mobileToggleBtn.setAttribute('aria-expanded', String(nextState));
    mobileToggleBtn.classList.toggle('is-active', nextState);
    mobileNav.classList.toggle('is-open', nextState);
    mobileNav.setAttribute('aria-hidden', String(!nextState));
  };

  const closeMobileMenu = () => {
    mobileToggleBtn.setAttribute('aria-expanded', 'false');
    mobileToggleBtn.classList.remove('is-active');
    mobileNav.classList.remove('is-open');
    mobileNav.setAttribute('aria-hidden', 'true');
  };

  if (mobileToggleBtn && mobileNav) {
    mobileToggleBtn.addEventListener('click', toggleMobileMenu);

    // Close menu when any mobile link is clicked
    mobileLinks.forEach(link => {
      link.addEventListener('click', closeMobileMenu);
    });

    // Close menu when clicking outside
    document.addEventListener('click', (event) => {
      if (
        mobileNav.classList.contains('is-open') &&
        !mobileNav.contains(event.target) &&
        !mobileToggleBtn.contains(event.target)
      ) {
        closeMobileMenu();
      }
    });
  }

  // =========================================================================
  // 3. Active Link Highlight (Scrollspy)
  // =========================================================================
  const sections = document.querySelectorAll('section[id]');
  const desktopNavLinks = document.querySelectorAll('.nav-links .nav-link');

  const handleScrollspy = () => {
    const scrollY = window.pageYOffset || document.documentElement.scrollTop;
    const headerOffset = 120; // accounting for sticky header height

    sections.forEach(section => {
      const sectionHeight = section.offsetHeight;
      const sectionTop = section.offsetTop - headerOffset;
      const sectionId = section.getAttribute('id');

      if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
        desktopNavLinks.forEach(link => {
          if (link.getAttribute('href') === `#${sectionId}`) {
            link.classList.add('active');
          } else {
            link.classList.remove('active');
          }
        });
      }
    });
  };

  window.addEventListener('scroll', handleScrollspy, { passive: true });

  // =========================================================================
  // 4. Smooth Viewport Reveal Animations (Intersection Observer)
  // =========================================================================
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const revealElements = document.querySelectorAll('.reveal');

  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    // If user prefers reduced motion or browser doesn't support observer, reveal immediately
    revealElements.forEach(el => el.classList.add('is-revealed'));
  } else {
    const revealObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target); // Unobserve once revealed
          }
        });
      },
      {
        root: null,
        threshold: 0.12, // trigger when 12% is visible
        rootMargin: '0px 0px -40px 0px'
      }
    );

    revealElements.forEach(el => revealObserver.observe(el));
  }

  // =========================================================================
  // 5. Showcase Collection Quick-View Modal
  // =========================================================================
  const collectionModal = document.getElementById('collectionModal');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const modalImg = document.getElementById('modalImg');
  const modalTitle = document.getElementById('modalTitle');
  const modalDescription = document.getElementById('modalDescription');
  const modalWhatsAppBtn = document.getElementById('modalWhatsAppBtn');
  let lastActiveElement = null;

  // Placeholder phone number in international format
  const BUSINESS_PHONE_INTL = '919586914546';

  const openCollectionModal = (cardElement) => {
    const category = cardElement.getAttribute('data-category') || 'Ethnic Kurti';
    const description = cardElement.getAttribute('data-description') || '';
    const imageSrc = cardElement.getAttribute('data-image') || '';

    if (modalImg) {
      modalImg.src = imageSrc;
      modalImg.alt = `Radhe Fashion - ${category}`;
    }
    if (modalTitle) {
      modalTitle.textContent = category;
    }
    if (modalDescription) {
      modalDescription.textContent = description;
    }

    // Prepare WhatsApp inquiry link specific to this selected category
    if (modalWhatsAppBtn) {
      const message = `Hello Radhe Fashion! I am interested in your ${category} collection. Could you share available sizes and pricing?`;
      modalWhatsAppBtn.href = `https://wa.me/${BUSINESS_PHONE_INTL}?text=${encodeURIComponent(message)}`;
    }

    lastActiveElement = document.activeElement;
    collectionModal.classList.add('is-active');
    collectionModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden'; // lock background scrolling

    // Move focus inside dialog
    if (modalCloseBtn) {
      modalCloseBtn.focus();
    }
  };

  const closeCollectionModal = () => {
    if (!collectionModal) return;
    collectionModal.classList.remove('is-active');
    collectionModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = ''; // restore scrolling

    // Return focus to triggering element
    if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
      lastActiveElement.focus();
    }
  };

  // Attach click events to collection cards
  const collectionCards = document.querySelectorAll('.collection-card');
  collectionCards.forEach(card => {
    card.addEventListener('click', (event) => {
      // Avoid re-triggering if clicking an internal link
      openCollectionModal(card);
    });

    // Support keyboard activation (Enter or Space)
    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
    card.setAttribute('aria-label', `View details for ${card.getAttribute('data-category')}`);
    card.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        openCollectionModal(card);
      }
    });
  });

  if (modalCloseBtn) {
    modalCloseBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      closeCollectionModal();
    });
  }

  if (collectionModal) {
    // Close modal on backdrop click
    collectionModal.addEventListener('click', (event) => {
      if (event.target === collectionModal) {
        closeCollectionModal();
      }
    });

    // Close on Escape key press
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && collectionModal.classList.contains('is-active')) {
        closeCollectionModal();
      }
    });
  }

  // Log confirmation in development
  console.log('Radhe Fashion: Website initialized cleanly with Vanilla JS.');
});
