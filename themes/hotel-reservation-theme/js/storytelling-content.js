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

  var hasOwn = Object.prototype.hasOwnProperty;

  function normaliseKey(value) {
    if (!value || typeof value !== 'string') {
      return '';
    }

    return value.trim().toLowerCase();
  }

  function fetchJson(url, onSuccess) {
    if (!url || typeof url !== 'string') {
      return;
    }

    fetch(url, { credentials: 'same-origin' })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Request failed');
        }

        return response.json();
      })
      .then(function (data) {
        if (typeof onSuccess === 'function') {
          onSuccess(data);
        }
      })
      .catch(function () {
        /* no-op: keep server-rendered fallback */
      });
  }

  function updateSlot(container, slotName, payload) {
    if (!container || !slotName) {
      return;
    }

    var target = container.querySelector('[data-kl-storytelling-slot="' + slotName + '"]');
    if (!target) {
      return;
    }

    if (!payload || !payload.content) {
      return;
    }

    target.innerHTML = payload.content;
  }

  function resolveSlotPayload(data, slotName, resourceKey) {
    if (!data || typeof data !== 'object' || !slotName) {
      return null;
    }

    var slotGroup = data[slotName];
    if (!slotGroup || typeof slotGroup !== 'object') {
      return null;
    }

    var normalisedResource = resourceKey ? normaliseKey(resourceKey) : '';

    if (normalisedResource) {
      if (hasOwn.call(slotGroup, normalisedResource) && slotGroup[normalisedResource]) {
        return slotGroup[normalisedResource];
      }

      for (var candidate in slotGroup) {
        if (!hasOwn.call(slotGroup, candidate)) {
          continue;
        }

        if (typeof candidate === 'string' && candidate.toLowerCase() === normalisedResource) {
          if (slotGroup[candidate]) {
            return slotGroup[candidate];
          }
        }
      }
    }

    if (Array.isArray(data.resource_groups)) {
      for (var i = 0; i < data.resource_groups.length; i += 1) {
        var groupKey = data.resource_groups[i];
        if (!groupKey || typeof groupKey !== 'string') {
          continue;
        }

        if (hasOwn.call(slotGroup, groupKey) && slotGroup[groupKey]) {
          return slotGroup[groupKey];
        }
      }
    }

    for (var fallbackKey in slotGroup) {
      if (!hasOwn.call(slotGroup, fallbackKey)) {
        continue;
      }

      if (slotGroup[fallbackKey]) {
        return slotGroup[fallbackKey];
      }
    }

    return null;
  }

  ready(function () {
    var containers = document.querySelectorAll('[data-kl-storytelling-resource]');
    if (!containers || !containers.length) {
      return;
    }

    for (var i = 0; i < containers.length; i += 1) {
      (function (container) {
        var resourceAttr = container.getAttribute('data-kl-storytelling-resource');
        var resource = normaliseKey(resourceAttr);
        if (!resource) {
          return;
        }

        var testimonialsTarget = container.querySelector('[data-kl-storytelling-slot="testimonials"]');
        if (testimonialsTarget) {
          var testimonialsEndpoint = container.getAttribute('data-kl-storytelling-testimonials-endpoint');
          fetchJson(testimonialsEndpoint, function (data) {
            if (!data || !data.testimonials) {
              return;
            }

            var slotPayload = resolveSlotPayload(data, 'testimonials', resource);
            if (!slotPayload) {
              return;
            }

            updateSlot(container, 'testimonials', slotPayload);
          });
        }

        var faqTarget = container.querySelector('[data-kl-storytelling-slot="faq"]');
        if (faqTarget) {
          var faqEndpoint = container.getAttribute('data-kl-storytelling-faq-endpoint');
          fetchJson(faqEndpoint, function (data) {
            if (!data || !data.faq) {
              return;
            }

            var slotPayload = resolveSlotPayload(data, 'faq', resource);
            if (!slotPayload) {
              return;
            }

            updateSlot(container, 'faq', slotPayload);
          });
        }
      })(containers[i]);
    }
  });
})(window, document);
