class GiftCard
  include ActiveModel::Model
  include ActiveModel::Attributes

  VALID_AMOUNTS = [ 10, 25, 50 ].freeze

  attribute :name, :string
  attribute :amount, :integer

  validates :name, presence: true, length: { minimum: 3, maximum: 255 }
  validates :amount, presence: true, inclusion: { in: VALID_AMOUNTS, message: "must be one of: #{VALID_AMOUNTS.join(", ")}" }

  def initialize(attributes = {})
    super
    @id = SecureRandom.uuid
  end

  attr_reader :id

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
