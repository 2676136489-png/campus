/* Campus Circle front-end engine
 * Theme, smooth scroll, reveal, composer, modal, toast and route polish.
 * Business actions stay in app.js; this layer never rewrites them.
 */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var themeKey = 'campus-theme';

  function currentTheme() {
    var stored = null;
    try {
      stored = window.localStorage ? localStorage.getItem(themeKey) : null;
    } catch (e) {
      stored = null;
    }
    if (stored === 'light' || stored === 'dark') {
      return stored;
    }
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    var meta = doc.querySelector('meta[name="theme-color"]');
    if (meta) {
      meta.setAttribute('content', theme === 'dark' ? '#0d1015' : '#f4f7fa');
    }
    try {
      if (window.localStorage) {
        localStorage.setItem(themeKey, theme);
      }
    } catch (e) {}
  }

  function initTheme() {
    applyTheme(currentTheme());
    var syncSwitch = function (button) {
      button.setAttribute('aria-checked', root.getAttribute('data-theme') === 'dark' ? 'true' : 'false');
    };
    doc.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      syncSwitch(button);
      button.addEventListener('click', function () {
        applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        syncSwitch(button);
      });
    });
  }

  var lenis = null;
  function initLenis() {
    if (reduceMotion || typeof window.Lenis !== 'function') {
      return;
    }
    lenis = new Lenis({
      duration: 1.05,
      easing: function (t) {
        return Math.min(1, 1.001 - Math.pow(2, -10 * t));
      },
      smoothWheel: true,
      syncTouch: true,
      touchMultiplier: 1.15
    });
    window.CampusLenis = lenis;

    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    var nativeScrollIntoView = Element.prototype.scrollIntoView;
    Element.prototype.scrollIntoView = function (options) {
      if (options && options.behavior === 'smooth' && lenis) {
        lenis.scrollTo(this, { offset: -88, duration: 1.0 });
        return;
      }
      nativeScrollIntoView.call(this, options);
    };

    var closeButton = doc.querySelector('[data-composer-close]');
    if (closeButton) {
      lenis.on('scroll', function () {
        var composer = doc.querySelector('[data-composer]');
        if (composer && composer.classList.contains('is-open') && !reduceMotion) {
          lenis.stop();
        }
      });
    }
  }

  function initReveal() {
    var items = doc.querySelectorAll('.reveal');
    if (!items.length) {
      return;
    }
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (item) {
        item.classList.add('is-visible');
      });
      return;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var delay = entry.target.getAttribute('data-reveal-delay');
          if (delay) {
            setTimeout(function () {
              entry.target.classList.add('is-visible');
            }, parseInt(delay, 10) || 0);
          } else {
            entry.target.classList.add('is-visible');
          }
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    items.forEach(function (item) {
      observer.observe(item);
    });
  }

  function showToast(message, type) {
    if (window.showToast && typeof window.showToast === 'function') {
      return;
    }
    var region = doc.getElementById('toastRegion');
    if (!region) {
      region = doc.createElement('div');
      region.className = 'toast-region';
      region.id = 'toastRegion';
      doc.body.appendChild(region);
    }
    var toast = doc.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    var icon = doc.createElement('span');
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = type === 'error' ? '!' : 'OK';
    var copy = doc.createElement('span');
    copy.textContent = message;
    toast.appendChild(icon);
    toast.appendChild(copy);
    region.appendChild(toast);
    requestAnimationFrame(function () {
      toast.classList.add('is-visible');
    });
    setTimeout(function () {
      toast.classList.remove('is-visible');
      setTimeout(function () {
        if (toast.parentNode === region) {
          region.removeChild(toast);
        }
      }, 260);
    }, 2600);
  }
  window.CampusToast = showToast;

  function initTopbar() {
    var header = doc.getElementById('siteHeader');
    if (!header) {
      return;
    }
    var update = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', update, { passive: true });
    update();

    var toggle = doc.querySelector('[data-nav-toggle]');
    var nav = doc.getElementById('siteNav');
    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      doc.addEventListener('click', function (event) {
        if (nav.classList.contains('is-open') && !nav.contains(event.target) && !toggle.contains(event.target)) {
          nav.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    }
  }

  function openComposer() {
    var composer = doc.querySelector('[data-composer]');
    if (!composer) {
      return;
    }
    window.__composerLastFocus = doc.activeElement;
    composer.classList.add('is-open');
    composer.removeAttribute('hidden');
    var textarea = composer.querySelector('textarea');
    if (textarea) {
      setTimeout(function () {
        textarea.focus();
      }, 260);
    }
    if (lenis) {
      lenis.stop();
    }
    doc.body.classList.add('composer-open');
  }

  function closeComposer() {
    var composer = doc.querySelector('[data-composer]');
    if (!composer) {
      return;
    }
    composer.classList.remove('is-open');
    doc.body.classList.remove('composer-open');
    if (lenis) {
      lenis.start();
    }
    if (window.__composerLastFocus && window.__composerLastFocus.focus) {
      window.__composerLastFocus.focus();
    }
  }

  function initComposer() {
    var composer = doc.querySelector('[data-composer]');
    var openers = doc.querySelectorAll('[data-open-composer]');
    var closers = doc.querySelectorAll('[data-composer-close]');
    if (!composer) {
      return;
    }
    openers.forEach(function (button) {
      button.addEventListener('click', function () {
        openComposer();
      });
    });
    closers.forEach(function (button) {
      button.addEventListener('click', closeComposer);
    });
    composer.addEventListener('click', function (event) {
      if (event.target === composer) {
        closeComposer();
      }
    });
    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && composer.classList.contains('is-open')) {
        closeComposer();
      }
    });
    composer.addEventListener('keydown', function (event) {
      if (event.key !== 'Tab') {
        return;
      }
      var focusables = composer.querySelectorAll('button, [href], input, textarea, select, [tabindex]:not([tabindex="-1"])');
      if (!focusables.length) {
        return;
      }
      var first = focusables[0];
      var last = focusables[focusables.length - 1];
      if (event.shiftKey && doc.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && doc.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    var form = composer.querySelector('[data-composer-form]');
    var frame = doc.querySelector('[data-composer-frame]');
    var errorBox = composer.querySelector('[data-composer-error]');
    var submit = composer.querySelector('[data-composer-submit]');
    if (!form || !frame) {
      return;
    }
    var composerTab = '';
    try { composerTab = sessionStorage.getItem('campus_tab_token') || ''; } catch (e) {}
    if (composerTab) {
      form.action = 'p_publishDynamic.php?tab=' + encodeURIComponent(composerTab);
    }
    form.addEventListener('submit', function () {
      if (submit) {
        submit.classList.add('is-loading');
      }
      if (errorBox) {
        errorBox.hidden = true;
      }
    });
    frame.addEventListener('load', function () {
      if (!form || !submit) {
        return;
      }
      try {
        var body = frame.contentDocument && frame.contentDocument.body;
        var success = body && body.querySelector('.alert-success');
        var error = body && body.querySelector('.alert-error');
        if (success) {
          var text = success.textContent.replace(/\s+/g, ' ').trim();
          closeComposer();
          if (window.showToast) {
            window.showToast(text || '动态发布成功。', 'success');
          } else {
            showToast(text || '动态发布成功。', 'success');
          }
          form.reset();
          var grid = composer.querySelector('[data-composer-photos]');
          if (grid) {
            grid.hidden = true;
            grid.innerHTML = '';
          }
          setTimeout(function () {
            if (window.location.search.indexOf('p_dynamics') === -1 && window.location.pathname.indexOf('p_dynamics') === -1) {
              window.location.href = 'p_dynamics.php';
            } else {
              window.location.reload();
            }
          }, 900);
        } else if (error) {
          var errorText = error.textContent.replace(/\s+/g, ' ').trim();
          if (errorBox) {
            errorBox.textContent = errorText || '发布失败，请稍后重试。';
            errorBox.hidden = false;
          }
        } else {
          if (errorBox) {
            errorBox.textContent = '发布失败，请稍后重试。';
            errorBox.hidden = false;
          }
        }
      } catch (e) {
        if (errorBox) {
          errorBox.textContent = '发布失败，请稍后重试。';
          errorBox.hidden = false;
        }
      }
      submit.classList.remove('is-loading');
      submit.disabled = false;
    });
  }

  function initComposerPhotos() {
    var input = doc.querySelector('[data-composer-photo-input]');
    var grid = doc.querySelector('[data-composer-photos]');
    if (!input || !grid) {
      return;
    }
    input.addEventListener('change', function () {
      var files = Array.prototype.slice.call(input.files || []).slice(0, 3);
      grid.innerHTML = '';
      grid.hidden = files.length === 0;
      files.forEach(function (file) {
        var reader = new FileReader();
        reader.onload = function () {
          var item = doc.createElement('div');
          item.className = 'photo-preview-item';
          var img = doc.createElement('img');
          img.src = reader.result;
          img.alt = '';
          item.appendChild(img);
          grid.appendChild(item);
        };
        reader.readAsDataURL(file);
      });
    });
  }

  function initRoutePolish() {
    if (!('MutationObserver' in window)) {
      return;
    }
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType !== 1) {
            return;
          }
          var target = node.matches && node.matches('[data-paginate]') ? node : node.querySelector && node.querySelector('[data-paginate]');
          if (target && reduceMotion === false) {
            target.classList.add('route-enter');
          }
        });
      });
    });
    observer.observe(doc.body, { childList: true, subtree: true });
  }

  function initFocusScroll() {
    var params = new URLSearchParams(window.location.search);
    var focusId = params.get('focus');
    if (!focusId) {
      return;
    }
    var target = doc.getElementById('dynamic-' + focusId);
    if (!target) {
      return;
    }
    setTimeout(function () {
      if (lenis) {
        lenis.scrollTo(target, { offset: -88, duration: 1.2 });
      } else {
        target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
      }
      target.classList.add('is-focused');
      setTimeout(function () {
        target.classList.remove('is-focused');
      }, 3200);
    }, 260);
  }

  function initFeedPerformance() {
    var feed = doc.querySelector('[data-feed-virtual]');
    if (!feed || !('IntersectionObserver' in window)) {
      return;
    }
    var cards = feed.querySelectorAll('.post-card');
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-onscreen');
          var images = entry.target.querySelectorAll('img[data-src]');
          images.forEach(function (img) {
            img.src = img.getAttribute('data-src');
            img.removeAttribute('data-src');
          });
        }
      });
    }, { rootMargin: '320px 0px' });
    cards.forEach(function (card) {
      observer.observe(card);
    });
  }

  function init() {
    initTheme();
    initTopbar();
    initLenis();
    initReveal();
    initComposer();
    initComposerPhotos();
    initRoutePolish();
    initFocusScroll();
    initImageReveal();
    initRailToggle();
    initFeedPerformance();
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

  function initImageReveal() {
    document.querySelectorAll('.post-photo img').forEach(function (img) {
      var reveal = function () {
        img.classList.add('is-loaded');
      };
      if (img.complete && img.naturalWidth > 0) {
        reveal();
      } else {
        img.addEventListener('load', reveal);
      }
    });
  }
  function initRailToggle() {
    var shell = document.querySelector('.app-shell');
    var buttons = document.querySelectorAll('[data-rail-toggle]');
    if (!shell || !buttons.length) return;
    var saved = null;
    try { saved = localStorage.getItem('campus-rail'); } catch (e) {}
    if (saved === '1') shell.classList.add('is-rail-collapsed');
    var syncLabels = function () {
      var collapsed = shell.classList.contains('is-rail-collapsed');
      document.querySelectorAll('[data-rail-toggle-label]').forEach(function (label) {
        label.textContent = collapsed ? '展开侧边栏' : '收起侧边栏';
      });
    };
    syncLabels();
    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        shell.classList.toggle('is-rail-collapsed');
        try { localStorage.setItem('campus-rail', shell.classList.contains('is-rail-collapsed') ? '1' : '0'); } catch (e) {}
        syncLabels();
      });
    });
  }
