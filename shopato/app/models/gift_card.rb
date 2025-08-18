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

    # In a real application, you might save to a database or external service
    # For now, we'll just return true if validation passes
    true
  end

  def persisted?
    false
  end
end
