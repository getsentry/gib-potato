package main

import (
	"log"
	"net/http"

	"github.com/joho/godotenv"
	"github.com/julienschmidt/httprouter"
)

func main() {
	err := godotenv.Load(".env")
	if err != nil {
		log.Fatalf("Error loading .env file")
	}

	router := httprouter.New()
	router.GET("/", DefaultHandler)
	router.POST("/events", EventsHandler)

	log.Fatal(http.ListenAndServe(":3000", router))
}
