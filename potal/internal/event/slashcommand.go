package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack"
)

type SlashCommandEvent struct {
	Type    PotalEventType `json:"type"`
	Command string         `json:"command"`
	User    string         `json:"user"`
	Channel string         `json:"channel"`
	Text    string         `json:"text"`
}

func (e SlashCommandEvent) isValid() bool {
	return true
}

func ProcessSlashCommand(ctx context.Context, e slack.SlashCommand) *SlashCommandEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process SlashCommand Event"))
	defer span.Finish()

	slashCommandEvent := SlashCommandEvent{
		Type:    slashCommand,
		Command: e.Command,
		User:    e.UserID,
		Channel: e.ChannelID,
		Text:    e.Text,
	}

	if !slashCommandEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", slashCommandEvent)
	hub.Scope().SetTag("event_type", slashCommand.String())

	return &slashCommandEvent
}
