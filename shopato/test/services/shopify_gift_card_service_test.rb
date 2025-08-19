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
    # Mock the GraphQL request to Shopify
    stub_request(:post, "https://#{Rails.application.config.shopify_shop_domain}/admin/api/2025-07/graphql.json")
      .with(
        headers: {
          "X-Shopify-Access-Token" => Rails.application.config.shopify_admin_access_token,
          "Content-Type" => "application/json"
        },
        body: {
          query: ShopifyGiftCardService::CREATE_MUTATION,
          variables: {
            input: {
              initialValue: "25.00",
              note: "Issued via Gib🥔 for mr.potato@eräpfel.com"
            }
          }
        }.to_json
      )
      .to_return(
        status: 200,
        headers: {"Content-Type" => "application/json"},
        body: {
          data: {
            giftCardCreate: {
              giftCard: {
                id: "gid://shopify/GiftCard/123",
                initialValue: {amount: "25.00"}
              },
              giftCardCode: "POTATO-1234",
              userErrors: []
            }
          }
        }.to_json
      )

    service = ShopifyGiftCardService.new
    result = service.create_gift_card(25, "mr.potato@eräpfel.com")

    assert result[:success]
    assert_equal "POTATO-1234", result[:gift_card]["code"]
  end
end
