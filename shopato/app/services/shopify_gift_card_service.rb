class ShopifyGiftCardService
  CREATE_MUTATION = <<~GRAPHQL
    mutation giftCardCreate($input: GiftCardCreateInput!) {
      giftCardCreate(input: $input) {
        giftCard {
          id
          note
          initialValue { amount }
        }
        giftCardCode
        userErrors { message field code }
      }
    }
  GRAPHQL

  def initialize(shop: Rails.application.config.shopify_shop_domain, token: Rails.application.config.shopify_admin_access_token)
    @session = ShopifyAPI::Auth::Session.new(shop: shop, access_token: token)
    @client  = ShopifyAPI::Clients::Graphql::Admin.new(session: @session)
  end

  def create_gift_card(amount, email = nil, note: nil)
    variables = {
      input: {
        initialValue: format("%.2f", amount.to_f),
        note: note.presence || "Issued via Gib🥔 for #{email}"
      }
    }

    response = @client.query(query: CREATE_MUTATION, variables: variables)
    payload  = response.body.dig("data", "giftCardCreate")

    return error!("Unexpected response") if payload.nil?

    errors = Array(payload["userErrors"]).map { |e| e["message"] }
    return error!(errors.join(", ")) if errors.any?

    success!(
      gift_card: {
        "id"     => payload.dig("giftCard", "id"),
        "amount" => payload.dig("giftCard", "initialValue", "amount"),
        "code"   => payload["giftCardCode"]
      }
    )
  rescue => e
    error!("Shopify error: #{e.message}")
  end

  private

  def success!(gift_card:)
    { success: true, gift_card: gift_card }
  end

  def error!(message)
    { success: false, message: message, errors: [ message ] }
  end
end
