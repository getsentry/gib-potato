
const params = {
  api: {
    host: import.meta.env.VITE_APP_API_HOST,
  },
  slack: {
    clientId: import.meta.env.VITE_APP_SLACK_CLIENT_ID,
    redirectUri: import.meta.env.VITE_APP_SLACK_REDIRECT_URI,
    baseUrl: 'https://slack.com/openid/connect/authorize',
    scopes: ['openid', 'email', 'profile'],
  },
} as const;

export default params;
