require('dotenv').config()
const { App } = require('@slack/bolt');

// TODO: Make a connection with the DB via an ORM??
// TODO: Find a way to make this work with an env file
const app = new App({
  token: process.env.SLACK_BOT_TOKEN,
  signingSecret: process.env.SLACK_SIGNING_SECRET,
});

async function getUserNameById(userId) {
  try {
    // Call the users.info method using the WebClient
    const result = await app.client.users.info({
      user: senderId
    });
    return result["real_name"]
  }
  catch (error) {
    console.error(error); // Maybe do some proper error handling here
    return "";
  }
}

// Listens to incoming messages that contain "hello"
app.message(':potato:', async ({ message, say }) => {
  const text = message.text
  // Find the user in the BD with the Slack UID
  let senderName = await getUserNameById(message.user) // <- How do we find this person in the DB ????
  // TODO: So need to use the UID from slack for our ID, ask @michi

  // TODO: Grab the users mentioned in the text
  // TODO: Check if it is a valid amount
  // TODO: Check the number of tacos the sender has given
  // TODO: Push the changes to the DB

  // We probably don't want to send a message later on
  // say() sends a message to the channel where the event was triggered
  await say(`Hey there <@${message.user}>!`);
});

(async () => {
  // Start your app
  await app.start(process.env.PORT || 3000);

  console.log('⚡️ Bolt app is running!');
})();