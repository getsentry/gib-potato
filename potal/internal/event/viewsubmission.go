package event

import (
	"context"

	"github.com/getsentry/sentry-go"
	"github.com/slack-go/slack"
)

type ViewSubmissionEvent struct {
	Type       PotalEventType    `json:"type"`
	User       string            `json:"user"`
	CallbackID string            `json:"callback_id"`
	Values     map[string]string `json:"values"`
}

func (e ViewSubmissionEvent) isValid() bool {
	return e.CallbackID != ""
}

func ProcessViewSubmissionEvent(ctx context.Context, e slack.InteractionCallback) *ViewSubmissionEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process ViewSubmission Event"))
	defer span.Finish()

	values := make(map[string]string)
	for blockID, blockActions := range e.View.State.Values {
		for actionID, action := range blockActions {
			key := blockID + "." + actionID
			if action.SelectedOption.Value != "" {
				values[key] = action.SelectedOption.Value
			} else if action.SelectedDate != "" {
				values[key] = action.SelectedDate
			} else {
				values[key] = action.Value
			}
		}
	}

	viewSubmissionEvent := ViewSubmissionEvent{
		Type:       viewSubmission,
		User:       e.User.ID,
		CallbackID: e.View.CallbackID,
		Values:     values,
	}

	if !viewSubmissionEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}
	span.Status = sentry.SpanStatusOK

	hub.Scope().SetContext("event", sentry.Context{"data": viewSubmissionEvent})
	hub.Scope().SetTag("event_type", viewSubmission.String())

	return &viewSubmissionEvent
}
