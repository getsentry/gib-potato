import { getGlobalObject, fill, getFunctionName, getOriginalFunction } from '@sentry/utils';
import { wrap } from '../helpers.js';

var DEFAULT_EVENT_TARGET = [
  'EventTarget',
  'Window',
  'Node',
  'ApplicationCache',
  'AudioTrackList',
  'ChannelMergerNode',
  'CryptoOperation',
  'EventSource',
  'FileReader',
  'HTMLUnknownElement',
  'IDBDatabase',
  'IDBRequest',
  'IDBTransaction',
  'KeyOperation',
  'MediaController',
  'MessagePort',
  'ModalWindow',
  'Notification',
  'SVGElementInstance',
  'Screen',
  'TextTrack',
  'TextTrackCue',
  'TextTrackList',
  'WebSocket',
  'WebSocketWorker',
  'Worker',
  'XMLHttpRequest',
  'XMLHttpRequestEventTarget',
  'XMLHttpRequestUpload',
];

/** Wrap timer functions and event targets to catch errors and provide better meta data */
class TryCatch  {
  /**
   * @inheritDoc
   */
   static __initStatic() {this.id = 'TryCatch';}

  /**
   * @inheritDoc
   */
   __init() {this.name = TryCatch.id;}

  /** JSDoc */
  

  /**
   * @inheritDoc
   */
   constructor(options) {;TryCatch.prototype.__init.call(this);
    this._options = {
      XMLHttpRequest: true,
      eventTarget: true,
      requestAnimationFrame: true,
      setInterval: true,
      setTimeout: true,
      ...options,
    };
  }

  /**
   * Wrap timer functions and event targets to catch errors
   * and provide better metadata.
   */
   setupOnce() {
    var global = getGlobalObject();

    if (this._options.setTimeout) {
      fill(global, 'setTimeout', _wrapTimeFunction);
    }

    if (this._options.setInterval) {
      fill(global, 'setInterval', _wrapTimeFunction);
    }

    if (this._options.requestAnimationFrame) {
      fill(global, 'requestAnimationFrame', _wrapRAF);
    }

    if (this._options.XMLHttpRequest && 'XMLHttpRequest' in global) {
      fill(XMLHttpRequest.prototype, 'send', _wrapXHR);
    }

    var eventTargetOption = this._options.eventTarget;
    if (eventTargetOption) {
      var eventTarget = Array.isArray(eventTargetOption) ? eventTargetOption : DEFAULT_EVENT_TARGET;
      eventTarget.forEach(_wrapEventTarget);
    }
  }
} TryCatch.__initStatic();

/** JSDoc */
function _wrapTimeFunction(original) {
    return function ( ...args) {
    var originalCallback = args[0];
    args[0] = wrap(originalCallback, {
      mechanism: {
        data: { function: getFunctionName(original) },
        handled: true,
        type: 'instrument',
      },
    });
    return original.apply(this, args);
  };
}

/** JSDoc */
function _wrapRAF(original) {
    return function ( callback) {
        return original.apply(this, [
      wrap(callback, {
        mechanism: {
          data: {
            function: 'requestAnimationFrame',
            handler: getFunctionName(original),
          },
          handled: true,
          type: 'instrument',
        },
      }),
    ]);
  };
}

/** JSDoc */
function _wrapXHR(originalSend) {
    return function ( ...args) {
        var xhr = this;
    var xmlHttpRequestProps = ['onload', 'onerror', 'onprogress', 'onreadystatechange'];

    xmlHttpRequestProps.forEach(prop => {
      if (prop in xhr && typeof xhr[prop] === 'function') {
                fill(xhr, prop, function (original) {
          var wrapOptions = {
            mechanism: {
              data: {
                function: prop,
                handler: getFunctionName(original),
              },
              handled: true,
              type: 'instrument',
            },
          };

          // If Instrument integration has been called before TryCatch, get the name of original function
          var originalFunction = getOriginalFunction(original);
          if (originalFunction) {
            wrapOptions.mechanism.data.handler = getFunctionName(originalFunction);
          }

          // Otherwise wrap directly
          return wrap(original, wrapOptions);
        });
      }
    });

    return originalSend.apply(this, args);
  };
}

/** JSDoc */
function _wrapEventTarget(target) {
    var global = getGlobalObject() ;
    var proto = global[target] && global[target].prototype;

    if (!proto || !proto.hasOwnProperty || !proto.hasOwnProperty('addEventListener')) {
    return;
  }

  fill(proto, 'addEventListener', function (original)

 {
    return function (
            
      eventName,
      fn,
      options,
    ) {
      try {
        if (typeof fn.handleEvent === 'function') {
          // ESlint disable explanation:
          //  First, it is generally safe to call `wrap` with an unbound function. Furthermore, using `.bind()` would
          //  introduce a bug here, because bind returns a new function that doesn't have our
          //  flags(like __sentry_original__) attached. `wrap` checks for those flags to avoid unnecessary wrapping.
          //  Without those flags, every call to addEventListener wraps the function again, causing a memory leak.
                    fn.handleEvent = wrap(fn.handleEvent, {
            mechanism: {
              data: {
                function: 'handleEvent',
                handler: getFunctionName(fn),
                target,
              },
              handled: true,
              type: 'instrument',
            },
          });
        }
      } catch (err) {
        // can sometimes get 'Permission denied to access property "handle Event'
      }

      return original.apply(this, [
        eventName,
                wrap(fn , {
          mechanism: {
            data: {
              function: 'addEventListener',
              handler: getFunctionName(fn),
              target,
            },
            handled: true,
            type: 'instrument',
          },
        }),
        options,
      ]);
    };
  });

  fill(
    proto,
    'removeEventListener',
    function (
      originalRemoveEventListener,
          ) {
      return function (
                
        eventName,
        fn,
        options,
      ) {
        /**
         * There are 2 possible scenarios here:
         *
         * 1. Someone passes a callback, which was attached prior to Sentry initialization, or by using unmodified
         * method, eg. `document.addEventListener.call(el, name, handler). In this case, we treat this function
         * as a pass-through, and call original `removeEventListener` with it.
         *
         * 2. Someone passes a callback, which was attached after Sentry was initialized, which means that it was using
         * our wrapped version of `addEventListener`, which internally calls `wrap` helper.
         * This helper "wraps" whole callback inside a try/catch statement, and attached appropriate metadata to it,
         * in order for us to make a distinction between wrapped/non-wrapped functions possible.
         * If a function was wrapped, it has additional property of `__sentry_wrapped__`, holding the handler.
         *
         * When someone adds a handler prior to initialization, and then do it again, but after,
         * then we have to detach both of them. Otherwise, if we'd detach only wrapped one, it'd be impossible
         * to get rid of the initial handler and it'd stick there forever.
         */
        var wrappedEventHandler = fn ;
        try {
          var originalEventHandler = wrappedEventHandler && wrappedEventHandler.__sentry_wrapped__;
          if (originalEventHandler) {
            originalRemoveEventListener.call(this, eventName, originalEventHandler, options);
          }
        } catch (e) {
          // ignore, accessing __sentry_wrapped__ will throw in some Selenium environments
        }
        return originalRemoveEventListener.call(this, eventName, wrappedEventHandler, options);
      };
    },
  );
}

export { TryCatch };
//# sourceMappingURL=trycatch.js.map
