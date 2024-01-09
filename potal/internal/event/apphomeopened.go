package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack/slackevents"
)

type AppHomeOpenedEvent struct {
	Type           PotalEventType `json:"type"`
	User           string         `json:"user"`
	Tab            string         `json:"tab"`
	EventTimestamp string         `json:"event_timestamp"`
}

func (e AppHomeOpenedEvent) isValid() bool {
	// Only process home tab
	return e.Tab == "home"
}

func ProcessAppHomeOpenedEvent(ctx context.Context, e *slackevents.AppHomeOpenedEvent) *AppHomeOpenedEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process AppHomeOpened Event"))
	defer span.Finish()

	appHomeOpenedEvent := AppHomeOpenedEvent{
		Type:           appHomeOpened,
		User:           e.User,
		Tab:            e.Tab,
		EventTimestamp: e.EventTimeStamp,
	}

	if !appHomeOpenedEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", appHomeOpenedEvent)
	hub.Scope().SetTag("event_type", appHomeOpened.String())

	return &appHomeOpenedEvent
}
