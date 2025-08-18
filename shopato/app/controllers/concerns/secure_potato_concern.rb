module SecurePotatoConcern
  extend ActiveSupport::Concern

  private

  def authenticate_🥔!
    head :unauthorized unless valid_authentication?
  end

  def valid_authentication?
    token = request.headers["Authorization"]
    return false unless token

    token == ENV["POTATO_TOKEN"]
  end
end
