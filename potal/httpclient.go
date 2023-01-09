package main

import (
	"bytes"
	"encoding/json"
	"log"
	"net/http"

	"github.com/getsentry/sentry-go"
)

func sendRequest(e Event, hub *sentry.Hub, transaction *sentry.Span) {
	span := transaction.StartChild("http.client")
	span.Description = "POST http://localhost:8080/events"
	defer span.Finish()

	url := "http://localhost:8080/events"

	body, jsonErr := json.Marshal(e)
	if jsonErr != nil {
		hub.CaptureException(jsonErr)
		log.Fatalf("An Error Occured %v", jsonErr)
		return
	}

	r, newReqErr := http.NewRequest("POST", url, bytes.NewBuffer(body))
	if newReqErr != nil {
		hub.CaptureException(newReqErr)
		log.Fatalf("An Error Occured %v", newReqErr)
		return
	}

	r.Header.Add("Content-Type", "application/json")
	r.Header.Add("Sentry-Trace", span.ToSentryTrace())
	r.Header.Add("Baggage", transaction.ToBaggage())

	client := &http.Client{}
	res, reqErr := client.Do(r)
	if reqErr != nil {
		hub.CaptureException(reqErr)
		span.Status = sentry.SpanStatusInternalError

		log.Fatalf("An Error Occured %v", reqErr)
		return
	}
	defer res.Body.Close()

	span.Status = sentry.SpanStatusOK
}
