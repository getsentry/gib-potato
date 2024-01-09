package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack/slackevents"
)

type DirectEvent struct {
	Type           PotalEventType `json:"type"`
	Sender         string         `json:"sender"`
	Channel        string         `json:"channel"`
	Text           string         `json:"text"`
	Timestamp      string         `json:"timestamp"`
	EventTimestamp string         `json:"event_timestamp"`

	BotID string `json:"-"`
}

func (e DirectEvent) isValid() bool {
	// Only process messages not from a bot
	return e.BotID == ""
}

func ProcessDirectMessageEvent(ctx context.Context, e *slackevents.MessageEvent) *DirectEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process Direct Message Event"))
	defer span.Finish()

	directMessageEvent := DirectEvent{
		Type:           directMessage,
		Sender:         e.User,
		Channel:        e.Channel,
		Text:           e.Text,
		Timestamp:      e.TimeStamp,
		EventTimestamp: e.EventTimeStamp,
		BotID:          e.BotID,
	}

	if !directMessageEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", directMessageEvent)
	hub.Scope().SetTag("event_type", directMessage.String())

	return &directMessageEvent
}
