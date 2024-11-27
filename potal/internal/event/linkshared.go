package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack/slackevents"
)

type LinkSharedEvent struct {
	Type           PotalEventType `json:"type"`
	User           string         `json:"user"`
	TimeStamp      string         `json:"ts"`
	Channel        string         `json:"channel"`
	MessageTimeStamp string        `json:"message_ts"`
	ThreadTimeStamp  string        `json:"thread_ts"`
	Links            []Link `json:"links"`
	EventTimestamp   string        `json:"event_ts"`
}

type Link struct {
	Domain string `json:"domain"`
	URL    string `json:"url"`
}

func (e LinkSharedEvent) isValid() bool {
	return len(e.Links) > 0
}

func ProcessLinkSharedEvent(ctx context.Context, e *slackevents.LinkSharedEvent) *LinkSharedEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process LinkShared Event"))
	defer span.Finish()

	links := make([]Link, len(e.Links))
	for i, link := range e.Links {
		links[i] = Link{
			Domain: link.Domain,
			URL:    link.URL,
		}
	}

	linkSharedEvent := LinkSharedEvent{
		Type:           linkShared,
		User:           e.User,
		TimeStamp:      e.TimeStamp,
		Channel:        e.Channel,
		MessageTimeStamp: e.MessageTimeStamp,
		ThreadTimeStamp:  e.ThreadTimeStamp,
		Links:            links,
		EventTimestamp:   e.EventTimestamp,
	}

	if !linkSharedEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", linkSharedEvent)
	hub.Scope().SetTag("event_type", linkShared.String())

	return &linkSharedEvent
}
