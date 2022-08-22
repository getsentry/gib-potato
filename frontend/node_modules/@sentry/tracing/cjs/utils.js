Object.defineProperty(exports, '__esModule', { value: true });

var hub = require('@sentry/hub');
var utils = require('@sentry/utils');

/**
 * Determines if tracing is currently enabled.
 *
 * Tracing is enabled when at least one of `tracesSampleRate` and `tracesSampler` is defined in the SDK config.
 */
function hasTracingEnabled(
  maybeOptions,
) {
  var client = hub.getCurrentHub().getClient();
  var options = maybeOptions || (client && client.getOptions());
  return !!options && ('tracesSampleRate' in options || 'tracesSampler' in options);
}

/** Grabs active transaction off scope, if any */
function getActiveTransaction(maybeHub) {
  var hub$1 = maybeHub || hub.getCurrentHub();
  var scope = hub$1.getScope();
  return scope && (scope.getTransaction() );
}

/**
 * Converts from milliseconds to seconds
 * @param time time in ms
 */
function msToSec(time) {
  return time / 1000;
}

/**
 * Converts from seconds to milliseconds
 * @param time time in seconds
 */
function secToMs(time) {
  return time * 1000;
}

exports.TRACEPARENT_REGEXP = utils.TRACEPARENT_REGEXP;
exports.extractTraceparentData = utils.extractTraceparentData;
exports.stripUrlQueryAndFragment = utils.stripUrlQueryAndFragment;
exports.getActiveTransaction = getActiveTransaction;
exports.hasTracingEnabled = hasTracingEnabled;
exports.msToSec = msToSec;
exports.secToMs = secToMs;
//# sourceMappingURL=utils.js.map
