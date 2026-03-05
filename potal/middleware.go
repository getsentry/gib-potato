package main

import (
	"bytes"
	"io"
	"log/slog"
	"net/http"
	"os"

	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
)

func slackVerification(h httprouter.Handle) httprouter.Handle {
	return func(w http.ResponseWriter, r *http.Request, ps httprouter.Params) {
		ctx := r.Context()

		// Verify the Slack request
		// see https://github.com/slack-go/slack/blob/master/examples/workflow_step/middleware.go
		body, err := io.ReadAll(r.Body)
		defer func() {
			if err := r.Body.Close(); err != nil {
				slog.ErrorContext(ctx, "failed to close request body", "error", err)
			}
		}()
		if err != nil {
			w.WriteHeader(http.StatusBadRequest)
			slog.ErrorContext(ctx, "failed to read request body", "error", err)
			return
		}
		r.Body = io.NopCloser(bytes.NewBuffer(body))

		sv, err := slack.NewSecretsVerifier(r.Header, os.Getenv("SLACK_SIGNING_SECRET"))
		if err != nil {
			w.WriteHeader(http.StatusBadRequest)
			slog.ErrorContext(ctx, "slack verification failed", "error", err)
			return
		}

		if _, err := sv.Write(body); err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			slog.ErrorContext(ctx, "slack verification write failed", "error", err)
			return
		}

		if err := sv.Ensure(); err != nil {
			w.WriteHeader(http.StatusUnauthorized)
			slog.WarnContext(ctx, "slack signature rejected", "error", err)
			return
		}

		h(w, r, ps)
	}
}
