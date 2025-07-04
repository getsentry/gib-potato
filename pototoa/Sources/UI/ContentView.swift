import OSLog
import Foundation
import SwiftUI
import Charts
import Sentry
import Combine

struct ContentView: View {
    class ViewModel: ObservableObject {
        let apiManager = ApiManager.shared

        @Published var lastUpdatedAt: Date?
        @Published var data: StocksResponseDto?
        @Published var error: Error?

        var cancellables = Set<AnyCancellable>()

        init() {
            apiManager.publisher.sink { [weak self] result in
                switch result {
                case .success(let response):
                    self?.data = response
                    self?.error = nil
                case .failure(let error):
                    // Do not update the data to keep the last known good state
                    self?.error = error
                }
                self?.lastUpdatedAt = Date()
            }
            .store(in: &cancellables)
        }

        func refresh() {
            // Dispatch the task to the global queue to avoid blocking the main thread
            Task.detached { [weak apiManager] in
                await apiManager?.fetch()
            }
        }
    }

    @ObservedObject private var viewModel = ViewModel()

    static let interval: TimeInterval = 15
    let timer = Timer.publish(every: interval, on: .main, in: .common).autoconnect()

    let dateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        formatter.timeStyle = .medium
        return formatter
    }()

    let columns = [
        GridItem(.flexible()),
        GridItem(.flexible()),
        GridItem(.flexible())
    ]
    var body: some View {
        VStack {
            NewsTickerView()
                .ignoresSafeArea(.all)
            if let error = viewModel.error {
                Text("Failed to fetch data: \(error.localizedDescription)")
                    .background(Color.red)
                    .foregroundStyle(Color.white)
                    .font(.caption)
            }
            if let stocks = viewModel.data?.stocks {
                LazyVGrid(columns: columns, spacing: 16) {
                    ForEach(stocks, id: \.self) { stock in
                        StockView(stock: stock)
                            .frame(maxHeight: .infinity)
                    }
                }
                .padding()
            } else {
                Spacer()
            }
            footerView
        }
        .frame(maxHeight: .infinity)
        .padding(.bottom)
        .onReceive(timer) { _ in
            viewModel.refresh()
        }
        .onAppear {
            viewModel.refresh()
        }
    }

    var footerView: some View {
        HStack {
            Spacer()
            if let lastUpdatedAt = viewModel.lastUpdatedAt {
                Text("Last updated: \(dateFormatter.string(from: lastUpdatedAt))")
            } else {
                Text("Last updated: Never")
            }
            Spacer()
            Button("Refresh") {
                viewModel.refresh()
            }
        }
        .foregroundColor(.gray)
        .font(.caption)
    }
}
