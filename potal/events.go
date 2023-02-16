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
	message             EventType = "message"
	directMessage       EventType = "direct_message"
	reactionAdded       EventType = "reaction_added"
	appMention          EventType = "app_mention"
	appHomeOpened       EventType = "app_home_opened"
	slashCommand        EventType = "slash_command"
	interactionCallback EventType = "interaction_callback"
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

type SlashCommandEvent struct {
	Type    EventType `json:"type"`
	Command string    `json:"command"`
	User    string    `json:"user"`
	Channel string    `json:"channel"`
	Text    string    `json:"text"`
}

type InteractionCallbackEvent struct {
	Type        EventType `json:"type"`
	User        string    `json:"user"`
	ActionID    string    `json:"action_id"`
	ResponseURL string    `json:"response_url"`
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

func (e SlashCommandEvent) isValid() bool {
	return true
}

func (e InteractionCallbackEvent) isValid() bool {
	return true
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

	coversationRepliesSpan := transaction.StartChild("http.client")
	coversationRepliesSpan.Description = "GET https://slack.com/api/conversations.replies"
	defer coversationRepliesSpan.Finish()

	conversationReplies, _, _, err := slackClient.GetConversationReplies(&slack.GetConversationRepliesParameters{
		ChannelID: event.Item.Channel,
		Timestamp: event.Item.Timestamp,
		Limit:     1,
	})

	if err != nil {
		coversationRepliesSpan.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		log.Printf("An Error Occured %v", err)
		return
	}

	coversationRepliesSpan.Status = sentry.SpanStatusOK

	if len(conversationReplies) == 0 {
		return
	}
	text := conversationReplies[0].Text
	threadTimestamp := conversationReplies[0].ThreadTimestamp

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

func processSlashCommand(ctx context.Context, event slack.SlashCommand) {
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
	transaction := sentry.StartTransaction(context.Background(), "EVENT slash_command", options...)
	defer transaction.Finish()

	slashCommandEvent := SlashCommandEvent{
		Type:    slashCommand,
		Command: event.Command,
		User:    event.UserID,
		Channel: event.ChannelID,
		Text:    event.Text,
	}

	if !slashCommandEvent.isValid() {
		return
	}

	hub.Scope().SetExtra("event", slashCommandEvent)
	hub.Scope().SetTag("event_type", slashCommand.String())

	sendRequest(slashCommandEvent, hub, transaction)
}

func processInteractionCallbackEvent(ctx context.Context, event slack.InteractionCallback) {
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
	transaction := sentry.StartTransaction(context.Background(), "EVENT interaction_callback", options...)
	defer transaction.Finish()

	if ok := event.ActionCallback.BlockActions[0].ActionID; ok == "" {
		return
	}

	interactionEvent := InteractionCallbackEvent{
		Type:        interactionCallback,
		User:        event.User.ID,
		ActionID:    event.ActionCallback.BlockActions[0].ActionID,
		ResponseURL: event.ResponseURL,
	}

	if !interactionEvent.isValid() {
		return
	}

	hub.Scope().SetExtra("event", interactionEvent)
	hub.Scope().SetTag("event_type", interactionCallback.String())

	sendRequest(interactionEvent, hub, transaction)
}
