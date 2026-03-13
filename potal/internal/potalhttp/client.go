package potalhttp

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"os"
	"time"

	sentryhttpclient "github.com/getsentry/sentry-go/httpclient"

	"github.com/getsentry/gib-potato/internal/event"
	"github.com/getsentry/sentry-go"
	"github.com/getsentry/sentry-go/attribute"
)

type Client struct {
	Meter sentry.Meter
}

func NewClient(meter sentry.Meter) *Client {
	return &Client{Meter: meter}
}

func (c *Client) SendRequest(ctx context.Context, e event.PotalEvent) error {
	url := os.Getenv("POTAL_URL")

	hub := sentry.GetHubFromContext(ctx)

	body, jsonErr := json.Marshal(e)
	if jsonErr != nil {
		hub.CaptureException(jsonErr)
		slog.ErrorContext(ctx, "failed to marshal event", "error", jsonErr)
		return jsonErr
	}

	r, newReqErr := http.NewRequestWithContext(ctx, "POST", url, bytes.NewBuffer(body))
	if newReqErr != nil {
		hub.CaptureException(newReqErr)
		slog.ErrorContext(ctx, "failed to create API request", "error", newReqErr)
		return newReqErr
	}

	r.Header.Add("Content-Type", "application/json")
	r.Header.Add("Authorization", os.Getenv("POTAL_TOKEN"))

	client := &http.Client{
		Transport: sentryhttpclient.NewSentryRoundTripper(nil),
	}

	start := time.Now()

	res, reqErr := client.Do(r)
	if reqErr != nil {
		hub.CaptureException(reqErr)
		slog.ErrorContext(ctx, "API request failed", "error", reqErr)
		return reqErr
	}
	defer func() {
		_ = res.Body.Close()
	}()

	c.Meter.WithCtx(ctx).Distribution(
		"potal.event.forward_duration",
		float64(time.Since(start).Milliseconds()),
		sentry.WithUnit(sentry.UnitMillisecond),
		sentry.WithAttributes(attribute.Int("http.response.status_code", res.StatusCode)),
	)

	if res.StatusCode == http.StatusOK {
		return nil
	}

	msg := fmt.Sprintf("GibPotato API: Got %s response", res.Status)
	slog.ErrorContext(ctx, "API error response", "status", res.Status, "status_code", res.StatusCode)

	hub.ConfigureScope(func(scope *sentry.Scope) {
		scope.SetLevel(sentry.LevelFatal)
	})
	hub.CaptureMessage(msg)

	return fmt.Errorf("%s", msg)
}
