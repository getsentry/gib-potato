package event

import (
	"context"
	"fmt"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack"
)

type InteractionCallbackEvent struct {
	Type        PotalEventType `json:"type"`
	User        string         `json:"user"`
	ResponseURL string         `json:"response_url"`
	TriggerID   string         `json:"trigger_id"`
}

type BlockInteractionCallbackEvent struct {
	InteractionCallbackEvent
	ActionID          string                  `json:"action_id"`
	Value             string                  `json:"value,omitempty"`
	SelectOptionValue string                  `json:"select_option_value,omitempty"`
	Actions           []slack.ActionCallbacks `json:"actions"`
}

type ViewInteractionCallbackEvent struct {
	InteractionCallbackEvent
	ResponseURLs []slack.ViewSubmissionCallbackResponseURL `json:"response_urls"`
	View         slack.View                                `json:"view"`
}

func (e BlockInteractionCallbackEvent) isValid() bool {
	// Only process block interactions with an action_id
	return e.ActionID != ""
}

func (e ViewInteractionCallbackEvent) isValid() bool {
	// Only process view interactions with an ResponseURLs
	return e.ResponseURLs != nil
}

func ProcessBlockInteractionCallbackEvent(ctx context.Context, e slack.InteractionCallback) *BlockInteractionCallbackEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process InteractionCallback Event"))
	defer span.Finish()

	if ok := e.ActionCallback.BlockActions[0].ActionID; ok == "" {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}

	interactionEvent := BlockInteractionCallbackEvent{}
	interactionEvent.Type = interactionCallback
	interactionEvent.User = e.User.ID
	interactionEvent.ActionID = e.ActionCallback.BlockActions[0].ActionID
	interactionEvent.ResponseURL = e.ResponseURL
	interactionEvent.TriggerID = e.TriggerID

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

func ProcessViewInteractionCallbackEvent(ctx context.Context, e slack.InteractionCallback) *ViewInteractionCallbackEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process InteractionCallback Event"))
	defer span.Finish()

	interactionEvent := ViewInteractionCallbackEvent{}
	interactionEvent.Type = interactionCallback
	interactionEvent.User = e.User.ID
	interactionEvent.ResponseURL = e.ResponseURL
	interactionEvent.ResponseURLs = e.ResponseURLs
	interactionEvent.TriggerID = e.TriggerID
	interactionEvent.View = e.View

	fmt.Printf("Event: %+v\n", e)
	fmt.Printf("ResponseURL: %s\n", e.ResponseURL)
	fmt.Printf("ResponseURLs: %+v\n", e.ResponseURLs)
	fmt.Printf("View: %+v\n", e.View)

	if !interactionEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", interactionEvent)
	hub.Scope().SetTag("event_type", interactionCallback.String())

	return &interactionEvent
}
