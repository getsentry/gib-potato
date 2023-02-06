package main

import (
	"context"
	"log"

	"github.com/getsentry/gib-potato/internal/constants"
	"github.com/getsentry/gib-potato/internal/utils"
	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

type Event interface {
	isValid() bool
}

type EventType string

const (
	message       EventType = "message"
	directMessage EventType = "direct_message"
	reactionAdded EventType = "reaction_added"
	appMention    EventType = "app_mention"
	appHomeOpened EventType = "app_home_opened"
)

func (e EventType) String() string {
	return string(e)
}

type MessageEvent struct {
	Type           EventType `json:"type"`
	Amount         int       `json:"amount"`
	Sender         string    `json:"sender"`
	Receivers      []string  `json:"receivers"`
	Channel        string    `json:"channel"`
	Text           string    `json:"text"`
	Reaction       string    `json:"reaction"`
	Timestamp      string    `json:"timestamp"`
	EventTimestamp string    `json:"event_timestamp"`
	Permalink      string    `json:"permalink"`

	ThreadTimestamp string `json:"thread_timestamp,omitempty"`

	BotID string `json:"-"`
}

type DirectEvent struct {
	Type           EventType `json:"type"`
	Sender         string    `json:"sender"`
	Channel        string    `json:"channel"`
	Text           string    `json:"text"`
	Timestamp      string    `json:"timestamp"`
	EventTimestamp string    `json:"event_timestamp"`

	BotID string `json:"-"`
}

type ReactionAddedEvent struct {
	Type           EventType `json:"type"`
	Amount         int       `json:"amount"`
	Sender         string    `json:"sender"`
	Receivers      []string  `json:"receivers"`
	Channel        string    `json:"channel"`
	Text           string    `json:"text"`
	Reaction       string    `json:"reaction"`
	Timestamp      string    `json:"timestamp"`
	EventTimestamp string    `json:"event_timestamp"`
	Permalink      string    `json:"permalink"`

	ThreadTimestamp string `json:"thread_timestamp,omitempty"`
}

type AppMentionEvent struct {
	Type           EventType `json:"type"`
	Sender         string    `json:"sender"`
	Channel        string    `json:"channel"`
	Text           string    `json:"text"`
	EventTimestamp string    `json:"event_timestamp"`

	BotID string `json:"-"`
}

type AppHomeOpenedEvent struct {
	Type           EventType `json:"type"`
	User           string    `json:"user"`
	Tab            string    `json:"tab"`
	EventTimestamp string    `json:"event_timestamp"`
}

func (e MessageEvent) isValid() bool {
	// Only process messages with potato and not from a bot
	return e.Amount > 0 && e.BotID == ""
}

func (e DirectEvent) isValid() bool {
	// Only process messages not from a bot
	return e.BotID == ""
}

func (e ReactionAddedEvent) isValid() bool {
	// Only process potato reactions
	return e.Reaction == "potato"
}

func (e AppMentionEvent) isValid() bool {
	// Only process messages not from a bot
	return e.BotID == ""
}

func (e AppHomeOpenedEvent) isValid() bool {
	// Only process home tab
	return e.Tab == "home"
}

func processMessageEvent(ctx context.Context, event *slackevents.MessageEvent) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctx)

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()

	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(context.Background(), "EVENT message", options...)
	defer transaction.Finish()

	messageEvent := MessageEvent{
		Type:            message,
		Amount:          utils.MessageAmount(event.Text),
		Sender:          event.User,
		Receivers:       utils.MessageReceivers(event.Text),
		Channel:         event.Channel,
		Text:            event.Text,
		Reaction:        constants.Potato, // hardoced for now
		Timestamp:       event.TimeStamp,
		EventTimestamp:  event.EventTimeStamp,
		ThreadTimestamp: event.ThreadTimeStamp,
		BotID:           event.BotID,
	}

	if !messageEvent.isValid() {
		return
	}

	span := transaction.StartChild("http.client")
	span.Description = "GET https://slack.com/api/chat.getPermalink "

	// Get the permalink for the message
	permalink, err := slackClient.GetPermalink(&slack.PermalinkParameters{
		Channel: event.Channel,
		Ts:      event.TimeStamp,
	})
	if err != nil {
		span.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		log.Printf("An Error Occured %v", err)
	} else {
		span.Status = sentry.SpanStatusOK
	}
	messageEvent.Permalink = permalink
	span.Finish()

	hub.Scope().SetExtra("event", messageEvent)
	hub.Scope().SetTag("event_type", message.String())

	sendRequest(messageEvent, hub, transaction)
}

func processDirectMessageEvent(ctx context.Context, event *slackevents.MessageEvent) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctx)

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()

	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(context.Background(), "EVENT direct_message", options...)
	defer transaction.Finish()

	directMessageEvent := DirectEvent{
		Type:           directMessage,
		Sender:         event.User,
		Channel:        event.Channel,
		Text:           event.Text,
		Timestamp:      event.TimeStamp,
		EventTimestamp: event.EventTimeStamp,
		BotID:          event.BotID,
	}

	if !directMessageEvent.isValid() {
		return
	}

	hub.Scope().SetExtra("event", directMessageEvent)
	hub.Scope().SetTag("event_type", directMessage.String())

	sendRequest(directMessageEvent, hub, transaction)
}

func processReactionEvent(ctx context.Context, event *slackevents.ReactionAddedEvent) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctx)

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()
	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(context.Background(), "EVENT reaction_added", options...)
	defer transaction.Finish()

	reactionEvent := ReactionAddedEvent{
		Reaction: event.Reaction,
	}

	if !reactionEvent.isValid() {
		return
	}

	var text string
	var threadTimestamp string

	converationHistorySpan := transaction.StartChild("http.client")
	converationHistorySpan.Description = "GET https://slack.com/api/conversations.history"

	// Get the text and thread timestamp for the original message
	conversationHistory, err := slackClient.GetConversationHistory(&slack.GetConversationHistoryParameters{
		ChannelID: event.Item.Channel,
		Oldest:    event.Item.Timestamp,
		Inclusive: true,
		Limit:     1,
	})
	if err != nil {
		converationHistorySpan.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		log.Printf("An Error Occured %v", err)
		return
	} else {
		converationHistorySpan.Status = sentry.SpanStatusOK
	}
	converationHistorySpan.Finish()

	// Check if the call to conversations.history hailed any results.
	// If not, try to get the message from conversations.replies (thread message).
	if len(conversationHistory.Messages) > 0 {
		text = conversationHistory.Messages[0].Text
		threadTimestamp = conversationHistory.Messages[0].ThreadTimestamp
	} else {
		converationRepliesSpan := transaction.StartChild("http.client")
		converationRepliesSpan.Description = "GET https://slack.com/api/conversations.history"

		conversationReplies, _, _, err := slackClient.GetConversationReplies(&slack.GetConversationRepliesParameters{
			ChannelID: event.Item.Channel,
			Timestamp: event.Item.Timestamp,
			Limit:     1,
		})

		if err != nil {
			converationRepliesSpan.Status = sentry.SpanStatusInternalError
			hub.CaptureException(err)
			log.Printf("An Error Occured %v", err)
			return
		} else {
			converationRepliesSpan.Status = sentry.SpanStatusOK
		}

		if len(conversationReplies) == 0 {
			return
		}

		text = conversationReplies[0].Text
		threadTimestamp = conversationReplies[0].ThreadTimestamp
	}

	permaLinkSpan := transaction.StartChild("http.client")
	permaLinkSpan.Description = "GET https://slack.com/api/chat.getPermalink"

	// Get the permalink for the original message
	permalink, err := slackClient.GetPermalink(&slack.PermalinkParameters{
		Channel: event.Item.Channel,
		Ts:      event.Item.Timestamp,
	})
	if err != nil {
		permaLinkSpan.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		log.Printf("An Error Occured %v", err)
	} else {
		permaLinkSpan.Status = sentry.SpanStatusOK
	}
	permaLinkSpan.Finish()

	reactionEvent = ReactionAddedEvent{
		Type:            reactionAdded,
		Amount:          1, // Amount is always 1 for reactions
		Sender:          event.User,
		Receivers:       utils.ReactionReceivers(text, event.User, event.ItemUser),
		Channel:         event.Item.Channel,
		Text:            text,
		Reaction:        constants.Potato, // hardoced for now
		Permalink:       permalink,
		Timestamp:       event.Item.Timestamp,
		EventTimestamp:  event.EventTimestamp,
		ThreadTimestamp: threadTimestamp,
	}

	hub.Scope().SetExtra("event", reactionEvent)
	hub.Scope().SetTag("event_type", reactionAdded.String())

	sendRequest(reactionEvent, hub, transaction)
}

func processAppMentionEvent(ctx context.Context, event *slackevents.AppMentionEvent) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctx)

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()
	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(context.Background(), "EVENT app_mention", options...)
	defer transaction.Finish()

	appMentionEvent := AppMentionEvent{
		Type:           appMention,
		Sender:         event.User,
		Channel:        event.Channel,
		Text:           event.Text,
		EventTimestamp: event.EventTimeStamp,
		BotID:          event.BotID,
	}

	if !appMentionEvent.isValid() {
		return
	}

	hub.Scope().SetExtra("event", appMentionEvent)
	hub.Scope().SetTag("event_type", appMention.String())

	sendRequest(appMentionEvent, hub, transaction)
}

func processAppHomeOpenedEvent(ctx context.Context, event *slackevents.AppHomeOpenedEvent) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctx)

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()
	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(context.Background(), "EVENT app_home_opened", options...)
	defer transaction.Finish()

	appHomeOpenedEvent := AppHomeOpenedEvent{
		Type:           appHomeOpened,
		User:           event.User,
		Tab:            event.Tab,
		EventTimestamp: event.EventTimeStamp,
	}

	if !appHomeOpenedEvent.isValid() {
		return
	}

	hub.Scope().SetExtra("event", appHomeOpenedEvent)
	hub.Scope().SetTag("event_type", appHomeOpened.String())

	sendRequest(appHomeOpenedEvent, hub, transaction)
}
