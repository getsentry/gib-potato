package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"

	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

func DefaultHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	data := map[string]string{
		"message": "The potato is a lie!",
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(data)
}

func EventsHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Verify the Slack request
	// see https://github.com/slack-go/slack/blob/master/examples/eventsapi/events.go
	body, err := io.ReadAll(r.Body)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	signingSecret := os.Getenv("SLACK_SIGNING_SECRET")
	sv, err := slack.NewSecretsVerifier(r.Header, signingSecret)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	if _, err := sv.Write(body); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	if err := sv.Ensure(); err != nil {
		w.WriteHeader(http.StatusUnauthorized)
		return
	}
	eventsAPIEvent, err := slackevents.ParseEvent(json.RawMessage(body), slackevents.OptionNoVerifyToken())
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	if eventsAPIEvent.Type == slackevents.URLVerification {
		var r *slackevents.ChallengeResponse
		err := json.Unmarshal([]byte(body), &r)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			return
		}
		w.Header().Set("Content-Type", "text")
		w.Write([]byte(r.Challenge))
	}

	fmt.Printf("Event received: %+v\n", eventsAPIEvent.Type)
	fmt.Printf("Event received: %+v\n", eventsAPIEvent.InnerEvent.Type)

	if eventsAPIEvent.Type == slackevents.CallbackEvent {
		var event Event
		innerEvent := eventsAPIEvent.InnerEvent

		switch ev := innerEvent.Data.(type) {
		case *slackevents.ReactionAddedEvent:
			event = parseReactionEvent(ev)
		case *slackevents.MessageEvent:
			event = parseMessageEvent(ev)
		case *slackevents.AppMentionEvent:
			event = parseAppMentionEvent(ev)
		case *slackevents.AppHomeOpenedEvent:
			event = parseAppHomeOpenedEvent(ev)
		}

		if event != nil && event.IsValid() {
			fmt.Printf("Event is valid: %+v\n", event)
			go sendRequest(event)
		}
	}

	w.WriteHeader(http.StatusOK)
}
