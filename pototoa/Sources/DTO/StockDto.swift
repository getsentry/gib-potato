struct StockDto: Decodable, Identifiable, Hashable {
    let id: Int
    let symbol: String
    let description: String
    let sharePrice: Int
    let stockInfo: StockInfoDto
    let data: StockDataDto
}
