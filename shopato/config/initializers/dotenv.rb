# Load environment variables from .env files
# This initializer loads environment variables from .env files based on the current environment

# Load the base .env file first
Dotenv::Railtie.load if defined?(Dotenv::Railtie)

# Load environment-specific .env files
env_file = ".env.#{Rails.env}"
if File.exist?(env_file)
  Dotenv.load(env_file)
end

# Load local overrides (for development)
local_env_file = ".env.#{Rails.env}.local"
if File.exist?(local_env_file)
  Dotenv.load(local_env_file)
end
