var {
  _nullishCoalesce
} = require('@sentry/utils/cjs/buildPolyfills');

Object.defineProperty(exports, '__esModule', { value: true });

var generateUniqueID = require('./generateUniqueID.js');

var initMetric = (name, value) => {
  return {
    name,
    value: _nullishCoalesce(value, () => ( -1)),
    delta: 0,
    entries: [],
    id: generateUniqueID.generateUniqueID(),
  };
};

exports.initMetric = initMetric;
//# sourceMappingURL=initMetric.js.map
