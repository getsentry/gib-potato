package event

import (
	"context"
	"log/slog"

	"github.com/getsentry/gib-potato/internal/constants"
	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

type ReactionRemovedEvent struct {
	Type      PotalEventType `json:"type"`
	Sender    string         `json:"sender"`
	Channel   string         `json:"channel"`
	Reaction  string         `json:"reaction"`
	Timestamp string         `json:"timestamp"`

	IsBot bool `json:"-"`
}

func (e ReactionRemovedEvent) isValid() bool {
	return e.Reaction == constants.PotatoVoucher && !e.IsBot
}

func ProcessReactionRemovedEvent(ctx context.Context, e *slackevents.ReactionRemovedEvent, sc *slack.Client) *ReactionRemovedEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process ReactionRemoved Event"))
	defer span.Finish()

	userSpan := span.StartChild("http.client")
	userSpan.Description = "GET https://slack.com/api/users.info"

	user, err := sc.GetUserInfo(e.User)
	if err != nil {
		userSpan.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		slog.ErrorContext(ctx, "failed to get user", "error", err, "user", e.User)
		return nil
	} else {
		userSpan.Status = sentry.SpanStatusOK
	}
	userSpan.Finish()

	removedEvent := ReactionRemovedEvent{
		Reaction: e.Reaction,
		IsBot:    user.IsBot,
	}

	if !removedEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}

	removedEvent = ReactionRemovedEvent{
		Type:      reactionRemoved,
		Sender:    e.User,
		Channel:   e.Item.Channel,
		Reaction:  ":" + e.Reaction + ":",
		Timestamp: e.Item.Timestamp,
	}

	span.Status = sentry.SpanStatusOK

	hub.Scope().SetContext("event", sentry.Context{"data": removedEvent})
	hub.Scope().SetTag("event_type", reactionRemoved.String())

	return &removedEvent
}
