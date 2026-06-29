class GiftCardController < ActionController::Base
  include SecurePotatoConcern
  include Loggable

  before_action :authenticate_🥔!, only: [:create]
  skip_forgery_protection

  def create
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
    service_result = gift_card_service.create_gift_card(gift_card.amount, gift_card.name)

    if service_result[:success]
      render json: service_result[:gift_card], status: :created
    else
      render json: {errors: service_result[:message]}, status: :unprocessable_content
    end
  end

  def handle_validation_errors(gift_card)
    log_warn("Gift card request failed validation",
      "gibpotato.giftcard.amount": gift_card.amount,
      "gibpotato.giftcard.validation_errors": gift_card.errors.full_messages)
    render json: {errors: gift_card.errors.full_messages}, status: :unprocessable_content
  end

  def gift_card_service
    @gift_card_service ||= ShopifyGiftCardService.new
  end
end
