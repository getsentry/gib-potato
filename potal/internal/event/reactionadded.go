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

type ReactionAddedEvent struct {
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
}

func (e ReactionAddedEvent) isValid() bool {
	// Only process potato reactions
	return e.Reaction == "potato"
}

func ProcessReactionEvent(ctx context.Context, e *slackevents.ReactionAddedEvent, sc *slack.Client) *ReactionAddedEvent {
	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("event.process", sentry.WithDescription("Process ReactionAdded Event"))
	defer span.Finish()

	reactionEvent := ReactionAddedEvent{
		Reaction: e.Reaction,
	}

	if !reactionEvent.isValid() {
		span.Status = sentry.SpanStatusInvalidArgument
		return nil
	}

	conversationsSpan := span.StartChild("http.client")
	conversationsSpan.Description = "GET https://slack.com/api/conversations.replies"

	conversationReplies, _, _, err := sc.GetConversationReplies(&slack.GetConversationRepliesParameters{
		ChannelID: e.Item.Channel,
		Timestamp: e.Item.Timestamp,
		Limit:     1,
	})
	if err != nil {
		conversationsSpan.Status = sentry.SpanStatusInternalError
		span.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		log.Printf("An Error Occured %v", err)
		return nil
	}
	conversationsSpan.Status = sentry.SpanStatusOK
	conversationsSpan.Finish()

	if len(conversationReplies) == 0 {
		return nil
	}
	text := conversationReplies[0].Text
	threadTimestamp := conversationReplies[0].ThreadTimestamp

	permaLinkSpan := span.StartChild("http.client")
	permaLinkSpan.Description = "GET https://slack.com/api/chat.getPermalink"

	// Get the permalink for the original message
	permalink, err := sc.GetPermalink(&slack.PermalinkParameters{
		Channel: e.Item.Channel,
		Ts:      e.Item.Timestamp,
	})
	if err != nil {
		permaLinkSpan.Status = sentry.SpanStatusInternalError
		hub.CaptureException(err)
		log.Printf("An Error Occured %v", err)
	} else {
		permaLinkSpan.Status = sentry.SpanStatusOK
	}
	permaLinkSpan.Finish()

	reactionEvent = ReactionAddedEvent{
		Type:            reactionAdded,
		Amount:          1, // Amount is always 1 for reactions
		Sender:          e.User,
		Receivers:       utils.ReactionReceivers(text, e.User, e.ItemUser),
		Channel:         e.Item.Channel,
		Text:            text,
		Reaction:        constants.Potato, // hardoced for now
		Permalink:       permalink,
		Timestamp:       e.Item.Timestamp,
		EventTimestamp:  e.EventTimestamp,
		ThreadTimestamp: threadTimestamp,
	}

	span.Status = sentry.SpanStatusOK

	hub.Scope().SetExtra("event", reactionEvent)
	hub.Scope().SetTag("event_type", reactionAdded.String())

	return &reactionEvent
}
