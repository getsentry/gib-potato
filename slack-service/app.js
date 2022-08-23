// UUID
const { uuid } = require("uuidv4");
// ORM
const { PrismaClient } = require("@prisma/client");
const prisma = new PrismaClient();
// Secrets for Slack API
require("dotenv").config();
// Slack API
const { App } = require("@slack/bolt");

// Initialize the slack App
const app = new App({
  token: process.env.SLACK_BOT_TOKEN,
  signingSecret: process.env.SLACK_SIGNING_SECRET,
});

/// Find the user in the DB that has a matching slack ID, if there is none return null
async function getUserBySlackId(slackId) {
  return await prisma.users.findFirst({
    where: {
      slack_uid: slackId,
    },
  });
}

/// Adds a user to the Database
async function addUser(fullName, slackId, uuid) {
  await prisma.users.create({
    data: {
      id: uuid,
      slack_full_name: fullName,
      slack_uid: slackId,
      created: new Date(),
      modified: new Date(),
    },
  });
}

async function addMessage(senderDBId, user, potatoCount) {
  await prisma.messages.create({
    data: {
      id: uuid(),
      sender_user_id: senderDBId,
      receiver_user_id: user,
      amount: potatoCount,
      created: new Date(),
    },
  });
}

// TODO: Finish
async function getPotatoesGiven(senderId) {
  // Need to check if it not the same
  const entry = await prisma.messages.findMany({
    where: {
      sender_user_id: senderId,
    },
  });

  const datesAreOnSameDay = (first, second) =>
    first.getUTCFullYear() === second.getUTCFullYear() &&
    first.getUTCMonth() === second.getUTCMonth() &&
    first.getUTCDate() === second.getUTCDate();

  let cur = new Date();
  return entry
    .filter((t) => datesAreOnSameDay(t.created, cur))
    .map((t) => t.amount)
    .reduce((a, b) => a + b, 0);
}

/// Get the full name using the userId from the Slack API
async function getUserNameBySlackId(userId) {
  try {
    // Call the users.info method using the WebClient
    const result = await app.client.users.info({
      user: userId,
    });
    return result["user"]["real_name"];
  } catch (error) {
    console.error(error); // Maybe do some proper error handling here
    return "";
  }
}

/// Gets the DB Id if the user is in the Db else adds the user to the DB
async function getUserDbId(slackId) {
  let user = await getUserBySlackId(slackId);

  // If the user is not found in the Database add it to the Database
  if (!user) {
    let fullName = await getUserNameBySlackId(slackId);
    let uid = uuid();
    addUser(fullName, slackId, uid);
    return uid;
  } else {
    return user["id"];
  }
}

// Listens to incoming messages that contain "hello"
app.message(":potato:", async ({ message, say }) => {
  const senderSlackId = message.user;
  const text = message.text;
  const senderDBId = await getUserDbId(senderSlackId);

  const regex = /<.*?>/g; // Regex to find all the mentions
  const userSlackIdsFound = text.match(regex);

  if (!userSlackIdsFound || userSlackIdsFound.length == 0) {
    // Check needed for the filter
    await say("Seems like no one was tagged in that message");
    return;
  }

  // Extract the ids from the mention
  const receiverSlackIds = userSlackIdsFound
    .filter((item, index) => userSlackIdsFound.indexOf(item) === index) // Remove duplicate ids
    .map((t) => t.substring(2, t.length - 1)) // Remove the <@ >
    .filter((t) => t !== senderSlackId); // Remove the sender if he is in the message

  if (!receiverSlackIds || receiverSlackIds.length == 0) {
    // Check needed for the length
    await say("Seems like no one was tagged in that message"); // <- Think about if we maybe want to handle this differently
    return;
  }

  const receiversCount = receiverSlackIds.length;
  const potatoCount = (text.match(/:potato:/g) || []).length;

  // TODO: Check that there are potatos left to give for the sender (sender ids)
  // one more check
  const potatoesGivenSoFar = await getPotatoesGiven(senderDBId);
  console.log(potatoesGivenSoFar);

  if (potatoesGivenSoFar > 5) {
    await say("You have already given 5 potatoes today");
    return;
  }

  if (receiversCount * potatoCount > 5 - potatoesGivenSoFar) {
    await say("You don't have that much potato's");
    return;
  }

  // These will be our DB ids for the people that where mentioned
  let userDBIds = [];

  for (const userSlackId of receiverSlackIds) {
    userDBIds.push(await getUserDbId(userSlackId));
  }

  // Add the message's to the DB
  userDBIds.forEach(async (user) => {
    await addMessage(senderDBId, user, potatoCount);
  });

  // This is just to check that we can find all the people ->  Seems to work
  let responds = "The following people got a potato:";
  receiverSlackIds.forEach((userSlackId) => {
    /* try {
      await app.client.chat.postMessage({
        channel: userSlackId,
        text: `You got ${potatoCount} by <@${senderSlackId}> \n>${text}`
      });
    }
    catch (error) {
      // TODO: handle this
      console.error(error);
    }
    */
    responds += `<@${userSlackId}>`;
  });

  // We probably don't want to send a message later on
  // say() sends a message to the channel where the event was triggered
  await say(responds);
});

// Listen to app home opened event
app.event("app_home_opened", async ({ event, client, context }) => {
  try {
    /* view.publish is the method that your app uses to push a view to the Home tab */
    await client.views.publish({
      /* the user that opened your app's app home */
      user_id: event.user,

      /* the view object that appears in the app home*/
      view: {
        type: "home",
        callback_id: "home_view",

        /* body of the view */
        blocks: [
          {
            type: "section",
            text: {
              type: "mrkdwn",
              text: "*Welcome to your _App's Home_* :tada:",
            },
          },
          {
            type: "divider",
          },
          {
            type: "section",
            text: {
              type: "mrkdwn",
              text: "This button won't do much for now but you can set up a listener for it using the `actions()` method and passing its unique `action_id`. See an example in the `examples` folder within your Bolt app.",
            },
          },
          {
            type: "actions",
            elements: [
              {
                type: "button",
                text: {
                  type: "plain_text",
                  text: "Click me!",
                },
              },
            ],
          },
        ],
      },
    });
  } catch (error) {
    console.error(error);
  }
});

(async () => {
  // Start your app
  await app.start(process.env.PORT || 3000);

  console.log("⚡️ Bolt app is running!");
})();
