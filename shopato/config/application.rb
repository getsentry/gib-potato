require_relative "boot"

# require "rails/all"

require "rails"

# action_mailer/railtie
# active_job/railtie
# action_cable/engine
# action_mailbox/engine
# action_text/engine
# active_storage/engine

%w[
  active_record/railtie
  action_controller/railtie
  action_view/railtie
  rails/test_unit/railtie
].each do |railtie|
  begin # rubocop:disable Style/RedundantBegin
    require railtie
  rescue LoadError
  end
end

# Require the gems listed in Gemfile, including any gems
# you've limited to :test, :development, or :production.
Bundler.require(*Rails.groups)

# Load environment variables from .env file in development and test environments
if Rails.env.development? || Rails.env.test?
  require "dotenv/load"
end

module Shopato
  class Application < Rails::Application
    # Initialize configuration defaults for originally generated Rails version.
    config.load_defaults 8.0

    # Please, add to the `ignore` list any other `lib` subdirectories that do
    # not contain `.rb` files, or that should not be reloaded or eager loaded.
    # Common ones are `templates`, `generators`, or `middleware`, for example.
    config.autoload_lib(ignore: %w[assets tasks])

    # Configuration for the application, engines, and railties goes here.
    #
    # These settings can be overridden in specific environments using the files
    # in config/environments, which are processed later.
    #
    # config.time_zone = "Central Time (US & Canada)"
    # config.eager_load_paths << Rails.root.join("extras")

    config.gib_potato_token = ENV.fetch("POTAL_TOKEN", nil)
    config.shopify_shop_domain = ENV.fetch("SHOPIFY_SHOP_DOMAIN", nil)
    config.shopify_admin_access_token = ENV.fetch("SHOPIFY_ADMIN_ACCESS_TOKEN", nil)

    # Log application startup
    initializer "shopato.startup_logging" do
      Rails.logger.info("Shopato application starting up")
      if defined?(Sentry)
        Sentry.logger.info("Application configuration loaded",
          environment: Rails.env,
          shopify_shop_domain: config.shopify_shop_domain,
          potato_token_configured: config.gib_potato_token.present?)
      end
    end
  end
end
