package potalhttp

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"

	sentryhttpclient "github.com/getsentry/sentry-go/httpclient"

	"github.com/getsentry/gib-potato/internal/event"
	"github.com/getsentry/sentry-go"
)

func SendRequest(ctx context.Context, e event.PotalEvent) error {
	url := os.Getenv("POTAL_URL")

	hub := sentry.GetHubFromContext(ctx)

	body, jsonErr := json.Marshal(e)
	if jsonErr != nil {
		hub.CaptureException(jsonErr)
		log.Printf("An Error Occured %v", jsonErr)
		return jsonErr
	}

	r, newReqErr := http.NewRequestWithContext(ctx, "POST", url, bytes.NewBuffer(body))
	if newReqErr != nil {
		hub.CaptureException(newReqErr)
		log.Printf("An Error Occured %v", newReqErr)
		return newReqErr
	}

	r.Header.Add("Content-Type", "application/json")
	r.Header.Add("Authorization", os.Getenv("POTAL_TOKEN"))

	// sentryhttpclient automatically creates spans, injects tracing headers, and sets status
	client := &http.Client{
		Transport: sentryhttpclient.NewSentryRoundTripper(nil),
	}

	res, reqErr := client.Do(r)
	if reqErr != nil {
		hub.CaptureException(reqErr)
		log.Printf("An Error Occured %v", reqErr)
		return reqErr
	}
	defer res.Body.Close()

	if res.StatusCode == http.StatusOK {
		return nil
	}

	msg := fmt.Sprintf("GibPotato API: Got %s response", res.Status)

	hub.ConfigureScope(func(scope *sentry.Scope) {
		scope.SetLevel(sentry.LevelFatal)
	})
	hub.CaptureMessage(msg)

	return fmt.Errorf("%s", msg)
}
