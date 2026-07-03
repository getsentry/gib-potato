module SecurePotatoConcern
  extend ActiveSupport::Concern

  private

  def authenticate_🥔!
    return if valid_authentication?

    Sentry.logger.warn("Gift card request authentication failed",
      "gibpotato.auth.request_path": request.path,
      "gibpotato.auth.request_method": request.method,
      "gibpotato.auth.remote_ip": request.remote_ip)
    head :unauthorized
  end

  def valid_authentication?
    token = request.headers["Authorization"]
    return false unless token

    token == Rails.application.config.gib_potato_token
  end
end
