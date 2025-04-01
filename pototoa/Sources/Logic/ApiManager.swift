import OSLog
import Foundation
import Sentry
import Combine

class ApiManager {
    static let shared = ApiManager()
    static let logger = Logger(subsystem: Bundle.main.bundleIdentifier!, category: "ApiManager")

    let publisher = PassthroughSubject<Result<StocksResponseDto, Error>, Never>()

    private let apiKey: String

    init () {
        guard let configPlist = Bundle.main.path(forResource: "Config", ofType: "plist") else {
            fatalError("Config.plist not found. Please refer to the README.md to set it up.")
        }
        guard let config = NSDictionary(contentsOfFile: configPlist) else {
            fatalError("Failed to read Config.plist. Please refer to the README.md to set it up.")
        }
        guard let apiKey = config["SENTRY_GIB_POTATO_API_KEY"] as? String else {
            fatalError("API_KEY not found in Config.plist. Please refer to the README.md to set it up.")
        }
        self.apiKey = apiKey
    }

    func fetch() async {
        Self.logger.log("Fetching data...")
        let transaction = SentrySDK.startTransaction(
            name: "fetch-stocks",
            operation: "stocks.fetch"
        )
        do {
            // Fetch data from API
            let data: Data
            let fetchSpan = transaction.startChild(
                operation: "stocks.fetch.api",
                description: "Fetching data from API"
            )
            do {
                let url = URL(string: "https://gibpotato.app/api/stocks")!
                var request = URLRequest(url: url)
                request.addValue(apiKey, forHTTPHeaderField: "Authorization")
                (data, _) = try await URLSession.shared.data(for: request)
                fetchSpan.finish()
            } catch {
                fetchSpan.finish()
                throw error
            }

            // Decode data into data-transfer-objects
            let decodedData: StocksResponseDto
            let decodeSpan = transaction.startChild(
                operation: "stocks.fetch.decode",
                description: "Decoding data to models"
            )
            do {
                let decoder = JSONDecoder()
                decoder.keyDecodingStrategy = .convertFromSnakeCase
                decodedData = try decoder.decode(StocksResponseDto.self, from: data)
                decodeSpan.finish()
            } catch {
                decodeSpan.finish()
                throw error
            }

            await MainActor.run {
                SentrySDK.capture(message: "Fetched data successfully")
                publisher.send(.success(decodedData))
            }
        } catch {
            Self.logger.error("Failed to fetch data, reason: \(error)")
            SentrySDK.capture(error: error)
            await MainActor.run {
                publisher.send(.failure(error))
            }
        }
        transaction.finish()
    }
}
