module SecurePotatoConcern
  extend ActiveSupport::Concern

  private

  def authenticate_🥔!
    unless valid_authentication?
      Sentry.logger.warn("Authentication failed - invalid or missing token",
        request_path: request.path,
        request_method: request.method,
        remote_ip: request.remote_ip
      )
      head :unauthorized
    else
      Sentry.logger.debug("Authentication successful",
        request_path: request.path,
        request_method: request.method
      )
    end
  end

  def valid_authentication?
    token = request.headers["Authorization"]
    return false unless token

    is_valid = token == Rails.application.config.gib_potato_token

    unless is_valid
      Sentry.logger.debug("Token validation failed",
        token_present: token.present?,
        token_length: token&.length
      )
    end

    is_valid
  end
end
