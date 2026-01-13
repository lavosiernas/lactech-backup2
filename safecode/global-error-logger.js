/**
 * SafeCode IDE - Global Error Logger
 * Captures all unhandled errors and logs them with formatting.
 */

(function () {
    console.log('🛡️ Global Error Logger initialized');

    // Handle standard errors
    window.onerror = function (message, source, lineno, colno, error) {
        const timestamp = new Date().toLocaleTimeString();
        console.log(`%c[GLOBAL ERROR] ${timestamp}%c ${message}`, 'background: #ef4444; color: white; padding: 2px 5px; border-radius: 3px;', '', {
            source,
            line: lineno,
            column: colno,
            error: error
        });

        // Return false to let the browser continue its normal error handling
        return false;
    };

    // Handle unhandled promise rejections
    window.onunhandledrejection = function (event) {
        const timestamp = new Date().toLocaleTimeString();
        console.log(`%c[PROMISE REJECTION] ${timestamp}%c ${event.reason}`, 'background: #f59e0b; color: white; padding: 2px 5px; border-radius: 3px;', '', {
            reason: event.reason,
            promise: event.promise
        });
    };

    // Optional: Intercept console.error
    const originalConsoleError = console.error;
    console.error = function (...args) {
        const timestamp = new Date().toLocaleTimeString();
        originalConsoleError.apply(console, [
            `%c[INTERNAL ERROR] ${timestamp}%c`,
            'background: #7f1d1d; color: white; padding: 2px 5px; border-radius: 3px;',
            '',
            ...args
        ]);
    };

    console.log('✅ Global error traps active.');
})();
