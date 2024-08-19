package main

import (
	"context"
	"encoding/json"
	"io"
	"net/http"

	"github.com/getsentry/gib-potato/internal/event"
	"github.com/getsentry/gib-potato/internal/potalhttp"
	"github.com/getsentry/sentry-go"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

func DefaultHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	data := map[string]string{
		"message": "The potato is a lie!",
	}
	w.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(w).Encode(data); err != nil {
		transaction.Status = sentry.SpanStatusInternalError
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	transaction.Status = sentry.SpanStatusOK
}

func ErrorHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	sentry.CaptureMessage("This is an error 🔥")

	transaction.Status = sentry.SpanStatusInternalError
	w.WriteHeader(http.StatusInternalServerError)
}

func EventsHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	body, err := io.ReadAll(r.Body)
	if err != nil {
		transaction.Status = sentry.SpanStatusInvalidArgument
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	eventsAPIEvent, err := slackevents.ParseEvent(json.RawMessage(body), slackevents.OptionNoVerifyToken())
	if err != nil {
		transaction.Status = sentry.SpanStatusInternalError
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	if eventsAPIEvent.Type == slackevents.URLVerification {
		var r *slackevents.ChallengeResponse
		err := json.Unmarshal([]byte(body), &r)
		if err != nil {
			transaction.Status = sentry.SpanStatusInternalError
			w.WriteHeader(http.StatusInternalServerError)
			return
		}
		w.Header().Set("Content-Type", "text")
		if _, err := w.Write([]byte(r.Challenge)); err != nil {
			transaction.Status = sentry.SpanStatusInternalError
			w.WriteHeader(http.StatusInternalServerError)
			return
		}

		transaction.Status = sentry.SpanStatusOK
		w.WriteHeader(http.StatusOK)
		return
	}

	if eventsAPIEvent.Type == slackevents.CallbackEvent {
		innerEvent := eventsAPIEvent.InnerEvent

		switch ev := innerEvent.Data.(type) {
		case *slackevents.MessageEvent:

			switch ev.ChannelType {
			case "im":
				// Handle direct messages to the bot separately
				go func() {
					hub := sentry.CurrentHub().Clone()
					ctx := sentry.SetHubOnContext(context.Background(), hub)

					options := []sentry.SpanOption{
						sentry.WithOpName("event.handler"),
						sentry.WithTransactionSource(sentry.SourceTask),
						sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
					}
					txn := sentry.StartTransaction(ctx, "EVENT direct_message", options...)
					defer txn.Finish()

					processedEvent := event.ProcessDirectMessageEvent(txn.Context(), ev)
					if processedEvent == nil {
						return
					}
					err := potalhttp.SendRequest(txn.Context(), processedEvent)
					if err != nil {
						txn.Status = sentry.SpanStatusInternalError
						return
					}

					txn.Status = sentry.SpanStatusOK
				}()
			default:
				go func() {
					hub := sentry.CurrentHub().Clone()
					ctx := sentry.SetHubOnContext(context.Background(), hub)

					options := []sentry.SpanOption{
						sentry.WithOpName("event.handler"),
						sentry.WithTransactionSource(sentry.SourceTask),
						sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
					}
					txn := sentry.StartTransaction(ctx, "EVENT message", options...)
					defer txn.Finish()

					processedEvent := event.ProcessMessageEvent(txn.Context(), ev, slackClient)
					if processedEvent == nil {
						return
					}
					err := potalhttp.SendRequest(txn.Context(), processedEvent)
					if err != nil {
						txn.Status = sentry.SpanStatusInternalError
						return
					}

					txn.Status = sentry.SpanStatusOK
				}()
			}
		case *slackevents.ReactionAddedEvent:
			go event.ProcessReactionEvent(r.Context(), ev, slackClient)
			go func() {
				hub := sentry.CurrentHub().Clone()
				ctx := sentry.SetHubOnContext(context.Background(), hub)

				options := []sentry.SpanOption{
					sentry.WithOpName("event.handler"),
					sentry.WithTransactionSource(sentry.SourceTask),
					sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
				}
				txn := sentry.StartTransaction(ctx, "EVENT reaction_added", options...)
				defer txn.Finish()

				processedEvent := event.ProcessReactionEvent(txn.Context(), ev, slackClient)
				if processedEvent == nil {
					txn.Status = sentry.SpanStatusInternalError
					return
				}
				err := potalhttp.SendRequest(txn.Context(), processedEvent)
				if err != nil {
					txn.Status = sentry.SpanStatusInternalError
					return
				}

				txn.Status = sentry.SpanStatusOK
			}()
		case *slackevents.AppMentionEvent:
			go event.ProcessAppMentionEvent(r.Context(), ev)
			go func() {
				hub := sentry.CurrentHub().Clone()
				ctx := sentry.SetHubOnContext(context.Background(), hub)

				options := []sentry.SpanOption{
					sentry.WithOpName("event.handler"),
					sentry.WithTransactionSource(sentry.SourceTask),
					sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
				}
				txn := sentry.StartTransaction(ctx, "EVENT app_mention", options...)
				defer txn.Finish()

				processedEvent := event.ProcessAppMentionEvent(txn.Context(), ev)
				if processedEvent == nil {
					txn.Status = sentry.SpanStatusInternalError
					return
				}
				err := potalhttp.SendRequest(txn.Context(), processedEvent)
				if err != nil {
					txn.Status = sentry.SpanStatusInternalError
					return
				}

				txn.Status = sentry.SpanStatusOK
			}()
		case *slackevents.AppHomeOpenedEvent:
			go event.ProcessAppHomeOpenedEvent(r.Context(), ev)
			go func() {
				hub := sentry.CurrentHub().Clone()
				ctx := sentry.SetHubOnContext(context.Background(), hub)

				options := []sentry.SpanOption{
					sentry.WithOpName("event.handler"),
					sentry.WithTransactionSource(sentry.SourceTask),
					sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
				}
				txn := sentry.StartTransaction(ctx, "EVENT app_home_opened", options...)
				defer txn.Finish()

				processedEvent := event.ProcessAppHomeOpenedEvent(txn.Context(), ev)
				if processedEvent == nil {
					txn.Status = sentry.SpanStatusInternalError
					return
				}
				err := potalhttp.SendRequest(txn.Context(), processedEvent)
				if err != nil {
					txn.Status = sentry.SpanStatusInternalError
					return
				}

				txn.Status = sentry.SpanStatusOK
			}()
		}
	}

	transaction.Status = sentry.SpanStatusOK
	w.WriteHeader(http.StatusOK)
}

func SlashHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	s, err := slack.SlashCommandParse(r)
	if err != nil {
		transaction.Status = sentry.SpanStatusInternalError
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	switch s.Command {
	case "/gibopinion":
		go func() {
			hub := sentry.CurrentHub().Clone()
			ctx := sentry.SetHubOnContext(context.Background(), hub)

			options := []sentry.SpanOption{
				sentry.WithOpName("command.handler"),
				sentry.WithTransactionSource(sentry.SourceTask),
				sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
			}
			txn := sentry.StartTransaction(ctx, "COMMAND /gibopinion", options...)
			defer txn.Finish()

			processedEvent := event.ProcessSlashCommand(txn.Context(), s)
			if processedEvent == nil {
				txn.Status = sentry.SpanStatusInternalError
				return
			}
			err := potalhttp.SendRequest(txn.Context(), processedEvent)
			if err != nil {
				txn.Status = sentry.SpanStatusInternalError
				return
			}

			txn.Status = sentry.SpanStatusOK
		}()
	default:
		transaction.Status = sentry.SpanStatusInvalidArgument
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	transaction.Status = sentry.SpanStatusOK
	w.WriteHeader(http.StatusOK)
}

func InteractionsHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	var payload slack.InteractionCallback
	jsonErr := json.Unmarshal([]byte(r.FormValue("payload")), &payload)
	if jsonErr != nil {
		transaction.Status = sentry.SpanStatusInternalError
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	switch payload.Type {
	case slack.InteractionTypeBlockActions:
		go func() {
			hub := sentry.CurrentHub().Clone()
			ctx := sentry.SetHubOnContext(context.Background(), hub)

			options := []sentry.SpanOption{
				sentry.WithOpName("interaction.handler"),
				sentry.WithTransactionSource(sentry.SourceTask),
				sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
			}
			txn := sentry.StartTransaction(ctx, "INTERACTION block", options...)
			defer txn.Finish()

			processedEvent := event.ProcessBlockInteractionCallbackEvent(txn.Context(), payload)
			if processedEvent == nil {
				txn.Status = sentry.SpanStatusInternalError
				return
			}
			err := potalhttp.SendRequest(txn.Context(), processedEvent)
			if err != nil {
				txn.Status = sentry.SpanStatusInternalError
				return
			}

			txn.Status = sentry.SpanStatusOK
		}()
	case slack.InteractionTypeViewSubmission:
		go func() {
			hub := sentry.CurrentHub().Clone()
			ctx := sentry.SetHubOnContext(context.Background(), hub)

			options := []sentry.SpanOption{
				sentry.WithOpName("interaction.handler"),
				sentry.WithTransactionSource(sentry.SourceTask),
				sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
			}
			txn := sentry.StartTransaction(ctx, "INTERACTION view", options...)
			defer txn.Finish()

			processedEvent := event.ProcessViewInteractionCallbackEvent(txn.Context(), payload)
			if processedEvent == nil {
				txn.Status = sentry.SpanStatusInternalError
				return
			}
			err := potalhttp.SendRequest(txn.Context(), processedEvent)
			if err != nil {
				txn.Status = sentry.SpanStatusInternalError
				return
			}

			txn.Status = sentry.SpanStatusOK
		}()
	default:
		transaction.Status = sentry.SpanStatusInvalidArgument
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	transaction.Status = sentry.SpanStatusOK
	w.WriteHeader(http.StatusOK)
}
