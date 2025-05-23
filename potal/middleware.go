package main

import (
	"bytes"
	"io"
	"net/http"
	"os"

	"github.com/getsentry/sentry-go"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
)

func slackVerification(h httprouter.Handle) httprouter.Handle {
	return func(w http.ResponseWriter, r *http.Request, ps httprouter.Params) {
		logger := sentry.NewLogger(r.Context())

		// Verify the Slack request
		// see https://github.com/slack-go/slack/blob/master/examples/workflow_step/middleware.go
		body, err := io.ReadAll(r.Body)
		defer r.Body.Close()
		if err != nil {
			w.WriteHeader(http.StatusBadRequest)
			logger.Error(r.Context(), "[slackVerification] Failed to read request body")
			return
		}
		r.Body = io.NopCloser(bytes.NewBuffer(body))

		sv, err := slack.NewSecretsVerifier(r.Header, os.Getenv("SLACK_SIGNING_SECRET"))
		if err != nil {
			w.WriteHeader(http.StatusBadRequest)
			logger.Errorf(r.Context(), "[slackVerification] %s", err)
			return
		}

		if _, err := sv.Write(body); err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			logger.Errorf(r.Context(), "[slackVerification] %s", err)
			return
		}

		if err := sv.Ensure(); err != nil {
			w.WriteHeader(http.StatusUnauthorized)
			logger.Errorf(r.Context(), "[slackVerification] %s", err)
			return
		}

		h(w, r, ps)
	}
}
