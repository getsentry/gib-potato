require_relative "boot"

require "rails"
# Pick the frameworks you want:
require "active_model/railtie"
# require "active_job/railtie"
require "active_record/railtie"
# require "active_storage/engine"
require "action_controller/railtie"
# require "action_mailer/railtie"
# require "action_mailbox/engine"
# require "action_text/engine"
require "action_view/railtie"
# require "action_cable/engine"
require "rails/test_unit/railtie"

# Require the gems listed in Gemfile, including any gems
# you've limited to :test, :development, or :production.
# In development and test this loads dotenv, whose railtie reads the .env files.
Bundler.require(*Rails.groups)

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

    config.gib_potato_token = ENV.fetch("SHOPATO_TOKEN", nil)
    config.shopify_shop_domain = ENV.fetch("SHOPIFY_SHOP_DOMAIN", nil)
    config.shopify_admin_access_token = ENV.fetch("SHOPIFY_ADMIN_ACCESS_TOKEN", nil)

    # no CSRF protection
    config.action_controller.allow_forgery_protection = false

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
