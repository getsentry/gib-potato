package main

import (
	"bytes"
	"encoding/json"
	"io"
	"io/ioutil"
	"net/http"
	"os"

	"github.com/getsentry/sentry-go"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

func DefaultHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	txn := sentry.TransactionFromContext(ctx)
	txn.Source = sentry.SourceRoute

	data := map[string]string{
		"message": "The potato is a lie!",
	}
	w.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(w).Encode(data); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
}

func EventsHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

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
		if _, err := w.Write([]byte(r.Challenge)); err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			return
		}
	}

	if eventsAPIEvent.Type == slackevents.CallbackEvent {
		innerEvent := eventsAPIEvent.InnerEvent

		switch ev := innerEvent.Data.(type) {
		case *slackevents.MessageEvent:

			switch ev.ChannelType {
			case "im":
				// Handle direct messages to the bot separately
				go processDirectMessageEvent(r.Context(), ev)
			default:
				go processMessageEvent(r.Context(), ev)
			}
		case *slackevents.ReactionAddedEvent:
			go processReactionEvent(r.Context(), ev)
		case *slackevents.AppMentionEvent:
			go processAppMentionEvent(r.Context(), ev)
		case *slackevents.AppHomeOpenedEvent:
			go processAppHomeOpenedEvent(r.Context(), ev)
		}
	}

	w.WriteHeader(http.StatusOK)
}

func SlashHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Verify the Slack request
	// see https://github.com/slack-go/slack/blob/master/examples/slash/slash.go
	signingSecret := os.Getenv("SLACK_SIGNING_SECRET")
	sv, err := slack.NewSecretsVerifier(r.Header, signingSecret)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	r.Body = ioutil.NopCloser(io.TeeReader(r.Body, &sv))
	s, err := slack.SlashCommandParse(r)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	if err := sv.Ensure(); err != nil {
		w.WriteHeader(http.StatusUnauthorized)
		return
	}

	switch s.Command {
	case "/gibopinion":
		go processSlashCommand(r.Context(), s)
	default:
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
}

func InteractionsHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	body, err := ioutil.ReadAll(r.Body)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	r.Body.Close()
	r.Body = ioutil.NopCloser(bytes.NewBuffer(body))

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

	var payload slack.InteractionCallback
	jsonErr := json.Unmarshal([]byte(r.FormValue("payload")), &payload)
	if jsonErr != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	switch payload.Type {
	case slack.InteractionTypeBlockActions:
		go processInteractionCallbackEvent(r.Context(), payload)
	default:
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
}
