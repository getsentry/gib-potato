import { BaseClient, Scope } from '@sentry/core';
import { ClientOptions, Event, EventHint, Options, Severity, SeverityLevel } from '@sentry/types';
import { BrowserTransportOptions } from './transports/types';
export interface BaseBrowserOptions {
    /**
     * A pattern for error URLs which should exclusively be sent to Sentry.
     * This is the opposite of {@link Options.denyUrls}.
     * By default, all errors will be sent.
     */
    allowUrls?: Array<string | RegExp>;
    /**
     * A pattern for error URLs which should not be sent to Sentry.
     * To allow certain errors instead, use {@link Options.allowUrls}.
     * By default, all errors will be sent.
     */
    denyUrls?: Array<string | RegExp>;
}
/**
 * Configuration options for the Sentry Browser SDK.
 * @see @sentry/types Options for more information.
 */
export interface BrowserOptions extends Options<BrowserTransportOptions>, BaseBrowserOptions {
}
/**
 * Configuration options for the Sentry Browser SDK Client class
 * @see BrowserClient for more information.
 */
export interface BrowserClientOptions extends ClientOptions<BrowserTransportOptions>, BaseBrowserOptions {
}
/**
 * The Sentry Browser SDK Client.
 *
 * @see BrowserOptions for documentation on configuration options.
 * @see SentryClient for usage documentation.
 */
export declare class BrowserClient extends BaseClient<BrowserClientOptions> {
    /**
     * Creates a new Browser SDK instance.
     *
     * @param options Configuration options for this SDK.
     */
    constructor(options: BrowserClientOptions);
    /**
     * @inheritDoc
     */
    eventFromException(exception: unknown, hint?: EventHint): PromiseLike<Event>;
    /**
     * @inheritDoc
     */
    eventFromMessage(message: string, level?: Severity | SeverityLevel, hint?: EventHint): PromiseLike<Event>;
    /**
     * @inheritDoc
     */
    sendEvent(event: Event, hint?: EventHint): void;
    /**
     * @inheritDoc
     */
    protected _prepareEvent(event: Event, hint: EventHint, scope?: Scope): PromiseLike<Event | null>;
    /**
     * Sends client reports as an envelope.
     */
    private _flushOutcomes;
}
//# sourceMappingURL=client.d.ts.map