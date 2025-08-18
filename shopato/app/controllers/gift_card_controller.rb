class GiftCardController < ApplicationController
  include SecurePotatoConcern

  before_action :authenticate_🥔!, only: [ :create ]

  def create
    gift_card_params = params.permit(:email, :amount)

    gift_card = GiftCard.new(
      email: gift_card_params[:email],
      amount: gift_card_params[:amount].to_i
    )

    if gift_card.valid?
      result = gift_card_service.create_gift_card(gift_card.amount, gift_card.email)

      if result[:success]
        render json: result[:gift_card], status: :created
      else
        render json: { errors: result[:message] }, status: :unprocessable_content
      end
    else
      render json: { errors: gift_card.errors.full_messages }, status: :unprocessable_content
    end
  end

  private

  def gift_card_service
    @gift_card_service ||= ShopifyGiftCardService.new
  end
end
