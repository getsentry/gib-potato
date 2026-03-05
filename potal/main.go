package main

import (
	"context"
	"log"
	"log/slog"
	"net/http"
	"os"
	"time"

	"github.com/getsentry/gib-potato/internal/potalhttp"
	"github.com/getsentry/sentry-go"
	sentryhttp "github.com/getsentry/sentry-go/http"
	sentryslog "github.com/getsentry/sentry-go/slog"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
)

func main() {
	sentryErr := sentry.Init(sentry.ClientOptions{
		Dsn:              os.Getenv("SENTRY_POTAL_DSN"),
		Release:          os.Getenv("RELEASE"),
		Environment:      os.Getenv("ENVIRONMENT"),
		AttachStacktrace: true,
		SendDefaultPII:   true,
		EnableTracing:    true,
		TracesSampleRate: 1.0,
		EnableLogs:       true,
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

	logLevels := []slog.Level{slog.LevelDebug, slog.LevelInfo, slog.LevelWarn, slog.LevelError}
	if os.Getenv("ENVIRONMENT") != "production" {
		logLevels = append(logLevels, slog.LevelDebug)
	}

	slogHandler := slog.NewMultiHandler(
		slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{
			Level: slog.LevelInfo,
		}),
		sentryslog.Option{
			EventLevel: []slog.Level{},
			LogLevel:   logLevels,
			AddSource:  true,
		}.NewSentryHandler(context.Background()),
	)
	slog.SetDefault(slog.New(slogHandler))

	meter := sentry.NewMeter(context.Background())
	slackClient := slack.New(os.Getenv("SLACK_BOT_USER_OAUTH_TOKEN"))
	potalClient := potalhttp.NewClient(meter)
	h := NewHandler(slackClient, potalClient, meter)

	router := httprouter.New()
	router.GET("/", DefaultHandler)
	router.POST("/events", slackVerification(meter, h.EventsHandler))
	router.POST("/slash", slackVerification(meter, h.SlashHandler))
	router.POST("/interactions", slackVerification(meter, h.InteractionsHandler))

	slog.Info("server starting", "port", 3000)
	httpErr := http.ListenAndServe(":3000", sentryHandler.Handle(router))
	if httpErr != nil {
		sentry.CaptureException(httpErr)
		slog.Error("server failed", "error", httpErr)
		os.Exit(1)
	}
}
