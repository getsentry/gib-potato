require "test_helper"

class GiftCardTest < ActiveSupport::TestCase
  test "should be valid with correct attributes" do
    gift_card = valid_gift_card
    assert gift_card.valid?
  end

  test "should require name" do
    gift_card = valid_gift_card
    gift_card.name = nil
    assert_not gift_card.valid?
    assert_includes gift_card.errors[:name], "can't be blank"
  end

  test "should require amount" do
    gift_card = valid_gift_card
    gift_card.amount = nil
    assert_not gift_card.valid?
    assert_includes gift_card.errors[:amount], "can't be blank"
  end

  test "should only accept valid amounts" do
    gift_card = valid_gift_card
    GiftCard::VALID_AMOUNTS.each do |amount|
      gift_card.amount = amount
      assert gift_card.valid?, "#{amount} should be valid"
    end
  end

  test "should not accept invalid amounts" do
    gift_card = valid_gift_card
    invalid_amounts = [1, 5, 100, 0, -5]
    invalid_amounts.each do |amount|
      gift_card.amount = amount
      assert_not gift_card.valid?, "#{amount} should be invalid"
      assert_includes gift_card.errors[:amount], "must be one of: 10, 25, 50"
    end
  end

  test "should not be persisted" do
    gift_card = valid_gift_card
    assert_not gift_card.persisted?
  end

  test "should have unique id" do
    gift_card_1 = valid_gift_card
    gift_card_2 = valid_gift_card
    assert_not_equal gift_card_1.id, gift_card_2.id
  end
end
