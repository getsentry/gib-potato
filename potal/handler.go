package main

import (
	"context"
	"encoding/json"
	"io"
	"log/slog"
	"net/http"

	"github.com/getsentry/gib-potato/internal/event"
	"github.com/getsentry/gib-potato/internal/potalhttp"
	"github.com/getsentry/sentry-go"
	"github.com/getsentry/sentry-go/attribute"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
	"github.com/slack-go/slack/slackevents"
)

type Handler struct {
	slackClient *slack.Client
	potalClient *potalhttp.Client
	meter       sentry.Meter
}

func NewHandler(slackClient *slack.Client, potalClient *potalhttp.Client, meter sentry.Meter) *Handler {
	return &Handler{
		slackClient: slackClient,
		potalClient: potalClient,
		meter:       meter,
	}
}

func (h *Handler) emitEventMetric(ctx context.Context, name string, eventType string) {
	h.meter.WithCtx(ctx).Count(name, 1,
		sentry.WithAttributes(attribute.String("event_type", eventType)),
	)
}

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

func (h *Handler) EventsHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	body, err := io.ReadAll(r.Body)
	if err != nil {
		slog.ErrorContext(ctx, "failed to read request body", "error", err)
		transaction.Status = sentry.SpanStatusInvalidArgument
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	eventsAPIEvent, err := slackevents.ParseEvent(json.RawMessage(body), slackevents.OptionNoVerifyToken())
	if err != nil {
		slog.ErrorContext(ctx, "failed to parse slack event", "error", err)
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
					txn.SetData("event_type", "direct_message")
					defer txn.Finish()

					processedEvent := event.ProcessDirectMessageEvent(txn.Context(), ev)
					if processedEvent == nil {
						h.emitEventMetric(txn.Context(), "potal.event.skipped", "direct_message")
						slog.DebugContext(txn.Context(), "event skipped", "event_type", "direct_message")
						return
					}
					err := h.potalClient.SendRequest(txn.Context(), processedEvent)
					if err != nil {
						h.emitEventMetric(txn.Context(), "potal.event.forward_error", "direct_message")
						slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "direct_message", "error", err)
						txn.Status = sentry.SpanStatusInternalError
						return
					}

					h.emitEventMetric(txn.Context(), "potal.event.forwarded", "direct_message")
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
					txn.SetData("event_type", "message")
					defer txn.Finish()

					processedEvent := event.ProcessMessageEvent(txn.Context(), ev, h.slackClient)
					if processedEvent == nil {
						h.emitEventMetric(txn.Context(), "potal.event.skipped", "message")
						slog.DebugContext(txn.Context(), "event skipped", "event_type", "message")
						return
					}
					err := h.potalClient.SendRequest(txn.Context(), processedEvent)
					if err != nil {
						h.emitEventMetric(txn.Context(), "potal.event.forward_error", "message")
						slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "message", "error", err)
						txn.Status = sentry.SpanStatusInternalError
						return
					}

					h.emitEventMetric(txn.Context(), "potal.event.forwarded", "message")
					txn.Status = sentry.SpanStatusOK
				}()
			}
		case *slackevents.ReactionAddedEvent:
			go event.ProcessReactionEvent(r.Context(), ev, h.slackClient)
			go func() {
				hub := sentry.CurrentHub().Clone()
				ctx := sentry.SetHubOnContext(context.Background(), hub)

				options := []sentry.SpanOption{
					sentry.WithOpName("event.handler"),
					sentry.WithTransactionSource(sentry.SourceTask),
					sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
				}
				txn := sentry.StartTransaction(ctx, "EVENT reaction_added", options...)
				txn.SetData("event_type", "reaction_added")
				defer txn.Finish()

				processedEvent := event.ProcessReactionEvent(txn.Context(), ev, h.slackClient)
				if processedEvent == nil {
					h.emitEventMetric(txn.Context(), "potal.event.skipped", "reaction_added")
					slog.DebugContext(txn.Context(), "event skipped", "event_type", "reaction_added")
					txn.Status = sentry.SpanStatusInternalError
					return
				}
				err := h.potalClient.SendRequest(txn.Context(), processedEvent)
				if err != nil {
					h.emitEventMetric(txn.Context(), "potal.event.forward_error", "reaction_added")
					slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "reaction_added", "error", err)
					txn.Status = sentry.SpanStatusInternalError
					return
				}

				h.emitEventMetric(txn.Context(), "potal.event.forwarded", "reaction_added")
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
				txn.SetData("event_type", "app_mention")
				defer txn.Finish()

				processedEvent := event.ProcessAppMentionEvent(txn.Context(), ev)
				if processedEvent == nil {
					h.emitEventMetric(txn.Context(), "potal.event.skipped", "app_mention")
					slog.DebugContext(txn.Context(), "event skipped", "event_type", "app_mention")
					txn.Status = sentry.SpanStatusInternalError
					return
				}
				err := h.potalClient.SendRequest(txn.Context(), processedEvent)
				if err != nil {
					h.emitEventMetric(txn.Context(), "potal.event.forward_error", "app_mention")
					slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "app_mention", "error", err)
					txn.Status = sentry.SpanStatusInternalError
					return
				}

				h.emitEventMetric(txn.Context(), "potal.event.forwarded", "app_mention")
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
				txn.SetData("event_type", "app_home_opened")
				defer txn.Finish()

				processedEvent := event.ProcessAppHomeOpenedEvent(txn.Context(), ev)
				if processedEvent == nil {
					h.emitEventMetric(txn.Context(), "potal.event.skipped", "app_home_opened")
					slog.DebugContext(txn.Context(), "event skipped", "event_type", "app_home_opened")
					txn.Status = sentry.SpanStatusInternalError
					return
				}
				err := h.potalClient.SendRequest(txn.Context(), processedEvent)
				if err != nil {
					h.emitEventMetric(txn.Context(), "potal.event.forward_error", "app_home_opened")
					slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "app_home_opened", "error", err)
					txn.Status = sentry.SpanStatusInternalError
					return
				}

				h.emitEventMetric(txn.Context(), "potal.event.forwarded", "app_home_opened")
				txn.Status = sentry.SpanStatusOK
			}()
		case *slackevents.LinkSharedEvent:
			go event.ProcessLinkSharedEvent(r.Context(), ev)
			go func() {
				hub := sentry.CurrentHub().Clone()
				ctx := sentry.SetHubOnContext(context.Background(), hub)

				options := []sentry.SpanOption{
					sentry.WithOpName("event.handler"),
					sentry.WithTransactionSource(sentry.SourceTask),
					sentry.ContinueFromHeaders(transaction.ToSentryTrace(), transaction.ToBaggage()),
				}
				txn := sentry.StartTransaction(ctx, "EVENT link_shared", options...)
				txn.SetData("event_type", "link_shared")
				defer txn.Finish()

				processedEvent := event.ProcessLinkSharedEvent(txn.Context(), ev)
				if processedEvent == nil {
					h.emitEventMetric(txn.Context(), "potal.event.skipped", "link_shared")
					slog.DebugContext(txn.Context(), "event skipped", "event_type", "link_shared")
					return
				}
				err := h.potalClient.SendRequest(txn.Context(), processedEvent)
				if err != nil {
					h.emitEventMetric(txn.Context(), "potal.event.forward_error", "link_shared")
					slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "link_shared", "error", err)
					txn.Status = sentry.SpanStatusInternalError
					return
				}

				h.emitEventMetric(txn.Context(), "potal.event.forwarded", "link_shared")
				txn.Status = sentry.SpanStatusOK
			}()
		default:
			slog.WarnContext(ctx, "unhandled callback event type", "type", innerEvent.Type)
		}
	}

	transaction.Status = sentry.SpanStatusOK
	w.WriteHeader(http.StatusOK)
}

func (h *Handler) SlashHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	s, err := slack.SlashCommandParse(r)
	if err != nil {
		slog.ErrorContext(ctx, "failed to parse slash command", "error", err)
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
			txn.SetData("event_type", "gibopinion")
			defer txn.Finish()

			processedEvent := event.ProcessSlashCommand(txn.Context(), s)
			if processedEvent == nil {
				h.emitEventMetric(txn.Context(), "potal.event.skipped", "slash_command")
				slog.DebugContext(txn.Context(), "event skipped", "event_type", "slash_command")
				txn.Status = sentry.SpanStatusInternalError
				return
			}
			err := h.potalClient.SendRequest(txn.Context(), processedEvent)
			if err != nil {
				h.emitEventMetric(txn.Context(), "potal.event.forward_error", "slash_command")
				slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "slash_command", "error", err)
				txn.Status = sentry.SpanStatusInternalError
				return
			}

			h.emitEventMetric(txn.Context(), "potal.event.forwarded", "slash_command")
			txn.Status = sentry.SpanStatusOK
		}()
	default:
		slog.WarnContext(ctx, "unknown slash command", "command", s.Command)
		transaction.Status = sentry.SpanStatusInvalidArgument
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	transaction.Status = sentry.SpanStatusOK
	w.WriteHeader(http.StatusOK)
}

func (h *Handler) InteractionsHandler(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	// Overwrite transaction source with something usefull
	ctx := r.Context()
	transaction := sentry.TransactionFromContext(ctx)
	transaction.Source = sentry.SourceRoute

	var payload slack.InteractionCallback
	jsonErr := json.Unmarshal([]byte(r.FormValue("payload")), &payload)
	if jsonErr != nil {
		slog.ErrorContext(ctx, "failed to parse interaction payload", "error", jsonErr)
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
			txn.SetData("event_type", "block")
			defer txn.Finish()

			processedEvent := event.ProcessInteractionCallbackEvent(txn.Context(), payload)
			if processedEvent == nil {
				h.emitEventMetric(txn.Context(), "potal.event.skipped", "interaction_callback")
				slog.DebugContext(txn.Context(), "event skipped", "event_type", "interaction_callback")
				txn.Status = sentry.SpanStatusInternalError
				return
			}
			err := h.potalClient.SendRequest(txn.Context(), processedEvent)
			if err != nil {
				h.emitEventMetric(txn.Context(), "potal.event.forward_error", "interaction_callback")
				slog.ErrorContext(txn.Context(), "failed to forward event", "event_type", "interaction_callback", "error", err)
				txn.Status = sentry.SpanStatusInternalError
				return
			}

			h.emitEventMetric(txn.Context(), "potal.event.forwarded", "interaction_callback")
			txn.Status = sentry.SpanStatusOK
		}()
	default:
		slog.WarnContext(ctx, "unknown interaction type", "type", payload.Type)
		transaction.Status = sentry.SpanStatusInvalidArgument
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	transaction.Status = sentry.SpanStatusOK
	w.WriteHeader(http.StatusOK)
}
