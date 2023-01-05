package main

import (
	"log"
	"net/http"
	"os"

	"github.com/joho/godotenv"
	"github.com/julienschmidt/httprouter"
	"github.com/slack-go/slack"
)

var slackClient *slack.Client

func main() {
	err := godotenv.Load(".env")
	if err != nil {
		log.Fatalf("An Error Occured %v", err)
	}

	slackClient = slack.New(os.Getenv("SLACK_BOT_TOKEN"))

	router := httprouter.New()
	router.GET("/", DefaultHandler)
	router.POST("/events", EventsHandler)

	log.Fatal(http.ListenAndServe(":3000", router))
}
