require('dotenv').config()
const { App } = require('@slack/bolt');

const app = new App({
  token: process.env.SLACK_BOT_TOKEN,
  signingSecret: process.env.SLACK_SIGNING_SECRET,
});

// Listens to incoming messages that contain "hello"
app.message(':potato:', async ({ message, say }) => {
  const text = message.text
  const senderId = message.user
  let senderName = ''

  try {
    // Call the users.info method using the WebClient
    const result = await app.client.users.info({
      user: senderId
    });
    senderName = result["real_name"]
  }
  catch (error) {
    console.error(error);
  }

  // say() sends a message to the channel where the event was triggered
  await say(`Hey there <@${message.user}>!`);
});

(async () => {
  // Start your app
  await app.start(process.env.PORT || 3000);

  console.log('⚡️ Bolt app is running!');
})();