struct TradeDto: Decodable, Identifiable {
    let id: Int
    let symbol: String
    let price: Int?
    let proposedPrice: Int
    let status: String
    let type: String
    let time: String
}
