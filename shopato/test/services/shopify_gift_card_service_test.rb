require "test_helper"

class ShopifyGiftCardServiceTest < ActiveSupport::TestCase
  def setup
    super
    WebMock.disable_net_connect!
  end

  def teardown
    WebMock.allow_net_connect!
    super
  end

  def test_creates_gift_card_with_token_only_session
    stub_shopify_api_success

    service = ShopifyGiftCardService.new
    result = service.create_gift_card(VALID_AMOUNT, VALID_NAME)

    assert result[:success]
    assert_equal "POTATO-1234", result[:gift_card]["code"]
    assert_equal "gid://shopify/GiftCard/123", result[:gift_card]["id"]
    assert_equal "25.00", result[:gift_card]["amount"]
  end

  def test_handles_shopify_api_user_errors
    stub_request(:post, "https://#{Rails.application.config.shopify_shop_domain}/admin/api/2025-07/graphql.json")
      .to_return(
        status: 200,
        headers: {"Content-Type" => "application/json"},
        body: {
          data: {
            giftCardCreate: {
              giftCard: nil,
              giftCardCode: nil,
              userErrors: [{message: "Invalid amount", field: "initialValue", code: "INVALID"}]
            }
          }
        }.to_json
      )

    service = ShopifyGiftCardService.new
    result = service.create_gift_card(VALID_AMOUNT, VALID_NAME)

    assert_not result[:success]
    assert_equal "Invalid amount", result[:message]
  end

  def test_handles_network_errors
    stub_request(:post, "https://#{Rails.application.config.shopify_shop_domain}/admin/api/2025-07/graphql.json")
      .to_raise(StandardError.new("Network error"))

    service = ShopifyGiftCardService.new
    result = service.create_gift_card(VALID_AMOUNT, VALID_NAME)

    assert_not result[:success]
    assert_includes result[:message], "Shopify error: Network error"
  end
end
