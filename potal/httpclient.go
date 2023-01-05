package main

import (
	"bytes"
	"encoding/json"
	"log"
	"net/http"
)

func sendRequest(e Event) {
	postBody, _ := json.Marshal(e)
	responseBody := bytes.NewBuffer(postBody)
	resp, err := http.Post("http://localhost:8080/events", "application/json", responseBody)
	if err != nil {
		log.Fatalf("An Error Occured %v", err)
	}
	defer resp.Body.Close()
}
