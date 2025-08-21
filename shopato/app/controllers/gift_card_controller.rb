class GiftCardController < ActionController::Base
  include SecurePotatoConcern

  before_action :authenticate_🥔!, only: [ :create ]
  skip_forgery_protection

  def create
    Sentry.logger.info("Gift card creation request received", name: params[:name], amount: params[:amount])

    gift_card_params = params.permit(:name, :amount)

    gift_card = GiftCard.new(
      name: gift_card_params[:name],
      amount: gift_card_params[:amount].to_i
    )

    if gift_card.valid?
      Sentry.logger.debug("Gift card validation passed", gift_card_id: gift_card.id, amount: gift_card.amount)

      result = gift_card_service.create_gift_card(gift_card.amount, gift_card.name)

      if result[:success]
        Sentry.logger.info("Gift card created successfully",
          gift_card_id: result[:gift_card]["id"],
          amount: result[:gift_card]["amount"],
          name: gift_card.name)
        render json: result[:gift_card], status: :created
      else
        Sentry.logger.error("Gift card creation failed",
          error_message: result[:message],
          name: gift_card.name,
          amount: gift_card.amount)
        render json: { errors: result[:message] }, status: :unprocessable_content
      end
    else
      Sentry.logger.warn("Gift card validation failed",
        errors: gift_card.errors.full_messages,
        name: gift_card.name,
        amount: gift_card.amount)
      render json: { errors: gift_card.errors.full_messages }, status: :unprocessable_content
    end
  end

  private

  def gift_card_service
    @gift_card_service ||= ShopifyGiftCardService.new
  end
end
