package main

import (
	"log"
	"net/http"
	"os"
	"time"

	"github.com/getsentry/sentry-go"
	sentryhttp "github.com/getsentry/sentry-go/http"
	"github.com/joho/godotenv"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
)

var slackClient *slack.Client

func main() {
	envErr := godotenv.Load(".env")
	if envErr != nil {
		log.Fatalf("An Error Occured: %v", envErr)
	}

	sentryErr := sentry.Init(sentry.ClientOptions{
		Dsn:              os.Getenv("SENTRY_DSN"),
		Release:          os.Getenv("SENTRY_RELEASE"),
		Environment:      os.Getenv("SENTRY_ENVIRONMENT"),
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

	slackClient = slack.New(os.Getenv("SLACK_BOT_TOKEN"))

	router := httprouter.New()
	router.GET("/", DefaultHandler)
	router.GET("/error", ErrorHandler)
	router.POST("/events", slackVerification(EventsHandler))
	router.POST("/slash", slackVerification(SlashHandler))
	router.POST("/interactions", slackVerification(InteractionsHandler))

	httpErr := http.ListenAndServe(":3000", sentryHandler.Handle(router))
	if httpErr != nil {
		sentry.CaptureException(httpErr)
		log.Fatalf("An Error Occured: %v", httpErr)
	}
}
