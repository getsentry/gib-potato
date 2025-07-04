struct StockDatasetDto: Decodable, Hashable {
    let data: [Int]
    let borderColor: String
    let backgroundColor: String
}
