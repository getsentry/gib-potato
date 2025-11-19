class ShopifyGiftCardService
  include Loggable

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
    log_debug("ShopifyGiftCardService initialized", shop: shop)
  end

  def create_gift_card(amount, name = nil, note: nil)
    log_info("Creating gift card via Shopify API", amount: amount, name: name)

    begin
      variables = build_mutation_variables(amount, name, note)
      response = execute_graphql_mutation(variables)
      process_shopify_response(response, amount, name)
    rescue => e
      handle_api_error(e, amount, name)
    end
  end

  private

  def build_mutation_variables(amount, name, note)
    {
      input: {
        initialValue: format("%.2f", amount.to_f),
        note: note.presence || "Issued via GibPotato for #{name}"
      }
    }
  end

  def execute_graphql_mutation(variables)
    log_trace("Executing Shopify GraphQL mutation", variables: variables)
    @client.query(query: CREATE_MUTATION, variables: variables)
  end

  def process_shopify_response(response, amount, name)
    payload = extract_payload(response)
    return error!("Unexpected response") if payload.nil?

    user_errors = extract_user_errors(payload)
    return handle_user_errors(user_errors, amount, name) if user_errors.any?

    build_success_response(payload)
  end

  def extract_payload(response)
    response.body.dig("data", "giftCardCreate")
  end

  def extract_user_errors(payload)
    Array(payload["userErrors"]).map { |e| e["message"] }
  end

  def handle_user_errors(errors, amount, name)
    log_error("Shopify API returned user errors",
      errors: errors,
      amount: amount,
      name: name)
    error!(errors.join(", "))
  end

  def build_success_response(payload)
    gift_card_data = {
      "id" => payload.dig("giftCard", "id"),
      "amount" => payload.dig("giftCard", "initialValue", "amount"),
      "code" => payload["giftCardCode"]
    }

    log_info("Gift card created successfully via Shopify",
      gift_card_id: gift_card_data["id"],
      amount: gift_card_data["amount"])

    success!(gift_card: gift_card_data)
  end

  def handle_api_error(error, amount, name)
    log_error("Shopify API error occurred",
      error_message: error.message,
      error_class: error.class.name,
      amount: amount,
      name: name)
    error!("Shopify error: #{error.message}")
  end

  def success!(gift_card:)
    {success: true, gift_card: gift_card}
  end

  def error!(message)
    {success: false, message: message, errors: [message]}
  end
end
