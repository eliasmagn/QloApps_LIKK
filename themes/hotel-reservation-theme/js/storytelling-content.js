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

  ready(function () {
    var containers = document.querySelectorAll('[data-kl-storytelling-resource]');
    if (!containers || !containers.length) {
      return;
    }

    for (var i = 0; i < containers.length; i += 1) {
      (function (container) {
        var resource = container.getAttribute('data-kl-storytelling-resource');
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

            var slotPayload = data.testimonials[resource];
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

            var slotPayload = data.faq[resource];
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
