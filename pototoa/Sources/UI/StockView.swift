import SwiftUI

struct StockView: View {
    let stock: StockDto

    let formatter: NumberFormatter = {
        let formatter = NumberFormatter()
        return formatter
    }()
    let diffFormatter: NumberFormatter = {
        let formatter = NumberFormatter()
        formatter.positivePrefix = "+"
        formatter.negativePrefix = "-"
        return formatter
    }()

    var body: some View {
        VStack(spacing: 16) {
            // Chart
            ChartView(stock: stock)
                .frame(height: 200)

            // Ticker and delta
            HStack(alignment: .firstTextBaseline) {
                VStack(alignment: .leading, spacing: 4) {
                    Text(stock.symbol)
                        .font(.headline)
                    Text(stock.description)
                        .foregroundColor(.gray)
                        .font(.subheadline)
                }
                Spacer()
                HStack(alignment: .firstTextBaseline, spacing: 8) {
                    Text(formatter.string(for: stock.sharePrice) ?? "")
                        .font(.title2)
                        .bold()
                    let diff = stock.sharePrice - stock.stockInfo.open
                    Text(diffFormatter.string(for: diff) ?? "")
                        .foregroundColor(diff > 0 ? .green : diff == 0 ? .gray : .red)
                        .font(.title3)
                }
            }

            // Stats
            Grid(alignment: .leading) {
                GridRow {
                    Text("Open").foregroundColor(.gray)
                    Text(formatter.string(for: stock.stockInfo.open) ?? "")
                    Text("Vol").foregroundColor(.gray)
                    Text(formatter.string(for: stock.stockInfo.volume) ?? "")
                }
                GridRow {
                    Text("High").foregroundColor(.gray)
                    Text(formatter.string(for: stock.stockInfo.high) ?? "")
                    Text("Mkt Cap").foregroundColor(.gray)
                    Text(formatter.string(for: stock.stockInfo.marketCap) ?? "")
                }
                GridRow {
                    Text("Low").foregroundColor(.gray)
                    Text(formatter.string(for: stock.stockInfo.low) ?? "")
                    Text("").hidden()
                    Text("").hidden()
                }
            }
            .font(.system(.body, design: .monospaced))
        }
    }
}
