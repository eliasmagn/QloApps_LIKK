(function (window, document) {
  'use strict';

  if (!window || !document) {
    return;
  }

  var pending = [];
  var ready = false;
  var doc = document;

  function normalise(definition) {
    if (!definition) {
      return null;
    }

    if (typeof definition === 'string') {
      definition = { src: definition };
    }

    var src = typeof definition.src === 'string' ? definition.src.trim() : '';
    var inline = typeof definition.inline === 'string' ? definition.inline : '';

    if (!src && !inline) {
      return null;
    }

    return {
      src: src || null,
      inline: inline ? inline : null,
      async: definition.async === true,
      module: definition.module === true || definition.type === 'module',
      type: definition.type && definition.type !== 'module' ? definition.type : null,
      target: definition.target === 'head' ? 'head' : 'body',
      immediate: definition.immediate === true
    };
  }

  function insertScript(def) {
    if (def.inline) {
      var inlineScript = doc.createElement('script');
      inlineScript.type = def.type || (def.module ? 'module' : 'text/javascript');
      inlineScript.text = def.inline;
      (def.target === 'head' ? doc.head : doc.body || doc.head).appendChild(inlineScript);
      return;
    }

    if (!def.src) {
      return;
    }

    var script = doc.createElement('script');
    script.src = def.src;

    if (def.module) {
      script.type = 'module';
    } else if (def.type) {
      script.type = def.type;
    }

    if (def.async) {
      script.async = true;
    } else {
      script.defer = true;
    }

    (def.target === 'head' ? doc.head : doc.body || doc.head).appendChild(script);
  }

  function flushQueue() {
    var entry;
    while ((entry = pending.shift())) {
      insertScript(entry);
    }
  }

  function queueScript(definition) {
    var entry = normalise(definition);
    if (!entry) {
      return;
    }

    if (entry.immediate) {
      insertScript(entry);
      return;
    }

    pending.push(entry);
    if (ready) {
      flushQueue();
    }
  }

  function hydrateFromNodes(nodes) {
    if (!nodes || !nodes.length) {
      return;
    }

    for (var i = 0; i < nodes.length; i += 1) {
      var node = nodes[i];
      var definition = {
        src: node.getAttribute('data-src'),
        async: node.hasAttribute('data-kl-async'),
        module: node.getAttribute('data-module') === 'true' || node.getAttribute('data-type') === 'module',
        type: node.getAttribute('data-type'),
        target: node.getAttribute('data-target')
      };

      if (node.hasAttribute('data-inline')) {
        definition.inline = node.textContent || '';
      }

      if (node.hasAttribute('data-immediate')) {
        definition.immediate = true;
      }

      queueScript(definition);

      if (node.parentNode) {
        node.parentNode.removeChild(node);
      }
    }
  }

  function hydrateTemplates(selector) {
    var nodes = doc.querySelectorAll(selector || '[data-kl-storytelling-defer]');
    hydrateFromNodes(nodes);
  }

  function onReady() {
    ready = true;
    hydrateTemplates();
    flushQueue();
  }

  var bootstrapQueue = window.klStorytellingDeferQueue;
  if (Array.isArray(bootstrapQueue)) {
    for (var i = 0; i < bootstrapQueue.length; i += 1) {
      queueScript(bootstrapQueue[i]);
    }
  }

  if (doc.readyState === 'complete') {
    onReady();
  } else {
    window.addEventListener('load', onReady, { once: true });
  }

  var publicApi = {
    push: queueScript,
    hydrate: hydrateTemplates,
    flush: function () {
      flushQueue();
    }
  };

  window.klStorytellingDefer = publicApi;

  var queueInterface = [];
  queueInterface.push = queueScript;
  queueInterface.hydrate = hydrateTemplates;
  queueInterface.flush = flushQueue;
  window.klStorytellingDeferQueue = queueInterface;
})(window, document);
