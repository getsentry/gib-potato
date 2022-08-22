var {
  _optionalChain
} = require('@sentry/utils/cjs/buildPolyfills');

Object.defineProperty(exports, '__esModule', { value: true });

var utils = require('@sentry/utils');

/** Tracing integration for node-mysql package */
class Mysql  {constructor() { Mysql.prototype.__init.call(this); }
  /**
   * @inheritDoc
   */
   static __initStatic() {this.id = 'Mysql';}

  /**
   * @inheritDoc
   */
   __init() {this.name = Mysql.id;}

  /**
   * @inheritDoc
   */
   setupOnce(_, getCurrentHub) {
    var pkg = utils.loadModule('mysql/lib/Connection.js');

    if (!pkg) {
      (typeof __SENTRY_DEBUG__ === 'undefined' || __SENTRY_DEBUG__) && utils.logger.error('Mysql Integration was unable to require `mysql` package.');
      return;
    }

    // The original function will have one of these signatures:
    //    function (callback) => void
    //    function (options, callback) => void
    //    function (options, values, callback) => void
    utils.fill(pkg, 'createQuery', function (orig) {
      return function ( options, values, callback) {
        var scope = getCurrentHub().getScope();
        var parentSpan = _optionalChain([scope, 'optionalAccess', _2 => _2.getSpan, 'call', _3 => _3()]);
        var span = _optionalChain([parentSpan, 'optionalAccess', _4 => _4.startChild, 'call', _5 => _5({
          description: typeof options === 'string' ? options : (options ).sql,
          op: 'db',
        })]);

        if (typeof callback === 'function') {
          return orig.call(this, options, values, function (err, result, fields) {
            _optionalChain([span, 'optionalAccess', _6 => _6.finish, 'call', _7 => _7()]);
            callback(err, result, fields);
          });
        }

        if (typeof values === 'function') {
          return orig.call(this, options, function (err, result, fields) {
            _optionalChain([span, 'optionalAccess', _8 => _8.finish, 'call', _9 => _9()]);
            values(err, result, fields);
          });
        }

        return orig.call(this, options, values, callback);
      };
    });
  }
}Mysql.__initStatic();

exports.Mysql = Mysql;
//# sourceMappingURL=mysql.js.map
