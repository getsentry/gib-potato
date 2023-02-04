package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"

	"github.com/getsentry/sentry-go"
)

func sendRequest(e Event, hub *sentry.Hub, transaction *sentry.Span) {
	url := os.Getenv("POTAL_URL")

	span := transaction.StartChild("http.client")
	span.Description = fmt.Sprintf("POST %s", url)
	defer span.Finish()

	body, jsonErr := json.Marshal(e)
	if jsonErr != nil {
		hub.CaptureException(jsonErr)
		log.Printf("An Error Occured %v", jsonErr)
		return
	}

	r, newReqErr := http.NewRequest("POST", url, bytes.NewBuffer(body))
	if newReqErr != nil {
		hub.CaptureException(newReqErr)
		log.Printf("An Error Occured %v", newReqErr)
		return
	}

	r.Header.Add("Content-Type", "application/json")
	r.Header.Add("Sentry-Trace", span.ToSentryTrace())
	r.Header.Add("Baggage", transaction.ToBaggage())
	r.Header.Add("Authorization", os.Getenv("POTAL_TOKEN"))

	client := &http.Client{}
	res, reqErr := client.Do(r)
	if reqErr != nil {
		hub.CaptureException(reqErr)
		span.Status = sentry.SpanStatusInternalError

		log.Printf("An Error Occured %v", reqErr)
		return
	}
	defer res.Body.Close()

	switch res.StatusCode {
	case http.StatusOK:
		span.Status = sentry.SpanStatusOK
		return
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
}
