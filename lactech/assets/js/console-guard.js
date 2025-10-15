/**
 * Console Guard
 * - Disables console.log/info/debug in production by default
 * - Keeps warn/error but redacts sensitive-looking tokens
 * - Enable verbose logs with: localStorage.DEBUG = '1' or ?debug=1
 */
(function () {
  try {
    if (!window) return;

    var originalConsole = window.console || {};
    var noop = function () {};

    function getQueryParam(name) {
      var params = new URLSearchParams(window.location.search || '');
      return params.get(name);
    }

    function isLocalhost(host) {
      return (
        host === 'localhost' ||
        host === '127.0.0.1' ||
        host === '::1'
      );
    }

    function isDebugEnabled() {
      if (getQueryParam('debug') === '1' || getQueryParam('debug') === 'true') return true;
      if (localStorage && localStorage.DEBUG === '1') return true;
      return false;
    }

    function isProduction() {
      var host = (window.location && window.location.hostname) || '';
      // Treat anything that is not localhost as production
      return !isLocalhost(host);
    }

    function redact(value) {
      try {
        if (typeof value !== 'string') return value;
        var v = value;
        // Redact JWT-like tokens
        v = v.replace(/[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_\.\/=+]+/g, '[REDACTED_TOKEN]');
        // Redact bearer headers
        v = v.replace(/(Bearer\s+)[A-Za-z0-9-_\.\/=+]+/gi, '$1[REDACTED]');
        // Redact API keys
        v = v.replace(/(api[-_ ]?key\s*[:=]\s*)([^\s"']+)/gi, '$1[REDACTED]');
        // Redact Supabase anon keys commonly starting with eyJ
        v = v.replace(/eyJ[a-zA-Z0-9_-]{10,}/g, '[REDACTED_KEY]');
        // Redact full Supabase project URLs
        v = v.replace(/https?:\/\/([a-z0-9-]+)\.supabase\.co/gi, 'https://[REDACTED].supabase.co');
        return v;
      } catch (_) {
        return value;
      }
    }

    function redactArgs(args) {
      try {
        return Array.prototype.map.call(args, function (arg) {
          if (typeof arg === 'string') return redact(arg);
          if (arg && typeof arg === 'object') {
            try {
              // Best-effort shallow clone with redaction for string values
              var clone = Array.isArray(arg) ? [] : {};
              for (var k in arg) {
                if (!Object.prototype.hasOwnProperty.call(arg, k)) continue;
                var val = arg[k];
                clone[k] = typeof val === 'string' ? redact(val) : val;
              }
              return clone;
            } catch (_) {
              return arg;
            }
          }
          return arg;
        });
      } catch (_) {
        return args;
      }
    }

    var production = isProduction();
    var debugEnabled = isDebugEnabled();

    var safeConsole = {
      log: noop,
      info: noop,
      debug: noop,
      warn: function () {
        try {
          var args = production ? redactArgs(arguments) : arguments;
          (originalConsole.warn || originalConsole.log || noop).apply(originalConsole, args);
        } catch (_) {}
      },
      error: function () {
        try {
          var args = production ? redactArgs(arguments) : arguments;
          (originalConsole.error || originalConsole.log || noop).apply(originalConsole, args);
        } catch (_) {}
      },
    };

    // If not production or explicitly enabled, keep original logging
    if (!production || debugEnabled) {
      // Expose a toggle helper
      window.setDebugLogging = function (enabled) {
        try {
          localStorage.DEBUG = enabled ? '1' : '0';
          window.location.reload();
        } catch (_) {}
      };
      return; // leave console intact
    }

    // Override console methods in production
    try {
      window.console = window.console || {};
      window.console.log = safeConsole.log;
      window.console.info = safeConsole.info;
      window.console.debug = safeConsole.debug;
      window.console.warn = safeConsole.warn;
      window.console.error = safeConsole.error;
    } catch (_) {}

    // Provide a global to re-enable without reload if needed
    window.setDebugLogging = function (enabled) {
      try {
        localStorage.DEBUG = enabled ? '1' : '0';
        window.location.reload();
      } catch (_) {}
    };
  } catch (_) {
    // Last resort: do nothing on guard failure
  }
})();


