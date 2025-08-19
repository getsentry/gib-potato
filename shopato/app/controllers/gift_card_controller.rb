class GiftCardController < ApplicationController
  include SecurePotatoConcern

  before_action :authenticate_🥔!, only: [ :create ]

  def create
    Sentry.logger.info("Gift card creation request received", email: params[:email], amount: params[:amount])

    gift_card_params = params.permit(:email, :amount)

    gift_card = GiftCard.new(
      email: gift_card_params[:email],
      amount: gift_card_params[:amount].to_i
    )

    if gift_card.valid?
      Sentry.logger.debug("Gift card validation passed", gift_card_id: gift_card.id, amount: gift_card.amount)

      result = gift_card_service.create_gift_card(gift_card.amount, gift_card.email)

      if result[:success]
        Sentry.logger.info("Gift card created successfully",
          gift_card_id: result[:gift_card]["id"],
          amount: result[:gift_card]["amount"],
          email: gift_card.email
        )
        render json: result[:gift_card], status: :created
      else
        Sentry.logger.error("Gift card creation failed",
          error_message: result[:message],
          email: gift_card.email,
          amount: gift_card.amount
        )
        render json: { errors: result[:message] }, status: :unprocessable_content
      end
    else
      Sentry.logger.warn("Gift card validation failed",
        errors: gift_card.errors.full_messages,
        email: gift_card.email,
        amount: gift_card.amount
      )
      render json: { errors: gift_card.errors.full_messages }, status: :unprocessable_content
    end
  end

  private

  def gift_card_service
    @gift_card_service ||= ShopifyGiftCardService.new
  end
end
