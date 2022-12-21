package main

import (
	"fmt"
	"regexp"
	"strings"

	"github.com/slack-go/slack/slackevents"
)

type Event interface {
	IsValid() bool
}

type ReactionAddedEvent struct {
	Type      string   `json:"type"`
	Amount    int      `json:"amount"`
	Sender    string   `json:"sender"`
	Receiver  []string `json:"receiver"`
	Reaction  string   `json:"reaction"`
	Item      []string `json:"text"`
	Timestamp string   `json:"timestamp"`
}

type MessageEvent struct {
	Type      string   `json:"type"`
	Amount    int      `json:"amount"`
	Sender    string   `json:"sender"`
	Receiver  []string `json:"receiver"`
	Channel   string   `json:"channel"`
	Text      string   `json:"text"`
	Timestamp string   `json:"timestamp"`
	BotID     string   `json:"-"`
}

type AppMentionEvent struct {
	Type      string `json:"type"`
	Sender    string `json:"sender"`
	Channel   string `json:"channel"`
	Text      string `json:"text"`
	Timestamp string `json:"timestamp"`
	BotID     string `json:"-"`
}

type AppHomeOpenedEvent struct {
	Type      string `json:"type"`
	User      string `json:"user"`
	Tab       string `json:"tab"`
	Timestamp string `json:"timestamp"`
}

func (e ReactionAddedEvent) IsValid() bool {
	// Only process potato reactions
	return e.Reaction == "potato"
}

func (e MessageEvent) IsValid() bool {
	// Only process messages with potato and not from a bot
	return e.Amount > 0 && e.BotID == ""
}

func (e AppMentionEvent) IsValid() bool {
	// Only process messages not from a bot
	return e.BotID == ""
}

func (e AppHomeOpenedEvent) IsValid() bool {
	// Only process home tab
	return e.Tab == "home"
}

func parseMessageEvent(event *slackevents.MessageEvent) MessageEvent {
	re := regexp.MustCompile(`<@.*?>`)
	users := re.FindAllString(event.Text, -1)

	receivers := []string{}
	unique := map[string]bool{}

	if len(users) > 0 {
		for _, user := range users {
			// Remove <@...> from the user
			user = strings.Trim(user, "<@>")
			// Onyl store unqiue users
			if _, ok := unique[user]; !ok {
				unique[user] = true
				receivers = append(receivers, user)
			}
		}
	}

	// Count potato
	amount := strings.Count(event.Text, ":potato:")

	fmt.Printf("Message Event: +%v\n", event)

	return MessageEvent{
		Type:      "message",
		Amount:    amount,
		Sender:    event.User,
		Receiver:  receivers,
		Channel:   event.Channel,
		Text:      event.Text,
		Timestamp: event.EventTimeStamp,
		BotID:     event.BotID,
	}
}

func parseReactionEvent(event *slackevents.ReactionAddedEvent) ReactionAddedEvent {
	return ReactionAddedEvent{
		Type:      "reaction_added",
		Amount:    1, // Amount is always 1 for reactions
		Sender:    event.User,
		Receiver:  []string{event.ItemUser},
		Reaction:  event.Reaction,
		Item:      []string{event.Item.Type, event.Item.Channel, event.Item.Timestamp},
		Timestamp: event.EventTimestamp,
	}
}

func parseAppMentionEvent(event *slackevents.AppMentionEvent) AppMentionEvent {
	return AppMentionEvent{
		Type:      "app_mention",
		Sender:    event.User,
		Channel:   event.Channel,
		Text:      event.Text,
		Timestamp: event.EventTimeStamp,
		BotID:     event.BotID,
	}
}

func parseAppHomeOpenedEvent(event *slackevents.AppHomeOpenedEvent) AppHomeOpenedEvent {
	return AppHomeOpenedEvent{
		Type:      "app_home_opened",
		User:      event.User,
		Tab:       event.Tab,
		Timestamp: event.EventTimeStamp,
	}
}
