import { createTransport } from '@sentry/core';
import { getNativeFetchImplementation } from './utils.js';

/**
 * Creates a Transport that uses the Fetch API to send events to Sentry.
 */
function makeFetchTransport(
  options,
  nativeFetch = getNativeFetchImplementation(),
) {
  function makeRequest(request) {
    var requestOptions = {
      body: request.body,
      method: 'POST',
      referrerPolicy: 'origin',
      headers: options.headers,
      ...options.fetchOptions,
    };

    return nativeFetch(options.url, requestOptions).then(response => ({
      statusCode: response.status,
      headers: {
        'x-sentry-rate-limits': response.headers.get('X-Sentry-Rate-Limits'),
        'retry-after': response.headers.get('Retry-After'),
      },
    }));
  }

  return createTransport(options, makeRequest);
}

export { makeFetchTransport };
//# sourceMappingURL=fetch.js.map
