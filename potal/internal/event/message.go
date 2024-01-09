package event

import (
	"context"
	"log"

	"github.com/getsentry/gib-potato/internal/constants"
	"github.com/getsentry/gib-potato/internal/utils"
	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

type MessageEvent struct {
	Type           PotalEventType `json:"type"`
	Amount         int            `json:"amount"`
	Sender         string         `json:"sender"`
	Receivers      []string       `json:"receivers"`
	Channel        string         `json:"channel"`
	Text           string         `json:"text"`
	Reaction       string         `json:"reaction"`
	Timestamp      string         `json:"timestamp"`
	EventTimestamp string         `json:"event_timestamp"`
	Permalink      string         `json:"permalink"`

	ThreadTimestamp string `json:"thread_timestamp,omitempty"`

	BotID string `json:"-"`
}

func (e MessageEvent) isValid() bool {
	// Only process messages with potato, not from a bot and not sent by slackbot
	return e.Amount > 0 &&
		e.BotID == "" &&
		e.Sender != "USLACKBOT"
}

func ProcessMessageEvent(ctx context.Context, e *slackevents.MessageEvent, sc *slack.Client) *MessageEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process Message Event"))
	defer span.Finish()

	messageEvent := MessageEvent{
		Type:            message,
		Amount:          utils.MessageAmount(e.Text),
		Sender:          e.User,
		Receivers:       utils.MessageReceivers(e.Text),
		Channel:         e.Channel,
		Text:            e.Text,
		Reaction:        constants.Potato, // hardoced for now
		Timestamp:       e.TimeStamp,
		EventTimestamp:  e.EventTimeStamp,
		ThreadTimestamp: e.ThreadTimeStamp,
		BotID:           e.BotID,
	}

	if !messageEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}

	permalinkSpan := span.StartChild("http.client")
	permalinkSpan.Description = "GET https://slack.com/api/chat.getPermalink "

	// Get the permalink for the message
	permalink, err := sc.GetPermalink(&slack.PermalinkParameters{
		Channel: e.Channel,
		Ts:      e.TimeStamp,
	})
	if err != nil {
		permalinkSpan.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		log.Printf("An Error Occured %v", err)
	} else {
		permalinkSpan.Status = sentry.SpanStatusOK
	}
	permalinkSpan.Finish()

	messageEvent.Permalink = permalink

	hub.Scope().SetExtra("event", messageEvent)
	hub.Scope().SetTag("event_type", message.String())

	return &messageEvent
}
