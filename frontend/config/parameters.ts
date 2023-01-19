const params = {
  app: {
    host: import.meta.env.VITE_APP_HOST,
  },
  api: {
    host: import.meta.env.VITE_APP_API_HOST,
  },
  sentry: {
    dsn: import.meta.env.VITE_APP_SENTRY_DSN,
    environment: import.meta.env.VITE_APP_ENVIRONMENT,
    version: import.meta.env.VITE_APP_VERSION,
  },
} as const;

export default params;
