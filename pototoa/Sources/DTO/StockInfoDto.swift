struct StockInfoDto: Decodable, Hashable {
    let amount: Int
    let open: Int
    let high: Int
    let low: Int
    let volume: Int
    let marketCap: Int
}
