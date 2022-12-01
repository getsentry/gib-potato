package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"time"

	"github.com/getsentry/sentry-go"
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/utils"
	"github.com/joho/godotenv"
	"github.com/valyala/fasthttp/fasthttpadaptor"
)

func SentryHandler() fiber.Handler {
	return func(c *fiber.Ctx) error {
		var r http.Request
		if err := fasthttpadaptor.ConvertRequest(c.Context(), &r, true); err != nil {
			return err
		}

		// Init sentry hub
		hub := sentry.CurrentHub().Clone()
		scope := hub.Scope()
		scope.SetRequest(&r)
		scope.SetRequestBody(utils.CopyBytes(c.Body()))
		c.Locals("sentry-hub", hub)

		// Catch panics
		defer func() {
			if err := recover(); err != nil {
				hub.RecoverWithContext(
					context.WithValue(context.Background(), sentry.RequestContextKey, c),
					err,
				)
			}
		}()

		transaction := sentry.StartTransaction(
			c.Context(),
			r.RequestURI,
			func(s *sentry.Span) {
				s.StartTime = time.Now()
				s.Op = "http.server"
			},
		)
		defer transaction.Finish()

		return c.Next()
	}
}

func main() {
	enverr := godotenv.Load()
	if enverr != nil {
		log.Fatal("Error loading .env file")
	}

	sentryerr := sentry.Init(sentry.ClientOptions{
		Dsn:              os.Getenv("SENTRY_DSN"),
		Debug:            true,
		Environment:      "production",
		TracesSampleRate: 1.0,
		AttachStacktrace: true,
	})
	if sentryerr != nil {
		log.Fatalf("sentry.Init: %s", sentryerr)
	}

	app := fiber.New()

	app.Use(SentryHandler())

	app.Get("/", func(c *fiber.Ctx) error {
		return c.JSON(&fiber.Map{
			"message": "the potato is a lie.",
		})
	})

	app.Get("/panic", func(c *fiber.Ctx) error {
		panic("panic 🔥")
	})

	log.Fatal(app.Listen(":3001"))
}
