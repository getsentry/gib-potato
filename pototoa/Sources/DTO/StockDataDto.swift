struct StockDataDto: Decodable, Hashable {
    let labels: [String]
    let datasets: [StockDatasetDto]
}
