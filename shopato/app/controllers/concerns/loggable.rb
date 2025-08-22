module Loggable
  extend ActiveSupport::Concern

  private

  def log_info(message, **metadata)
    Sentry.logger.info(message, **metadata)
  end

  def log_debug(message, **metadata)
    Sentry.logger.debug(message, **metadata)
  end

  def log_error(message, **metadata)
    Sentry.logger.error(message, **metadata)
  end

  def log_warn(message, **metadata)
    Sentry.logger.warn(message, **metadata)
  end

  def log_trace(message, **metadata)
    Sentry.logger.trace(message, **metadata)
  end

  def log_request_metadata
    return {} unless defined?(request)

    {
      request_path: request.path,
      request_method: request.method,
      remote_ip: request.remote_ip
    }
  end
end
