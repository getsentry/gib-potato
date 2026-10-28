package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack/slackevents"
)

type AppMentionEvent struct {
	Type            PotalEventType `json:"type"`
	Sender          string         `json:"sender"`
	Channel         string         `json:"channel"`
	Text            string         `json:"text"`
	Timestamp       string         `json:"timestamp"`
	EventTimestamp  string         `json:"event_timestamp"`
	ThreadTimestamp string         `json:"thread_timestamp,omitempty"`

	BotID string `json:"-"`
}

func (e AppMentionEvent) isValid() bool {
	// Only process messages not from a bot
	return e.BotID == ""
}

func ProcessAppMentionEvent(ctx context.Context, e *slackevents.AppMentionEvent) *AppMentionEvent {
	scope := sentry.ScopeFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process AppMention Event"))
	defer span.Finish()

	appMentionEvent := AppMentionEvent{
		Type:            appMention,
		Sender:          e.User,
		Channel:         e.Channel,
		Text:            e.Text,
		Timestamp:       e.TimeStamp,
		EventTimestamp:  e.EventTimeStamp,
		ThreadTimestamp: e.ThreadTimeStamp,
		BotID:           e.BotID,
	}

	if !appMentionEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	scope.SetContext("event", sentry.Context{"data": appMentionEvent})
	scope.SetTag("event_type", appMention.String())

	return &appMentionEvent
}
