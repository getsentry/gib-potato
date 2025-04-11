package main

import (
	"log"
	"net/http"
	"os"
	"time"

	"github.com/getsentry/sentry-go"
	sentryhttp "github.com/getsentry/sentry-go/http"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
)

var slackClient *slack.Client

func main() {
	sentryErr := sentry.Init(sentry.ClientOptions{
		Dsn:              os.Getenv("SENTRY_POTAL_DSN"),
		Release:          os.Getenv("RELEASE"),
		Environment:      os.Getenv("ENVIRONMENT"),
		AttachStacktrace: true,
		SendDefaultPII:   true,
		EnableTracing:    true,
		TracesSampleRate: 1.0,
	})
	if sentryErr != nil {
		log.Fatalf("An Error Occured: %v", sentryErr)
	}
	// Flush buffered events before the program terminates.
	// Set the timeout to the maximum duration the program can afford to wait.
	defer sentry.Flush(2 * time.Second)

	sentryHandler := sentryhttp.New(sentryhttp.Options{
		Repanic: true,
	})

	slackClient = slack.New(os.Getenv("SLACK_BOT_USER_OAUTH_TOKEN"))

	router := httprouter.New()
	router.GET("/", DefaultHandler)
	router.POST("/events", slackVerification(EventsHandler))
	router.POST("/slash", slackVerification(SlashHandler))
	router.POST("/interactions", slackVerification(InteractionsHandler))

	httpErr := http.ListenAndServe(":3000", sentryHandler.Handle(router))
	if httpErr != nil {
		sentry.CaptureException(httpErr)
		log.Fatalf("An Error Occured: %v", httpErr)
	}
}
