package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack/slackevents"
)

type AppMentionEvent struct {
	Type           PotalEventType `json:"type"`
	Sender         string         `json:"sender"`
	Channel        string         `json:"channel"`
	Text           string         `json:"text"`
	EventTimestamp string         `json:"event_timestamp"`

	BotID string `json:"-"`
}

func (e AppMentionEvent) isValid() bool {
	// Only process messages not from a bot
	return e.BotID == ""
}

func ProcessAppMentionEvent(ctx context.Context, e *slackevents.AppMentionEvent) *AppMentionEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process")
	span.Description = "Process AppMention Event"
	defer span.Finish()

	appMentionEvent := AppMentionEvent{
		Type:           appMention,
		Sender:         e.User,
		Channel:        e.Channel,
		Text:           e.Text,
		EventTimestamp: e.EventTimeStamp,
		BotID:          e.BotID,
	}

	if !appMentionEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", appMentionEvent)
	hub.Scope().SetTag("event_type", appMention.String())

	return &appMentionEvent
}
