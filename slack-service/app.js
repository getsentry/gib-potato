// Sentry
const Sentry = require("@sentry/node");
// Import order here matters, @sentry/tracing must be imported before @sentry/node
require("@sentry/tracing")
const {ProfilingIntegration} = require("@sentry/profiling-node")

// lodash
const _ = require("lodash");

// UUID
const { v4: uuid } = require("uuid");

// ORM
const { PrismaClient } = require("@prisma/client");
const prisma = new PrismaClient();

// Secrets for Slack API
require("dotenv").config();

// Slack API
const { App } = require("@slack/bolt");

Sentry.init({ 
  dsn: process.env.SENTRY_DSN,
  tracesSampleRate: 1,
  profilesSampleRate: 1, // Set profiling sampling rate.
  integrations: [new ProfilingIntegration()] 
});

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

/// Adds a message to the database
async function addMessage(senderDbId, receiverDbId, potatoCount) {
  return prisma.messages.create({
    data: {
      id: uuid(),
      sender_user_id: senderDbId,
      receiver_user_id: receiverDbId,
      amount: potatoCount,
      created: newUTCDate(),
    },
  });
}

/// Gets the total potatoes given by the sender
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

/// Gets the total potatoes received by the sender
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

/// Gets the potatoes given today by the sender
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
    // Maybe do some proper error handling here
    console.error(error);
    Sentry.captureException(error);

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

/// Figure out who gets potato
async function givePotato({user, text, channel, ts}) {
  const senderSlackId = user;
  const senderDBId = await getUserDbId(senderSlackId);

  // Regex to find all the mentions
  const regex = /<.*?>/g;
  const userSlackIdsFound = text.match(regex);

  // Extract the ids from the mention
  let receiverSlackIds = userSlackIdsFound
    // Remove duplicate ids 
    .filter((item, index) => userSlackIdsFound.indexOf(item) === index)
    // Remove the <@ >
    .map((t) => t.substring(2, t.length - 1));

  const postEphemeral = async (text) => {
    return app.client.chat.postEphemeral({
      channel: channel,
      user: senderSlackId,
      text,
    });
  };

  // Check if the only person recieving a potato is the creator of the message
  // and blame them...
  if (_.isEqual(receiverSlackIds, [`${senderSlackId}`])) {
    await postEphemeral("You cannot gib potato to yourself :face_with_raised_eyebrow:");
    return;
  }

  // Remove the sender if they are in the message
  receiverSlackIds = receiverSlackIds.filter((t) => t !== senderSlackId);

  if (!receiverSlackIds || receiverSlackIds.length == 0) {
    await postEphemeral("To gib people potato, you have to @ someone");
    return;
  }

  const receiversCount = receiverSlackIds.length;
  const potatoCount = (text.match(/:potato:/g) || []).length;

  // Check that there are potatos left to give for the sender (sender ids)
  const potatoesGivenToday = await getPotatoesGivenToday(senderDBId);

  if (potatoesGivenToday > maxPotato) {
    await postEphemeral("You already have gib out 5 potato today");
    return;
  }

  if (receiversCount * potatoCount > maxPotato - potatoesGivenToday) {
    await postEphemeral("You don't have genug potato");
    return;
  }

  // These will be our DB ids for the people that where mentioned
  let userDBIds = [];

  for (const userSlackId of receiverSlackIds) {
    // Adds the users to the Db if they are not in there yet
    userDBIds.push(await getUserDbId(userSlackId));
  }

  // Add the message's to the DB
  userDBIds.forEach(async (user) => {
    await addMessage(senderDBId, user, potatoCount);
  });

  const permalinkToMessage = await app.client.chat.getPermalink({
    channel: channel,
    message_ts: ts
  })

  // This is just to check that we can find all the people ->  Seems to work
  let receivers = "";
  receiverSlackIds.forEach((userSlackId) => {
    try {
      app.client.chat.postMessage({
        channel: userSlackId,
        text: `You got *${potatoCount} potato* by <@${senderSlackId}> \n>${permalinkToMessage.permalink}`,
      });
    } catch (error) {
      console.error(error);
      Sentry.captureException(error);
    }
    receivers += `<@${userSlackId}> `;
  });

   // Send the a Message to the sender of the Potatoes
   try {
    app.client.chat.postMessage({
      channel: senderSlackId,
      text: `You send *${potatoCount*receiversCount} potato* to ${receivers}\nYou have *${(maxPotato - potatoesGivenToday) - (potatoCount*receiversCount)} potato* left.\n>${permalinkToMessage.permalink}`,
    });
  } catch (error) {
    console.error(error);
    Sentry.captureException(error);
  }
}

/// Listens to incoming messages that contain :potato:
app.message(":potato:", async ({message}) => await givePotato({
    user: message.user,
    text: message.text,
    channel: message.channel,
    ts: message.ts,
  })
);


/// Listens to incoming :potato: reactions
app.event("reaction_added", async ({ event }) => {
  if (event.reaction === "potato") {
    await givePotato({
      user: event.user,
      // Set the text of the message to the slack user_id of the creator
      // of the message the reaction was added to.
      // We do this messy stuff to be able to work with the same interface
      // givePotato() provides.
      text: `<@${event.item_user}> :${event.reaction}:`,
      channel: event.item.channel,
      ts: event.item.ts,
    });
  }
});

/// Handle direct messages send to the bot
app.event("message", async ({ event, client, context }) => {
  if (event["channel_type"] === "im") {
    if (event["text"] === "potato") {
      const cur = newUTCDate();
      const userID = await getUserDbId(event["user"]);
      const potatoesGivenSoFar = await getPotatoesGivenToday(userID);
      client.chat.postMessage({
        channel: event["channel"],
        text: `You have *${
          maxPotato - potatoesGivenSoFar
        }* potato left to gib today. Your potato will reset in ${
          23 - cur.getUTCHours()
        } hours and ${60 - cur.getUTCMinutes()} minutes.`,
      });
    }
  }
});

/// Listen to app home opened event
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
              "text": "*Hallo to GibPotato!*"
            }
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "You can gib people potato to show your much like them and recognize them for all the toll things they do."
            }
          },
          {
            "type": "divider"
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "*My Potato*"
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
              "text": `Potatoes left to gib today: *${maxPotato - potatoesGivenToday}*`
            }
          },
          {
            "type": "divider"
          },
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "*Potato Received Leaderboard*"
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
                "url": "https://gibpotato.app"
              }
            ]
          }
        ]
      },
    });
  } catch (error) {
    console.log(error);
    Sentry.captureException(error);
  }
});

(async () => {
  await app.start(process.env.PORT || 3000);

  console.log("🥔 GibPotato app is running!");
})();
