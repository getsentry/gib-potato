package main

import (
	"log"

	"github.com/getsentry/gib-potato/internal/utils"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

type Event interface {
	isValid() bool
}

type MessageEvent struct {
	Type           string   `json:"type"`
	Amount         int      `json:"amount"`
	Sender         string   `json:"sender"`
	Receivers      []string `json:"receivers"`
	Channel        string   `json:"channel"`
	Text           string   `json:"text"`
	Timestamp      string   `json:"timestamp"`
	EventTimestamp string   `json:"event_timestamp"`
	Permalink      string   `json:"permalink"`

	ThreadTimestamp string `json:"thread_timestamp,omitempty"`

	BotID string `json:"-"`
}

type ReactionAddedEvent struct {
	Type           string   `json:"type"`
	Amount         int      `json:"amount"`
	Sender         string   `json:"sender"`
	Receivers      []string `json:"receivers"`
	Channel        string   `json:"channel"`
	Text           string   `json:"text"`
	Reaction       string   `json:"reaction"`
	Timestamp      string   `json:"timestamp"`
	EventTimestamp string   `json:"event_timestamp"`
	Permalink      string   `json:"permalink"`

	ThreadTimestamp string `json:"thread_timestamp,omitempty"`
}

type AppMentionEvent struct {
	Type           string `json:"type"`
	Sender         string `json:"sender"`
	Channel        string `json:"channel"`
	Text           string `json:"text"`
	EventTimestamp string `json:"event_timestamp"`
	BotID          string `json:"-"`
}

type AppHomeOpenedEvent struct {
	Type           string `json:"type"`
	User           string `json:"user"`
	Tab            string `json:"tab"`
	EventTimestamp string `json:"event_timestamp"`
}

func (e MessageEvent) isValid() bool {
	// Only process messages with potato and not from a bot
	return e.Amount > 0 && e.BotID == ""
}

func (e ReactionAddedEvent) isValid() bool {
	// Only process potato reactions
	return e.Reaction == "potato"
}

func (e AppMentionEvent) isValid() bool {
	// Only process messages not from a bot
	return e.BotID == ""
}

func (e AppHomeOpenedEvent) isValid() bool {
	// Only process home tab
	return e.Tab == "home"
}

func processMessageEvent(event *slackevents.MessageEvent) {
	messageEvent := MessageEvent{
		Type:            "message",
		Amount:          utils.MessageAmount(event.Text),
		Sender:          event.User,
		Receivers:       utils.MessageReceivers(event.Text),
		Channel:         event.Channel,
		Text:            event.Text,
		Timestamp:       event.TimeStamp,
		EventTimestamp:  event.EventTimeStamp,
		ThreadTimestamp: event.ThreadTimeStamp,
		BotID:           event.BotID,
	}

	if !messageEvent.isValid() {
		return
	}

	// Get the permalink for the message
	permalink, err := slackClient.GetPermalink(&slack.PermalinkParameters{
		Channel: event.Channel,
		Ts:      event.TimeStamp,
	})
	if err == nil {
		messageEvent.Permalink = permalink
	}

	sendRequest(messageEvent)
}

func processReactionEvent(event *slackevents.ReactionAddedEvent) {
	reactionEvent := ReactionAddedEvent{
		Reaction: event.Reaction,
	}

	if !reactionEvent.isValid() {
		return
	}

	conversationHistory, err := slackClient.GetConversationHistory(&slack.GetConversationHistoryParameters{
		ChannelID: event.Item.Channel,
		Latest:    event.Item.Timestamp,
		Inclusive: true,
		Limit:     1,
	})
	if err != nil {
		log.Fatalf("An Error Occured %v", err)
		return
	}

	if len(conversationHistory.Messages) == 0 {
		return
	}
	text := conversationHistory.Messages[0].Text
	permalink := conversationHistory.Messages[0].Permalink
	threadTimestamp := conversationHistory.Messages[0].ThreadTimestamp

	reactionEvent = ReactionAddedEvent{
		Type:            "reaction_added",
		Amount:          1, // Amount is always 1 for reactions
		Sender:          event.User,
		Receivers:       utils.ReactionReceivers(text, event.ItemUser),
		Channel:         event.Item.Channel,
		Text:            text,
		Reaction:        event.Reaction,
		Permalink:       permalink,
		Timestamp:       event.Item.Timestamp,
		EventTimestamp:  event.EventTimestamp,
		ThreadTimestamp: threadTimestamp,
	}

	sendRequest(reactionEvent)
}

func processAppMentionEvent(event *slackevents.AppMentionEvent) {
	appMentionEvent := AppMentionEvent{
		Type:           "app_mention",
		Sender:         event.User,
		Channel:        event.Channel,
		Text:           event.Text,
		EventTimestamp: event.EventTimeStamp,
		BotID:          event.BotID,
	}

	if !appMentionEvent.isValid() {
		return
	}

	sendRequest(appMentionEvent)
}

func processAppHomeOpenedEvent(event *slackevents.AppHomeOpenedEvent) {
	appHomeOpenedEvent := AppHomeOpenedEvent{
		Type:           "app_home_opened",
		User:           event.User,
		Tab:            event.Tab,
		EventTimestamp: event.EventTimeStamp,
	}

	if !appHomeOpenedEvent.isValid() {
		return
	}

	sendRequest(appHomeOpenedEvent)
}
