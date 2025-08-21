class GiftCardController < ActionController::Base
  include SecurePotatoConcern
  include Loggable

  before_action :authenticate_🥔!, only: [:create]
  skip_forgery_protection

  def create
    log_info("Gift card creation request received", name: params[:name], amount: params[:amount])

    gift_card = build_gift_card

    if gift_card.valid?
      handle_valid_gift_card(gift_card)
    else
      handle_validation_errors(gift_card)
    end
  end

  private

  def build_gift_card
    gift_card_params = params.permit(:name, :amount)

    GiftCard.new(
      name: gift_card_params[:name],
      amount: gift_card_params[:amount].to_i
    )
  end

  def handle_valid_gift_card(gift_card)
    log_debug("Gift card validation passed", gift_card_id: gift_card.id, amount: gift_card.amount)

    service_result = gift_card_service.create_gift_card(gift_card.amount, gift_card.name)

    if service_result[:success]
      handle_service_success(service_result, gift_card)
    else
      handle_service_failure(service_result, gift_card)
    end
  end

  def handle_service_success(result, gift_card)
    log_info("Gift card created successfully",
      gift_card_id: result[:gift_card]["id"],
      amount: result[:gift_card]["amount"],
      name: gift_card.name)
    render json: result[:gift_card], status: :created
  end

  def handle_service_failure(result, gift_card)
    log_error("Gift card creation failed",
      error_message: result[:message],
      name: gift_card.name,
      amount: gift_card.amount)
    render json: {errors: result[:message]}, status: :unprocessable_content
  end

  def handle_validation_errors(gift_card)
    log_warn("Gift card validation failed",
      errors: gift_card.errors.full_messages,
      name: gift_card.name,
      amount: gift_card.amount)
    render json: {errors: gift_card.errors.full_messages}, status: :unprocessable_content
  end

  def gift_card_service
    @gift_card_service ||= ShopifyGiftCardService.new
  end
end
