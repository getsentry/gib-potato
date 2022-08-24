// UUID
const { uuid } = require("uuidv4");
// ORM
const { PrismaClient } = require("@prisma/client");
const prisma = new PrismaClient();
// Secrets for Slack API
require("dotenv").config();
// Slack API
const { App } = require("@slack/bolt");

const maxPotato = process.env.MAX_POTATO

// Initialize the slack App
const app = new App({
  token: process.env.SLACK_BOT_TOKEN,
  signingSecret: process.env.SLACK_SIGNING_SECRET,
});

const newUTCDate = () => {
  const date = new Date();
  const nowUtc = Date.UTC(date.getUTCFullYear(), date.getUTCMonth(),
                  date.getUTCDate(), date.getUTCHours(),
                  date.getUTCMinutes(), date.getUTCSeconds());
  return new Date(nowUtc)
}

/// Find the user in the DB that has a matching slack ID, if there is none return null
async function getUserBySlackId(slackId) {
  return await prisma.users.findFirst({
    where: {
      slack_user_id: slackId,
    },
  });
}

/// Adds a user to the Database
async function addUser(fullName, slackId, uuid, imageURL) {
  return prisma.users.create({
    data: {
      id: uuid,
      slack_name: fullName,
      slack_user_id: slackId,
      slack_picture: imageURL,
      created: newUTCDate(),
      modified: newUTCDate(),
    },
  });
}

async function addMessage(senderDBId, user, potatoCount) {
  return prisma.messages.create({
    data: {
      id: uuid(),
      sender_user_id: senderDBId,
      receiver_user_id: user,
      amount: potatoCount,
      created: newUTCDate(),
    },
  });
}

async function getTotalPotatoesGiven(senderId) {
    // Need to check if it not the same
    const entry = await prisma.messages.findMany({
      where: {
        sender_user_id: senderId,
      },
    });
    
    return entry
      .map((t) => t.amount)
      .reduce((a, b) => a + b, 0);
}

async function getTotalPotatoesReceived(senderId) {
  // Need to check if it not the same
  const entry = await prisma.messages.findMany({
    where: {
      receiver_user_id: senderId,
    },
  });

  return entry
    .map((t) => t.amount)
    .reduce((a, b) => a + b, 0);
}

// TODO: Finish
async function getPotatoesGivenToday(senderId) {
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

  const cur = newUTCDate();
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
  const user = await getUserBySlackId(slackId);
  const imageURL = (await app.client.users.profile.get({ user: slackId }))[
    "profile"
  ]["image_72"];

  // If the user is not found in the Database add it to the Database
  if (!user) {
    const fullName = await getUserNameBySlackId(slackId);
    const uid = uuid();
    await addUser(fullName, slackId, uid, imageURL);
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

  // Extract the ids from the mention
  const receiverSlackIds = userSlackIdsFound
    .filter((item, index) => userSlackIdsFound.indexOf(item) === index) // Remove duplicate ids
    .map((t) => t.substring(2, t.length - 1)) // Remove the <@ >
    .filter((t) => t !== senderSlackId); // Remove the sender if he is in the message

  const postEphemeral = async (text) => {
    return app.client.chat.postEphemeral({
      channel: message.channel,
      user: senderSlackId,
      text,
    });
  };

  if (!receiverSlackIds || receiverSlackIds.length == 0) {
    // Check needed for the length
    await postEphemeral("Seems like no one was tagged in that message");
    return;
  }

  const receiversCount = receiverSlackIds.length;
  const potatoCount = (text.match(/:potato:/g) || []).length;

  // TODO: Check that there are potatos left to give for the sender (sender ids)
  // one more check
  const potatoesGivenSoFar = await getPotatoesGivenToday(senderDBId);

  if (potatoesGivenSoFar > maxPotato) {
    await postEphemeral("You have already given 5 potatoes today");
    return;
  }

  if (receiversCount * potatoCount > maxPotato - potatoesGivenSoFar) {
    await postEphemeral("You don't have enough potatoes");
    return;
  }

  // These will be our DB ids for the people that where mentioned
  let userDBIds = [];

  for (const userSlackId of receiverSlackIds) {
    userDBIds.push(await getUserDbId(userSlackId)); // Adds the users to the Db if they are not in there yet
  }

  // Add the message's to the DB
  userDBIds.forEach(async (user) => {
    await addMessage(senderDBId, user, potatoCount);
  });

  // This is just to check that we can find all the people ->  Seems to work
  let responds = "The following people got a potato:";
  receiverSlackIds.forEach((userSlackId) => {
    try {
      app.client.chat.postMessage({
        channel: userSlackId,
        text: `You got *${potatoCount} potato* by <@${senderSlackId}> \n>${text}`,
      });
    } catch (error) {
      // TODO: handle this
      console.error(error);
    }
    responds += `<@${userSlackId}>`;
  });
});

/// Handle the messages
app.event("message", async ({ event, client, context }) => {
  if (event["channel_type"] === "im") {
    if (event["text"] === "potatoes") {
      const cur = newUTCDate();
      const userID = await getUserDbId(event["user"]);
      const potatoesGivenSoFar = await getPotatoesGivenToday(userID);
      client.chat.postMessage({
        channel: event["channel"],
        text: `You have *${
          maxPotato - potatoesGivenSoFar
        }* potatoes left to give today. Your potatoes will reset in ${
          23 - cur.getUTCHours()
        } hours and ${60 - cur.getUTCMinutes()} minutes.`,
      });
    }
  }
});

// Listen to app home opened event
app.event("app_home_opened", async ({ event, client, context }) => {
  const userSlackId = event["user"]
  const userDbId = await getUserDbId(userSlackId)
  const potatoesGivenToday = await getPotatoesGivenToday(userDbId)
  const totalPotatoesGiven = await getTotalPotatoesGiven(userDbId)
  const totalPotatoesReceived = await getTotalPotatoesReceived(userDbId)

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
        "blocks": [
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "*Welcome to GibPotato!* :potato:"
            }
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "You can give people :potato:'s to show your appreciation and recognize them for all the great things they do."
            }
          },
          {
            "type": "divider"
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "*My Potatoes*"
            }
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": `Received: ${totalPotatoesReceived} :potato:   |   Given: ${totalPotatoesGiven} :potato:`
            }
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": `Potatoes left to give today: *${maxPotato - potatoesGivenToday}*`
            }
          },
          {
            "type": "divider"
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "*Potatoes Received Leaderboard*"
            }
          },
          {
            "type": "actions",
            "elements": [
              {
                "type": "button",
                "text": {
                  "type": "plain_text",
                  "text": "View Full Leaderboard",
                  "emoji": true
                },
                "url": "https://test.com" // TODO: change this to actual url
              }
            ]
          }
        ]
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