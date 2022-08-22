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
      user: userId
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

  const regex = /<.*?>/g; // Regex to find all the mentions
  let usersFound  = text.match(regex);
  usersFound = usersFound.filter((t) => t !== `<@${message.user}>` ) // Remove the sender if he is in the message

  let userCount = usersFound.length
  let potatoCount = (text.match(/:potato:/g) || []).length

  // Check the total amount doesn't surpass the 5 limit
  if(userCount*potatoCount > 5){
    await say("You don't have that much potato's");
    return
  }
  // TODO: Check that the number of potato's given is smaller or equal to the amount the user has

  // This is just to check that we can find all the people
  let responds =  "The following people got a potato:"
  usersFound.forEach(user => {
    responds += user
  });


  // TODO: Check the number of tacos the sender has given
  // TODO: Push the changes to the DB

  // We probably don't want to send a message later on
  // say() sends a message to the channel where the event was triggered
  await say(responds);
});

(async () => {
  // Start your app
  await app.start(process.env.PORT || 3000);

  console.log('⚡️ Bolt app is running!');
})();