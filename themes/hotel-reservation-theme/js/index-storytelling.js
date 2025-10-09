/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*/

(function (window, document) {
  'use strict';

  if (!window || !document) {
    return;
  }

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  function normaliseId(value) {
    if (!value || typeof value !== 'string') {
      return '';
    }

    return value.replace(/^#/, '').trim();
  }

  ready(function () {
    var nav = document.querySelector('[data-kl-storytelling-home-nav]');
    if (!nav) {
      return;
    }

    var items = nav.querySelectorAll('[data-kl-storytelling-target]');
    if (!items || !items.length) {
      return;
    }

    var prefersReducedMotion = false;
    if (window.matchMedia) {
      try {
        prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      } catch (error) {
        prefersReducedMotion = false;
      }
    }

    var entries = [];

    function setActive(id) {
      if (!id) {
        return;
      }

      for (var i = 0; i < entries.length; i += 1) {
        var entry = entries[i];
        if (!entry || !entry.navItem) {
          continue;
        }

        if (entry.sectionId === id) {
          entry.navItem.classList.add('is-active');
        } else {
          entry.navItem.classList.remove('is-active');
        }
      }
    }

    function handleClick(section) {
      return function (event) {
        if (event) {
          event.preventDefault();
        }

        if (!section) {
          return;
        }

        var behavior = prefersReducedMotion ? 'auto' : 'smooth';
        if (typeof section.scrollIntoView === 'function') {
          try {
            section.scrollIntoView({ behavior: behavior, block: 'start' });
          } catch (error) {
            section.scrollIntoView();
          }
        } else {
          window.location.hash = section.id;
        }

        if (section.id) {
          setActive(normaliseId(section.id));
        }
      };
    }

    for (var index = 0; index < items.length; index += 1) {
      var navItem = items[index];
      if (!navItem) {
        continue;
      }

      var targetId = normaliseId(navItem.getAttribute('data-kl-storytelling-target'));
      if (!targetId) {
        continue;
      }

      var section = document.getElementById(targetId);
      if (!section) {
        continue;
      }

      var link = navItem.querySelector('a');
      if (link) {
        link.addEventListener('click', handleClick(section));
      }

      entries.push({
        section: section,
        sectionId: targetId,
        navItem: navItem
      });
    }

    if (!entries.length) {
      return;
    }

    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (observerEntries) {
        if (!observerEntries) {
          return;
        }

        for (var i = 0; i < observerEntries.length; i += 1) {
          var observerEntry = observerEntries[i];
          if (!observerEntry || !observerEntry.isIntersecting || !observerEntry.target) {
            continue;
          }

          var sectionId = normaliseId(observerEntry.target.id);
          if (!sectionId) {
            continue;
          }

          setActive(sectionId);
        }
      }, {
        rootMargin: '-40% 0px -50% 0px',
        threshold: 0.25
      });

      for (var j = 0; j < entries.length; j += 1) {
        observer.observe(entries[j].section);
      }
      if (entries[0] && entries[0].sectionId) {
        setActive(entries[0].sectionId);
      }
    } else {
      setActive(entries[0].sectionId);
    }
  });
})(window, document);
