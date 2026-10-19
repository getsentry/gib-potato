# syntax=docker/dockerfile:1
# check=error=true

# This Dockerfile is designed for production, not development. Use with Kamal or build'n'run by hand:
# docker build -t shopato .
# docker run -d -p 80:80 -e RAILS_MASTER_KEY=<value from config/master.key> --name shopato shopato

# For a containerized dev environment, see Dev Containers: https://guides.rubyonrails.org/getting_started_with_devcontainer.html

# Make sure RUBY_VERSION matches the Ruby version in .ruby-version
ARG RUBY_VERSION=3.4.5
FROM docker.io/library/ruby:$RUBY_VERSION-slim AS build

# Rails app lives here
WORKDIR /src

# Install packages needed to build gems, and jemalloc for the runtime image
RUN apt-get update -qq && \
    apt-get install --no-install-recommends -y build-essential git libjemalloc2 libssl-dev libyaml-dev pkg-config && \
    rm -rf /var/lib/apt/lists /var/cache/apt/archives

# Set production environment
ENV RAILS_ENV="production" \
    BUNDLE_DEPLOYMENT="1" \
    BUNDLE_PATH="/usr/local/bundle" \
    BUNDLE_WITHOUT="development test"

# Install application gems
COPY ./shopato/Gemfile ./shopato/Gemfile.lock ./
RUN bundle install && \
    rm -rf ~/.bundle/ "${BUNDLE_PATH}"/ruby/*/cache "${BUNDLE_PATH}"/ruby/*/bundler/gems/*/.git && \
    bundle exec bootsnap precompile --gemfile

# Copy application code
COPY ./shopato .

# Precompile bootsnap code for faster boot times
RUN bundle exec bootsnap precompile app/ lib/

# Own only the runtime files as the distroless nonroot user, ownership is kept when copied
RUN chown -R 65532:65532 db log storage tmp

# Collect the shared libraries Ruby, its extensions, native gems and jemalloc link against.
# glibc and the C++ runtime are skipped, the distroless cc image already ships them.
# Paths are normalized to /usr/lib, as /lib is a symlink in the distroless image.
RUN mkdir /runtime-libs && \
    { find /usr/local/bin/ruby /usr/local/lib "${BUNDLE_PATH}" -type f \( -name ruby -o -name '*.so*' \); \
      find /usr/lib -name 'libjemalloc.so.2'; } | \
    xargs ldd 2>/dev/null | awk '$2 == "=>" && $3 ~ /^\// { sub(/^\/lib\//, "/usr/lib/", $3); print $3 }' | sort -u | \
    grep -vE '/(ld-linux[^/]*|libc|libm|libdl|libpthread|librt|libresolv|libgcc_s|libstdc\+\+)\.so' | \
    xargs -I{} cp --parents -L {} /runtime-libs && \
    cp --parents -L "$(find /usr/lib -name 'libjemalloc.so.2')" /runtime-libs

# Final stage for app image
FROM gcr.io/distroless/cc-debian13:nonroot

# Mirror the environment of the official Ruby image
ENV LANG="C.UTF-8" \
    GEM_HOME="/usr/local/bundle" \
    BUNDLE_APP_CONFIG="/usr/local/bundle" \
    PATH="/usr/local/bundle/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

ENV RAILS_ENV="production" \
    BUNDLE_DEPLOYMENT="1" \
    BUNDLE_PATH="/usr/local/bundle" \
    BUNDLE_WITHOUT="development test"

# Copy built artifacts: Ruby, shared libraries, gems, application
COPY --from=build /usr/local/bin /usr/local/bin
COPY --from=build /usr/local/lib /usr/local/lib
COPY --from=build /usr/local/etc /usr/local/etc
COPY --from=build /runtime-libs /
COPY --from=build "${BUNDLE_PATH}" "${BUNDLE_PATH}"
COPY --from=build /src /src

WORKDIR /src

# Entrypoint prepares the database.
# There is no /usr/bin/env in the image, so Ruby scripts are run through ruby explicitly.
ENTRYPOINT ["ruby", "/src/bin/docker-entrypoint"]

# Start server via Thruster by default, this can be overwritten at runtime
EXPOSE 80
CMD ["ruby", "./bin/thrust", "ruby", "./bin/rails", "server"]
