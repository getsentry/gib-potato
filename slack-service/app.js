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
  profilesSampleRate: 1,
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

const currentUTCDateDay = () => {

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
    const entries = await prisma.messages.findMany({
      where: {
        sender_user_id: senderId,
      },
    });

    return entries
      .map((t) => t.amount)
      .reduce((a, b) => a + b, 0);
}

/// Gets the total potatoes received by the sender
async function getTotalPotatoesEverReceived(senderId) {
  // Need to check if it not the same
  const entries = await prisma.messages.findMany({
    where: {
      receiver_user_id: senderId,
    },
  });

  if(!entries.length){
    return 0
  }

  return entries
    .map((t) => t.amount)
    .reduce((a, b) => a + b, 0);
}

/// Gets the potatoes given today by the sender
async function getPotatoesGivenToday(senderId) {
  // Need to check if it not the same
  const entries = await prisma.messages.findMany({
    select: {
      amount: true,
    },
    // You can only ever send 5 potato per day, so it takes at most 5 messages
    // before you run out of potatoes. We can just take last 5 messages from today and discard the rest
    take: maxPotato,
    orderBy: [{
      created: 'desc'
    }],
    where: {
      sender_user_id: senderId,
      created: {
        gte: newUTCDate().setHours(0, 0, 0, 0),
      }
    },
  });

  if(!entries.length) {
    return 0;
  }

  return entries.map((t) => t.amount).reduce((a, b) => a + b, 0);
}

/// Get the full name using the userId from the Slack API
function getUserNameBySlackId(userId) {
  // Call the users.info method using the WebClient
  return app.client.users.info({
    user: userId,
  }).then(result => result["user"]["real_name"]);
}

/// Gets the DB Id if the user is in the Db else adds the user to the DB
async function getUserDbIdOrCreateUser(slackId) {
  const user = await getUserBySlackId(slackId);

  if(user){
    return user.id;
  }

  // If the user is not found in the Database add it to the Database
  return getUserNameBySlackId(slackId).then(async (fullName) => {
    const userId = uuid()
    const imageURL = (await app.client.users.profile.get({ user: slackId }))[
      "profile"
    ]["image_72"];
    await addUser(fullName, slackId, uuid(), imageURL);
    return userId
  })
}

// Regex to find all the mentions
const mentionsRegex = /<.*?>/g;

/// Figure out who gets potato
async function givePotato({user, text, channel, ts}) {
  const senderSlackId = user;
  const userSlackIdsFound = text.match(mentionsRegex);
  
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
  if (receiverSlackIds[0] === `${senderSlackId}`) {
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
  const senderDBId = await getUserDbIdOrCreateUser(senderSlackId);
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
  let userDBIds = receiverSlackIds.map(userSlackId => getUserDbIdOrCreateUser(userSlackId))
  const resolvedUsers = await Promise.all(userDBIds)

  // Add the message's to the DB
  const addMessagePromises = resolvedUsers.map(userDbId => addMessage(senderDBId, userDbId, potatoCount))
  await Promise.all(addMessagePromises)

  const permalinkToMessage = await app.client.chat.getPermalink({
    channel: channel,
    message_ts: ts
  })

  // This is just to check that we can find all the people ->  Seems to work
  let receivers = "";
  receiverSlackIds.forEach((userSlackId) => {
    app.client.chat.postMessage({
      channel: userSlackId,
      text: `You got *${potatoCount} potato* by <@${senderSlackId}> \n>${permalinkToMessage.permalink}`,
    })
    receivers += `<@${userSlackId}> `;
  });

  // Send the a Message to the sender of the Potatoes
  app.client.chat.postMessage({
    channel: senderSlackId,
    text: `You send *${potatoCount*receiversCount} potato* to ${receivers}\nYou have *${(maxPotato - potatoesGivenToday) - (potatoCount*receiversCount)} potato* left.\n>${permalinkToMessage.permalink}`,
  });
}

/// Listens to incoming messages that contain :potato:
app.message(":potato:", async ({message}) => {
  const transaction = Sentry.startTransaction({
    name: ":potato:"
  })
  await givePotato({
    user: message.user,
    text: message.text,
    channel: message.channel,
    ts: message.ts,
  })
  transaction.finish();
});


/// Listens to incoming :potato: reactions
app.event("reaction_added", async ({ event }) => {
  if (event.reaction === "potato") {
    const transaction = Sentry.startTransaction({
      name: "reaction_added: potato"
    })
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
    transaction.finish()
  }
});

/// Handle direct messages send to the bot
app.event("message", async ({ event, client, context }) => {
  if (event["channel_type"] === "im") {
    if (event["text"] === "potato") {
      const transaction = Sentry.startTransaction({
        name: "message: potato"
      })

      const cur = newUTCDate();
      const userID = await getUserDbIdOrCreateUser(event["user"]);
      const potatoesGivenSoFar = await getPotatoesGivenToday(userID);

      client.chat.postMessage({
        channel: event["channel"],
        text: `You have *${
          maxPotato - potatoesGivenSoFar
        }* potato left to gib today. Your potato will reset in ${
          23 - cur.getUTCHours()
        } hours and ${60 - cur.getUTCMinutes()} minutes.`,
      });
      
      transaction.finish();
    }
  }
});

/// Listen to app home opened event
app.event("app_home_opened", async ({ event, client, context }) => {
  const transaction = Sentry.startTransaction({
    name: "app_home_opened"
  })

  const userDbId = await getUserDbIdOrCreateUser(event["user"])
  const homePromises = [
    getPotatoesGivenToday(userDbId), 
    getTotalPotatoesGiven(userDbId),
    getTotalPotatoesEverReceived(userDbId)
  ]

  await Promise.all(homePromises).then(async ([potatoesGivenToday, totalPotatoesGiven, totalPotatoesReceived]) => {
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
  })
  transaction.finish()
});

(async () => {
  await app.start(process.env.PORT || 3000);
  console.log("🥔 GibPotato app is running!");
})();
