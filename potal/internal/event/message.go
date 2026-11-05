package event

import (
	"context"
	"log/slog"

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
	// Only process messages with potato or super potato, not from a bot and not sent by slackbot
	return e.Amount > 0 &&
		e.BotID == "" &&
		e.Sender != "USLACKBOT"
}

// ProcessMessageEvent returns one event per kind of potato in the message,
// so potatoes and super potatoes in the same message are both awarded.
func ProcessMessageEvent(ctx context.Context, e *slackevents.MessageEvent, sc *slack.Client) []*MessageEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process Message Event"))
	defer span.Finish()

	amounts := []struct {
		reaction string
		amount   int
	}{
		{constants.Potato, utils.MessageAmount(e.Text)},
		{constants.SuperPotato, utils.MessageSuperPotatoAmount(e.Text)},
	}

	var messageEvents []*MessageEvent
	for _, a := range amounts {
		messageEvent := MessageEvent{
			Type:            message,
			Amount:          a.amount,
			Sender:          e.User,
			Receivers:       utils.MessageReceivers(e.Text),
			Channel:         e.Channel,
			Text:            e.Text,
			Reaction:        a.reaction,
			Timestamp:       e.TimeStamp,
			EventTimestamp:  e.EventTimeStamp,
			ThreadTimestamp: e.ThreadTimeStamp,
			BotID:           e.BotID,
		}
		if messageEvent.isValid() {
			messageEvents = append(messageEvents, &messageEvent)
		}
	}

	if len(messageEvents) == 0 {
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
		slog.ErrorContext(ctx, "failed to get permalink", "error", err, "channel", e.Channel)
	} else {
		permalinkSpan.Status = sentry.SpanStatusOK
	}
	permalinkSpan.Finish()

	for _, messageEvent := range messageEvents {
		messageEvent.Permalink = permalink
		messageEvent.Text = utils.ScrubText(messageEvent.Text)
	}

	hub.Scope().SetContext("event", sentry.Context{"data": messageEvents})
	hub.Scope().SetTag("event_type", message.String())

	return messageEvents
}
