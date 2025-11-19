package potalhttp

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"

	"github.com/getsentry/gib-potato/internal/event"
	"github.com/getsentry/sentry-go"
)

func SendRequest(ctx context.Context, e event.PotalEvent) error {
	url := os.Getenv("POTAL_URL")

	hub := sentry.GetHubFromContext(ctx)
	txn := sentry.TransactionFromContext(ctx)

	span := txn.StartChild("http.client", sentry.WithDescription(fmt.Sprintf("POST %s", url)))
	defer span.Finish()

	body, jsonErr := json.Marshal(e)
	if jsonErr != nil {
		hub.CaptureException(jsonErr)
		log.Printf("An Error Occured %v", jsonErr)
		return jsonErr
	}

	r, newReqErr := http.NewRequest("POST", url, bytes.NewBuffer(body))
	if newReqErr != nil {
		hub.CaptureException(newReqErr)
		log.Printf("An Error Occured %v", newReqErr)
		return newReqErr
	}

	r.Header.Add("Content-Type", "application/json")
	r.Header.Add("Sentry-Trace", span.ToSentryTrace())
	r.Header.Add("Baggage", span.ToBaggage())
	r.Header.Add("Authorization", os.Getenv("POTAL_TOKEN"))

	client := &http.Client{}
	res, reqErr := client.Do(r)
	if reqErr != nil {
		hub.CaptureException(reqErr)
		span.Status = sentry.SpanStatusInternalError

		log.Printf("An Error Occured %v", reqErr)
		return reqErr
	}
	defer func() {
		if err := res.Body.Close(); err != nil {
			log.Printf("Failed to close response body: %v", err)
		}
	}()

	span.Data = map[string]interface{}{
		"http.response.status_code": res.StatusCode,
	}

	switch res.StatusCode {
	case http.StatusOK:
		span.Status = sentry.SpanStatusOK
		return nil
	case http.StatusUnauthorized:
		fallthrough
	case http.StatusForbidden:
		span.Status = sentry.SpanStatusPermissionDenied
	case http.StatusNotFound:
		span.Status = sentry.SpanStatusNotFound
	case http.StatusInternalServerError:
		span.Status = sentry.SpanStatusInternalError
	}

	msg := fmt.Sprintf("GibPotato API: Got %s response", res.Status)

	hub.ConfigureScope(func(scope *sentry.Scope) {
		scope.SetLevel(sentry.LevelFatal)
	})
	hub.CaptureMessage(msg)

	return fmt.Errorf("%s", msg)
}
