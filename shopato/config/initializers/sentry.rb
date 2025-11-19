GIB_POTATO_ENV_MAPPING = {
  production: "production",
  development: "local"
}.freeze

Sentry.init do |config|
  config.dsn = ENV["SENTRY_SHOPATO_DSN"]
  config.environment = ENV["ENVIRONMENT"]
  config.release = ENV["RELEASE"]

  config.breadcrumbs_logger = [:active_support_logger, :http_logger]
  config.traces_sample_rate = 1.0
  config.profiles_sample_rate = 1.0
  config.send_default_pii = true

  config.enable_logs = true
  config.profiler_class = Sentry::Vernier::Profiler
end
