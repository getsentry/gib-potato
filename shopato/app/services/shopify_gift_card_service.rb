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
    @client = ShopifyAPI::Clients::Graphql::Admin.new(session: @session)
    Sentry.logger.debug("ShopifyGiftCardService initialized", shop: shop)
  end

  def create_gift_card(amount, email = nil, note: nil)
    Sentry.logger.info("Creating gift card via Shopify API", amount: amount, email: email)

    variables = {
      input: {
        initialValue: format("%.2f", amount.to_f),
        note: note.presence || "Issued via Gib🥔 for #{email}"
      }
    }

    Sentry.logger.trace("Executing Shopify GraphQL mutation", variables: variables)
    response = @client.query(query: CREATE_MUTATION, variables: variables)
    payload = response.body.dig("data", "giftCardCreate")

    return error!("Unexpected response") if payload.nil?

    errors = Array(payload["userErrors"]).map { |e| e["message"] }
    if errors.any?
      Sentry.logger.error("Shopify API returned user errors",
        errors: errors,
        amount: amount,
        email: email)
      return error!(errors.join(", "))
    end

    gift_card_data = {
      "id" => payload.dig("giftCard", "id"),
      "amount" => payload.dig("giftCard", "initialValue", "amount"),
      "code" => payload["giftCardCode"]
    }

    Sentry.logger.info("Gift card created successfully via Shopify",
      gift_card_id: gift_card_data["id"],
      amount: gift_card_data["amount"])

    success!(gift_card: gift_card_data)
  rescue => e
    Sentry.logger.error("Shopify API error occurred",
      error_message: e.message,
      error_class: e.class.name,
      amount: amount,
      email: email)
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
