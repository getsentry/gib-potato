//UUID
const { uuid } = require('uuidv4');
// ORM
const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient()
// Secrets for Slack API
require('dotenv').config()
// Slack API
const { App } = require('@slack/bolt');

// Initialize the slack App
const app = new App({
  token: process.env.SLACK_BOT_TOKEN,
  signingSecret: process.env.SLACK_SIGNING_SECRET,
});


/// Find the user in the DB that has a matching slack ID, if there is none return null
async function getUserBySlackId(slackId) {
  return await prisma.users.findFirst({
    where: {
      slack_uid: slackId
    }
  })
}

/// Adds a user to the Database
async function addUser(fullName, slackId, uuid) {
  await prisma.users.create({
    data: {
      id: uuid,
      slack_full_name: fullName,
      slack_uid: slackId,
      created: new Date(),
      modified: new Date()
    }
  })
}

async function addMessage(senderDBId, user, potatoCount) {
  await prisma.messages.create({
    data: {
      id: uuid(),
      sender_user_id: senderDBId,
      receiver_user_id: user,
      amount: potatoCount,
      created: new Date()
    }
  });
}

// TODO: Finish
async function getPotatoCount(senderId) {
  // Need to check if it not the same
  const entry = await prisma.messages.findMany({
    where: {
      sender_user_id: senderId
    }
  })
  let cur = new Date()
  return entry.filter(t => t.created.getUTCDate() === cur.getUTCDate() && t.created.getUTCFullYear() === cur.getUTCFullYear() && t.created.getUTCMonth() === cur.getUTCMonth()).map(t => t.amount)
}

/// Grabs the database ID from the DB for the user with the matching slack ID
async function getDBIdBySlackId(slackId) {
  const user = await prisma.users.findFirst({
    where: {
      slack_uid: slackId
    }
  })
  return user["id"]
}

/// Get the full name using the userId from the Slack API
async function getUserNameBySlackId(userId) {
  try {
    // Call the users.info method using the WebClient
    const result = await app.client.users.info({
      user: userId
    });
    return result["user"]["real_name"]
  }
  catch (error) {
    console.error(error); // Maybe do some proper error handling here
    return "";
  }
}

/// Gets the DB Id if the user is in the Db else adds the user to the DB
async function getUserDbId(slackId) {
  let user = await getUserBySlackId(slackId)

  // If the user is not found in the Database add it to the Database
  if (!user) {
    let fullName = await getUserNameBySlackId(slackId)
    let uid = uuid();
    addUser(fullName, slackId, uid)
    return uid;
  } else {
    return user["id"]
  }
}

// Listens to incoming messages that contain "hello"
app.message(':potato:', async ({ message, say }) => {
  let senderSlackId = message.user;
  const text = message.text
  let senderDBId = await getUserDbId(senderSlackId)

  const regex = /<.*?>/g; // Regex to find all the mentions
  let usersFound = text.match(regex);

  if (!usersFound || usersFound.length == 0) { // Check needed for the filter
    await say("Seems like no one was tagged in that message");
    return
  }

  // Extract the ids from the mention
  usersFound = usersFound
    .map((t) => t.substring(2, t.length - 1)) // Remove the <@ >
    .filter((t) => t !== senderSlackId) // Remove the sender if he is in the message

  // These will be our DB ids for the people that where mentioned
  let userIds = []

  for (let key in usersFound) {
    let userSlackId = usersFound[key]
    userIds.push(await getUserDbId(userSlackId))
  }

  if (!usersFound || usersFound.length == 0) { // Check needed for the length
    await say("Seems like no one was tagged in that message"); // <- Think about if we maybe want to handle this differently
    return
  }

  let userCount = usersFound.length
  let potatoCount = (text.match(/:potato:/g) || []).length

  // TODO: Check that there are potatos left to give for the sender (sender ids)
  // one more check
  let potatoesGiven = (await getPotatoCount(senderDBId)).reduce((a, b) => a + b, 0)
  console.log(potatoesGiven)

  if (potatoesGiven > 5) {
    await say("You have already given 5 potatoes today");
    return
  }

  if (userCount * potatoCount > (5 - potatoesGiven)) {
    await say("You don't have that much potato's");
    return
  }

  // Add the message's to the DB
  userIds.forEach(async (user) => {
    await addMessage(senderDBId, user, potatoCount);
  })

  // This is just to check that we can find all the people ->  Seems to work
  let responds = "The following people got a potato:"
  usersFound.forEach(user => {
    responds += user
  });

  // We probably don't want to send a message later on
  // say() sends a message to the channel where the event was triggered
  await say(responds);
});

(async () => {
  // Start your app
  await app.start(process.env.PORT || 3000);

  console.log('⚡️ Bolt app is running!');
})();
