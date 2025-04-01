struct StocksResponseDto: Decodable {
    let trades: [TradeDto]
    let portfolio: [PortfolioDto]
    let stocks: [StockDto]
}
