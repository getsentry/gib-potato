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
	BotID          string    `json:"-"`
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

func processMessageEvent(event *slackevents.MessageEvent, ctxx context.Context) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctxx)
	ctx := context.Background()

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()
	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(ctx, "EVENT message", options...)
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
		log.Fatalf("An Error Occured %v", err)
	} else {
		span.Status = sentry.SpanStatusOK
	}
	messageEvent.Permalink = permalink
	span.Finish()

	hub.Scope().SetExtra("event", messageEvent)
	hub.Scope().SetTag("event_type", message.String())

	sendRequest(messageEvent, hub, transaction)
}

func processReactionEvent(event *slackevents.ReactionAddedEvent, ctxx context.Context) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctxx)
	ctx := context.Background()

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()
	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(ctx, "EVENT reaction_added", options...)
	defer transaction.Finish()

	reactionEvent := ReactionAddedEvent{
		Reaction: event.Reaction,
	}

	if !reactionEvent.isValid() {
		return
	}

	conversationHistory, err := slackClient.GetConversationHistory(&slack.GetConversationHistoryParameters{
		ChannelID: event.Item.Channel,
		Latest:    event.Item.Timestamp,
		Inclusive: true,
		Limit:     1,
	})
	if err != nil {
		hub.CaptureException(err)
		log.Fatalf("An Error Occured %v", err)
		return
	}

	if len(conversationHistory.Messages) == 0 {
		return
	}
	text := conversationHistory.Messages[0].Text
	permalink := conversationHistory.Messages[0].Permalink
	threadTimestamp := conversationHistory.Messages[0].ThreadTimestamp

	reactionEvent = ReactionAddedEvent{
		Type:            reactionAdded,
		Amount:          1, // Amount is always 1 for reactions
		Sender:          event.User,
		Receivers:       utils.ReactionReceivers(text, event.ItemUser),
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

func processAppMentionEvent(event *slackevents.AppMentionEvent, ctxx context.Context) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctxx)
	ctx := context.Background()

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()
	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(ctx, "EVENT app_mention", options...)
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

func processAppHomeOpenedEvent(event *slackevents.AppHomeOpenedEvent, ctxx context.Context) {
	// Recive the transaction attached to the context
	httpTxn := sentry.TransactionFromContext(ctxx)
	ctx := context.Background()

	// Clone the current hub
	hub := sentry.CurrentHub().Clone()
	options := []sentry.SpanOption{
		sentry.OpName("event.process"),
		sentry.TransctionSource(sentry.SourceTask),
		// Continue the trace
		sentry.ContinueFromHeaders(httpTxn.ToSentryTrace(), httpTxn.ToBaggage()),
	}
	transaction := sentry.StartTransaction(ctx, "EVENT app_home_opened", options...)
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
