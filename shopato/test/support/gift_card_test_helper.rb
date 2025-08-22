module GiftCardTestHelper
  VALID_NAME = "Mr. Potato Head"
  VALID_AMOUNT = 25

  def valid_gift_card_params
    {name: VALID_NAME, amount: VALID_AMOUNT}
  end

  def authenticated_headers
    {"Authorization" => Rails.application.config.gib_potato_token}
  end

  def mock_successful_gift_card_service(expected_amount = VALID_AMOUNT, expected_name = VALID_NAME)
    mock_service = mock
    mock_service.expects(:create_gift_card)
      .with(expected_amount, expected_name)
      .returns({
        success: true,
        gift_card: {
          "id" => "gid://shopify/GiftCard/123",
          "code" => "POTATO-1234",
          "amount" => "#{expected_amount}.00"
        }
      })

    GiftCardController.any_instance.stubs(:gift_card_service).returns(mock_service)
    mock_service
  end

  def mock_failed_gift_card_service(error_message, expected_amount = VALID_AMOUNT, expected_name = VALID_NAME)
    mock_service = mock
    mock_service.expects(:create_gift_card)
      .with(expected_amount, expected_name)
      .returns({
        success: false,
        message: error_message,
        errors: [error_message]
      })

    GiftCardController.any_instance.stubs(:gift_card_service).returns(mock_service)
    mock_service
  end

  def valid_gift_card
    GiftCard.new(valid_gift_card_params)
  end

  def stub_shopify_api_success(amount = VALID_AMOUNT, name = VALID_NAME)
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
              initialValue: "#{amount}.00",
              note: "Issued via GibPotato for #{name}"
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
                initialValue: {amount: "#{amount}.00"}
              },
              giftCardCode: "POTATO-1234",
              userErrors: []
            }
          }
        }.to_json
      )
  end

  def assert_gift_card_created_successfully(response_body)
    assert_equal "POTATO-1234", response_body["code"]
    assert_includes response_body["amount"], "25.00"
  end

  def assert_unauthorized_response
    assert_response :unauthorized
  end

  def assert_unprocessable_response_with_errors(expected_errors)
    assert_response :unprocessable_content
    response_body = JSON.parse(response.body)
    Array(expected_errors).each do |error|
      assert_includes response_body["errors"], error
    end
  end
end
