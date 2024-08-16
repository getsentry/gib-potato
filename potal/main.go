package main

import (
	"log"
	"net/http"
	"os"
	"time"

	"github.com/getsentry/sentry-go"
	sentryhttp "github.com/getsentry/sentry-go/http"
	"github.com/slack-go/slack"
)

var slackClient *slack.Client

func main() {
	sentryErr := sentry.Init(sentry.ClientOptions{
		Dsn:                os.Getenv("SENTRY_POTAL_DSN"),
		Release:            os.Getenv("RELEASE"),
		Environment:        os.Getenv("ENVIRONMENT"),
		AttachStacktrace:   true,
		SendDefaultPII:     true,
		EnableTracing:      true,
		TracesSampleRate:   1.0,
		ProfilesSampleRate: 1.0,
	})
	if sentryErr != nil {
		log.Fatalf("An Error Occured: %v", sentryErr)
	}
	defer sentry.Flush(2 * time.Second)

	sentryHandler := sentryhttp.New(sentryhttp.Options{
		Repanic: true,
	})

	slackClient = slack.New(os.Getenv("SLACK_BOT_USER_OAUTH_TOKEN"))

	router := http.NewServeMux()
	router.HandleFunc("GET /", DefaultHandler)
	router.HandleFunc("GET /error", ErrorHandler)
	router.Handle("POST /events", slackVerification(http.HandlerFunc(EventsHandler)))
	router.Handle("POST /slash", slackVerification(http.HandlerFunc(SlashHandler)))
	router.Handle("POST /interactions", slackVerification(http.HandlerFunc(InteractionsHandler)))

	server := http.Server{
		Addr:    ":3000",
		Handler: sentryHandler.Handle(router),
	}

	serverErr := server.ListenAndServe()
	if serverErr != nil {
		sentry.CaptureException(serverErr)
		log.Fatalf("An Error Occured: %v", serverErr)
	}
}
