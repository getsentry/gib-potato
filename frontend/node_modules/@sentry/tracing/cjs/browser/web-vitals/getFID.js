Object.defineProperty(exports, '__esModule', { value: true });

var bindReporter = require('./lib/bindReporter.js');
var getVisibilityWatcher = require('./lib/getVisibilityWatcher.js');
var initMetric = require('./lib/initMetric.js');
var observe = require('./lib/observe.js');
var onHidden = require('./lib/onHidden.js');

/*
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

var getFID = (onReport, reportAllChanges) => {
  var visibilityWatcher = getVisibilityWatcher.getVisibilityWatcher();
  var metric = initMetric.initMetric('FID');
  let report;

  var entryHandler = (entry) => {
    // Only report if the page wasn't hidden prior to the first input.
    if (report && entry.startTime < visibilityWatcher.firstHiddenTime) {
      metric.value = entry.processingStart - entry.startTime;
      metric.entries.push(entry);
      report(true);
    }
  };

  var po = observe.observe('first-input', entryHandler );
  if (po) {
    report = bindReporter.bindReporter(onReport, metric, reportAllChanges);
    onHidden.onHidden(() => {
      po.takeRecords().map(entryHandler );
      po.disconnect();
    }, true);
  }
};

exports.getFID = getFID;
//# sourceMappingURL=getFID.js.map
