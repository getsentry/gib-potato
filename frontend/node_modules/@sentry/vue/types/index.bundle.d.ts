export type { Breadcrumb, Request, SdkInfo, Event, Exception, SeverityLevel, StackFrame, Stacktrace, Thread, User, } from '@sentry/types';
export type { BrowserOptions, ReportDialogOptions } from '@sentry/browser';
export { BrowserClient, defaultIntegrations, forceLoad, lastEventId, onLoad, showReportDialog, flush, close, wrap, addGlobalEventProcessor, addBreadcrumb, captureException, captureEvent, captureMessage, configureScope, getHubFromCarrier, getCurrentHub, Hub, Scope, setContext, setExtra, setExtras, setTag, setTags, setUser, startTransaction, makeFetchTransport, makeXHRTransport, withScope, SDK_VERSION, } from '@sentry/browser';
export { init } from './sdk';
export { vueRouterInstrumentation } from './router';
export { attachErrorHandler } from './errorhandler';
export { createTracingMixins } from './tracing';
declare const INTEGRATIONS: {
    GlobalHandlers: typeof import("@sentry/browser").GlobalHandlers;
    TryCatch: typeof import("@sentry/browser").TryCatch;
    Breadcrumbs: typeof import("@sentry/browser").Breadcrumbs;
    LinkedErrors: typeof import("@sentry/browser").LinkedErrors;
    HttpContext: typeof import("@sentry/browser").HttpContext;
    Dedupe: typeof import("@sentry/browser").Dedupe;
    FunctionToString: typeof import("@sentry/browser").FunctionToString;
    InboundFilters: typeof import("@sentry/browser").InboundFilters;
};
export { INTEGRATIONS as Integrations };
//# sourceMappingURL=index.bundle.d.ts.map