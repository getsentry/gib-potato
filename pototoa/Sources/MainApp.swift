import SwiftUI
import Sentry

@main
struct MainApp: App {
    init() {
        SentrySDK.start { options in
            options.dsn = "https://600e80a8c22554775b3d4230806e1ddb@o1.ingest.us.sentry.io/4509077960327168"
            options.debug = true
            options.diagnosticLevel = .debug
            options.environment = "local"
            
            options.enableAppHangTracking = false
            options.enableAppHangTrackingV2 = true
            options.enableReportNonFullyBlockingAppHangs = true

            options.enableAutoBreadcrumbTracking = true
            options.enableAutoPerformanceTracing = true
            options.enableAutoSessionTracking = true
            options.enableCaptureFailedRequests = true
            options.enableCoreDataTracing = true
            options.enableCrashHandler = true
            options.enableFileIOTracing = true
            options.enableGraphQLOperationTracking = true
            options.enableNetworkBreadcrumbs = true
            options.enableNetworkTracking = true
            options.enablePerformanceV2 = true
            options.enablePersistingTracesWhenCrashing = true
            options.enablePreWarmedAppStartTracing = true
            options.enableSigtermReporting = true
            options.enableSpotlight = false // Disabled due to limited functionality
            options.enableSwizzling = true
            options.enableTimeToFullDisplayTracing = true
            options.enableUIViewControllerTracing = true
            options.enableUserInteractionTracing = true
            options.enableWatchdogTerminationTracking = true
            options.swiftAsyncStacktraces = true
            options.sendClientReports = true

            options.sendDefaultPii = true
            options.attachScreenshot = true
            options.attachViewHierarchy = true
            options.reportAccessibilityIdentifier = true

            options.sampleRate = 1.0
            options.tracesSampleRate = 1.0

            options.sessionReplay.sessionSampleRate = 1.0
            options.sessionReplay.onErrorSampleRate = 1.0
            options.sessionReplay.enableExperimentalViewRenderer = true

            options.experimental.enableFileManagerSwizzling = false
            options.experimental.enableDataSwizzling = false
        }

        // Keep the Apple TV from going to sleep
        UIApplication.shared.isIdleTimerDisabled = true
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
        }
    }
}
