require "test_helper"

class GiftCardControllerTest < ActionDispatch::IntegrationTest
  def test_create_gift_card_success
    mock_successful_gift_card_service

    post "/gift-card", params: valid_gift_card_params, headers: authenticated_headers

    assert_response :success
    response_body = JSON.parse(response.body)
    assert_gift_card_created_successfully(response_body)
  end

  def test_create_gift_card_requires_authentication
    post "/gift-card", params: valid_gift_card_params

    assert_unauthorized_response
  end

  def test_create_gift_card_with_shopify_error
    error_message = "Shopify error occurred"
    mock_failed_gift_card_service(error_message)

    post "/gift-card", params: valid_gift_card_params, headers: authenticated_headers

    assert_unprocessable_response_with_errors(error_message)
  end

  def test_create_gift_card_with_validation_errors
    post "/gift-card", params: {name: "", amount: 25}, headers: authenticated_headers

    assert_unprocessable_response_with_errors("Name can't be blank")
  end

  def test_create_gift_card_with_invalid_amount
    post "/gift-card", params: {name: VALID_NAME, amount: 100}, headers: authenticated_headers

    assert_unprocessable_response_with_errors("Amount must be one of: 10, 25, 50")
  end
end
