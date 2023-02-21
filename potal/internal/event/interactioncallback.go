package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack"
)

type InteractionCallbackEvent struct {
	Type              PotalEventType `json:"type"`
	User              string         `json:"user"`
	ActionID          string         `json:"action_id"`
	ResponseURL       string         `json:"response_url"`
	TriggerID         string         `json:"trigger_id,omitempty"`
	Value             string         `json:"value,omitempty"`
	SelectOptionValue string         `json:"select_option_value,omitempty"`
}

func (e InteractionCallbackEvent) isValid() bool {
	// Only process interactions with an action_id
	return e.ActionID != ""
}

func ProcessInteractionCallbackEvent(ctx context.Context, e slack.InteractionCallback) *InteractionCallbackEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process")
	span.Description = "Process InteractionCallback Event"
	defer span.Finish()

	if ok := e.ActionCallback.BlockActions[0].ActionID; ok == "" {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}

	interactionEvent := InteractionCallbackEvent{
		Type:        interactionCallback,
		User:        e.User.ID,
		ActionID:    e.ActionCallback.BlockActions[0].ActionID,
		ResponseURL: e.ResponseURL,
		TriggerID:   e.TriggerID,
	}
	if ok := e.ActionCallback.BlockActions[0].Value; ok != "" {
		interactionEvent.Value = e.ActionCallback.BlockActions[0].Value
	}
	if ok := e.ActionCallback.BlockActions[0].SelectedOption.Value; ok != "" {
		interactionEvent.SelectOptionValue = e.ActionCallback.BlockActions[0].SelectedOption.Value
	}

	if !interactionEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", interactionEvent)
	hub.Scope().SetTag("event_type", interactionCallback.String())

	return &interactionEvent
}
