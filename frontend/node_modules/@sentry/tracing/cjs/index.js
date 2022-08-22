Object.defineProperty(exports, '__esModule', { value: true });

var hubextensions = require('./hubextensions.js');
var index = require('./integrations/index.js');
require('./browser/index.js');
var span = require('./span.js');
var spanstatus = require('./spanstatus.js');
var transaction = require('./transaction.js');
var idletransaction = require('./idletransaction.js');
var utils$1 = require('./utils.js');
var browsertracing = require('./browser/browsertracing.js');
var request = require('./browser/request.js');
var utils = require('@sentry/utils');

;
;

// Treeshakable guard to remove all code related to tracing

// Guard for tree
if (typeof __SENTRY_TRACING__ === 'undefined' || __SENTRY_TRACING__) {
  // We are patching the global object with our hub extension methods
  hubextensions.addExtensionMethods();
}

exports.addExtensionMethods = hubextensions.addExtensionMethods;
exports.startIdleTransaction = hubextensions.startIdleTransaction;
exports.Integrations = index;
exports.Span = span.Span;
exports.spanStatusfromHttpCode = span.spanStatusfromHttpCode;
Object.defineProperty(exports, 'SpanStatus', {
  enumerable: true,
  get: () => spanstatus.SpanStatus
});
exports.Transaction = transaction.Transaction;
exports.IdleTransaction = idletransaction.IdleTransaction;
exports.getActiveTransaction = utils$1.getActiveTransaction;
exports.hasTracingEnabled = utils$1.hasTracingEnabled;
exports.BROWSER_TRACING_INTEGRATION_ID = browsertracing.BROWSER_TRACING_INTEGRATION_ID;
exports.BrowserTracing = browsertracing.BrowserTracing;
exports.defaultRequestInstrumentationOptions = request.defaultRequestInstrumentationOptions;
exports.instrumentOutgoingRequests = request.instrumentOutgoingRequests;
exports.TRACEPARENT_REGEXP = utils.TRACEPARENT_REGEXP;
exports.extractTraceparentData = utils.extractTraceparentData;
exports.stripUrlQueryAndFragment = utils.stripUrlQueryAndFragment;
//# sourceMappingURL=index.js.map
