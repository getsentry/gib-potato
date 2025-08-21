require "test_helper"

class GiftCardTest < ActiveSupport::TestCase
  def setup
    @valid_gift_card = GiftCard.new(
      name: "Mr. Potato Head",
      amount: 25
    )
  end

  test "should be valid with correct attributes" do
    assert @valid_gift_card.valid?
  end

  test "should require name" do
    @valid_gift_card.name = nil
    assert_not @valid_gift_card.valid?
    assert_includes @valid_gift_card.errors[:name], "can't be blank"
  end

  test "should require amount" do
    @valid_gift_card.amount = nil
    assert_not @valid_gift_card.valid?
    assert_includes @valid_gift_card.errors[:amount], "can't be blank"
  end

  test "should only accept valid amounts" do
    GiftCard::VALID_AMOUNTS.each do |amount|
      @valid_gift_card.amount = amount
      assert @valid_gift_card.valid?, "#{amount} should be valid"
    end
  end

  test "should not accept invalid amounts" do
    invalid_amounts = [ 1, 5, 100, 0, -5 ]
    invalid_amounts.each do |amount|
      @valid_gift_card.amount = amount
      assert_not @valid_gift_card.valid?, "#{amount} should be invalid"
      assert_includes @valid_gift_card.errors[:amount], "must be one of: 10, 25, 50"
    end
  end

  test "should not be persisted" do
    assert_not @valid_gift_card.persisted?
  end
end
