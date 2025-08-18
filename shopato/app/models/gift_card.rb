class GiftCard
  include ActiveModel::Model
  include ActiveModel::Attributes

  VALID_AMOUNTS = [ 5, 25, 50 ].freeze

  attribute :email, :string
  attribute :amount, :integer

  validates :email, presence: true, format: { with: URI::MailTo::EMAIL_REGEXP }
  validates :amount, presence: true, inclusion: { in: VALID_AMOUNTS, message: "must be one of: #{VALID_AMOUNTS.join(', ')}" }

  def initialize(attributes = {})
    super
    @id = SecureRandom.uuid
  end

  def id
    @id
  end

  def save
    return false unless valid?

    # Create gift card in Shopify
    service = ShopifyGiftCardService.new
    result = service.create_gift_card(amount, email)
    
    if result[:success]
      @shopify_gift_card = result[:gift_card]
      true
    else
      errors.add(:base, result[:message])
      result[:errors]&.each { |error| errors.add(:base, error) }
      false
    end
  rescue => e
    errors.add(:base, "Failed to create gift card: #{e.message}")
    false
  end

  def shopify_gift_card
    @shopify_gift_card
  end

  def persisted?
    false
  end
end
