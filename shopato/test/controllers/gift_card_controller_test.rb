require "test_helper"

class GiftCardControllerTest < ActionDispatch::IntegrationTest
  def setup
    super
    @valid_email = "mr.potato@erdapfel.com"
    @valid_amount = 25
    @potato_token = "no_more_sweet_potatoes"

    ENV["POTATO_TOKEN"] = @potato_token
  end

  def test_create_gift_card_success
    mock_service = mock
    mock_service.expects(:create_gift_card).with(25, @valid_email).returns({ success: true, gift_card: { "code" => "POTATO-1234", "amount" => "25.00" } })

    GiftCardController.any_instance.stubs(:gift_card_service).returns(mock_service)

    post "/gift-card", params: {
      email: @valid_email,
      amount: @valid_amount
    }, headers: { "Authorization" => @potato_token }

    assert_response :success
    response_body = JSON.parse(response.body)
    assert_equal "POTATO-1234", response_body["code"]
    assert_equal "25.00", response_body["amount"]
  end

  def test_create_gift_card_requires_authentication
    post "/gift-card", params: {
      email: @valid_email,
      amount: @valid_amount
    }

    assert_response :unauthorized
  end

  def test_create_gift_card_with_shopify_error
    # Stub the gift card service to return an error
    mock_service = mock
    mock_service.expects(:create_gift_card).with(25, @valid_email).returns({ success: false, message: "Shopify error occurred" })

    GiftCardController.any_instance.stubs(:gift_card_service).returns(mock_service)

    post "/gift-card", params: {
      email: @valid_email,
      amount: @valid_amount
    }, headers: { "Authorization" => @potato_token }

    assert_response :unprocessable_content
    response_body = JSON.parse(response.body)
    assert_includes response_body["errors"], "Shopify error occurred"
  end
end
