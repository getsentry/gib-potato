//UUID
const { uuid } = require('uuidv4');
// ORM
const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient()
// Secrets for Slack API
require('dotenv').config()
// Slack API
const { App } = require('@slack/bolt');


// PoC to get info from the DB
async function query() {
  const allUsers = await prisma.users.findMany()
  console.log(allUsers)
}

// PoC to get info into the DB
/// Find the user in the DB that has a matching slack ID, if there is none return null
async function getUserBySlackId(slackId){
  const user  = await prisma.users.findFirst({
    where: {
      slack_uid: slackId
    }
  })

  return user
}

/// Adds a user to the Database
async function addUser(fullName, slackId){
  await prisma.users.create({
    data : {
      id : uuid(),
      slack_full_name : fullName,
      slack_uid : slackId,
      created :  new Date(),
      modified :  new Date()
    }
  })
}

// Initialize the slack App
const app = new App({
  token: process.env.SLACK_BOT_TOKEN,
  signingSecret: process.env.SLACK_SIGNING_SECRET,
});

// Get the full name using the userId from the Slack API
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
  
  let senderName = await getUserNameById(message.user) // <- How do we find this person in the DB ????
  // Find the user in the BD with the Slack UID
  let sender = await getUserBySlackId(message.user)
  if(!sender){
    console.log("Seems like the user is not yet in the DB")
    // TODO: Need to add the User to the DB
  }
  
  const regex = /<.*?>/g; // Regex to find all the mentions
  let usersFound = text.match(regex);

  if(!usersFound || usersFound.length == 0){ // Check needed for the filter
    await say("Seems like no one was tagged in that message");
    return
  }

  usersFound = usersFound.filter((t) => t !== `<@${message.user}>`) // Remove the sender if he is in the message

  if(!usersFound || usersFound.length == 0){ // Check needed for the length
    await say("Seems like no one was tagged in that message"); // <- Think about if we maybe want to handle this differently
    return
  }

  let userCount = usersFound.length
  let potatoCount = (text.match(/:potato:/g) || []).length

  // Check the total amount doesn't surpass the 5 limit
  if (userCount * potatoCount > 5) {
    await say("You don't have that much potato's");
    return
  }
  // TODO: Check that the number of potato's given is smaller or equal to the amount the user has

  // This is just to check that we can find all the people ->  Seems to work
  let responds = "The following people got a potato:"
  usersFound.forEach(user => {
    responds += user
  });


  // TODO: Check the number of potato's the sender has given
  // TODO: Push the changes to the DB

  // We probably don't want to send a message later on
  // say() sends a message to the channel where the event was triggered
  await say(responds);
});

(async () => {
  // Start your app
  await app.start(process.env.PORT || 3000);

  /* The proper way to work with the DB
  query().then(async () => {
    await prisma.$disconnect() // Do not want to have this in later versions since we need multiple queries
  })
    .catch(async (e) => { // Need to see if we want to be that strict about things
      console.error(e)
      await prisma.$disconnect()
      process.exit(1)
    })
  */ 
  addUser("User 1", "<@1>");
  console.log('⚡️ Bolt app is running!');
})();