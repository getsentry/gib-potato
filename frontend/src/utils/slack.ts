
export const buildSlackAuthUrl = (options: {
  baseUrl: string;
  clientId: string;
  redirectUri: string;
  scopes: readonly string[];
}): string => {
  const { baseUrl, clientId, redirectUri, scopes } = options;
  const url = [
    baseUrl,
    '?',
    `scope=${scopes.join(' ')}`,
    '&',
    'response_type=code',
    '&',
    `redirect_uri=${redirectUri}`,
    '&',
    `client_id=${clientId}`,
  ].join('');
  return encodeURI(url);
};
