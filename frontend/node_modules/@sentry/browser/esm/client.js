import { BaseClient, SDK_VERSION, getCurrentHub, getEnvelopeEndpointWithUrlEncodedAuth } from '@sentry/core';
import { getGlobalObject, getEventDescription, logger, createClientReportEnvelope, dsnToString, serializeEnvelope } from '@sentry/utils';
import { eventFromException, eventFromMessage } from './eventbuilder.js';
import { BREADCRUMB_INTEGRATION_ID } from './integrations/breadcrumbs.js';
import { sendReport } from './transports/utils.js';

var globalObject = getGlobalObject();

/**
 * The Sentry Browser SDK Client.
 *
 * @see BrowserOptions for documentation on configuration options.
 * @see SentryClient for usage documentation.
 */
class BrowserClient extends BaseClient {
  /**
   * Creates a new Browser SDK instance.
   *
   * @param options Configuration options for this SDK.
   */
   constructor(options) {
    options._metadata = options._metadata || {};
    options._metadata.sdk = options._metadata.sdk || {
      name: 'sentry.javascript.browser',
      packages: [
        {
          name: 'npm:@sentry/browser',
          version: SDK_VERSION,
        },
      ],
      version: SDK_VERSION,
    };

    super(options);

    if (options.sendClientReports && globalObject.document) {
      globalObject.document.addEventListener('visibilitychange', () => {
        if (globalObject.document.visibilityState === 'hidden') {
          this._flushOutcomes();
        }
      });
    }
  }

  /**
   * @inheritDoc
   */
   eventFromException(exception, hint) {
    return eventFromException(this._options.stackParser, exception, hint, this._options.attachStacktrace);
  }

  /**
   * @inheritDoc
   */
   eventFromMessage(
    message,
        level = 'info',
    hint,
  ) {
    return eventFromMessage(this._options.stackParser, message, level, hint, this._options.attachStacktrace);
  }

  /**
   * @inheritDoc
   */
   sendEvent(event, hint) {
    // We only want to add the sentry event breadcrumb when the user has the breadcrumb integration installed and
    // activated its `sentry` option.
    // We also do not want to use the `Breadcrumbs` class here directly, because we do not want it to be included in
    // bundles, if it is not used by the SDK.
    // This all sadly is a bit ugly, but we currently don't have a "pre-send" hook on the integrations so we do it this
    // way for now.
    var breadcrumbIntegration = this.getIntegrationById(BREADCRUMB_INTEGRATION_ID) ;
    if (
      breadcrumbIntegration &&
      // We check for definedness of `options`, even though it is not strictly necessary, because that access to
      // `.sentry` below does not throw, in case users provided their own integration with id "Breadcrumbs" that does
      // not have an`options` field
      breadcrumbIntegration.options &&
      breadcrumbIntegration.options.sentry
    ) {
      getCurrentHub().addBreadcrumb(
        {
          category: `sentry.${event.type === 'transaction' ? 'transaction' : 'event'}`,
          event_id: event.event_id,
          level: event.level,
          message: getEventDescription(event),
        },
        {
          event,
        },
      );
    }

    super.sendEvent(event, hint);
  }

  /**
   * @inheritDoc
   */
   _prepareEvent(event, hint, scope) {
    event.platform = event.platform || 'javascript';
    return super._prepareEvent(event, hint, scope);
  }

  /**
   * Sends client reports as an envelope.
   */
   _flushOutcomes() {
    var outcomes = this._clearOutcomes();

    if (outcomes.length === 0) {
      (typeof __SENTRY_DEBUG__ === 'undefined' || __SENTRY_DEBUG__) && logger.log('No outcomes to send');
      return;
    }

    if (!this._dsn) {
      (typeof __SENTRY_DEBUG__ === 'undefined' || __SENTRY_DEBUG__) && logger.log('No dsn provided, will not send outcomes');
      return;
    }

    (typeof __SENTRY_DEBUG__ === 'undefined' || __SENTRY_DEBUG__) && logger.log('Sending outcomes:', outcomes);

    var url = getEnvelopeEndpointWithUrlEncodedAuth(this._dsn, this._options);
    var envelope = createClientReportEnvelope(outcomes, this._options.tunnel && dsnToString(this._dsn));

    try {
      sendReport(url, serializeEnvelope(envelope));
    } catch (e) {
      (typeof __SENTRY_DEBUG__ === 'undefined' || __SENTRY_DEBUG__) && logger.error(e);
    }
  }
}

export { BrowserClient };
//# sourceMappingURL=client.js.map
