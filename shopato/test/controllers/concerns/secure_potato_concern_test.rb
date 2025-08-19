require "test_helper"

class SecurePotatoConcernTest < ActionDispatch::IntegrationTest
  class TestController < ApplicationController
    include SecurePotatoConcern

    before_action :authenticate_🥔!

    def test_action
      head :ok
    end
  end

  def setup
    @valid_token = Rails.application.config.gib_potato_token
  end

  test "should authenticate with valid token" do
    with_test_controller do
      get "/test", headers: {"Authorization" => @valid_token}

      assert_response :success
    end
  end

  test "should reject request without authorization header" do
    with_test_controller do
      get "/test"

      assert_response :unauthorized
    end
  end

  test "should reject request with invalid token" do
    with_test_controller do
      get "/test", headers: {"Authorization" => "SüßkartoffelFTW"}

      assert_response :unauthorized
    end
  end

  private

  def with_test_controller
    with_routing do |set|
      set.draw do
        get "test" => "secure_potato_concern_test/test#test_action"
      end

      yield
    end
  end
end
