module github.com/getsentry/gib-potato

go 1.24.0

toolchain go1.24.1

replace github.com/getsentry/sentry-go => github.com/aldy505/sentry-go v0.0.0-20250603124640-b31001c29726

require (
	github.com/getsentry/sentry-go v0.33.0
	github.com/google/go-cmp v0.5.9
	github.com/julienschmidt/httprouter v1.3.0
	github.com/slack-go/slack v0.16.0
)

require (
	github.com/gorilla/websocket v1.5.3 // indirect
	golang.org/x/sys v0.33.0 // indirect
	golang.org/x/text v0.25.0 // indirect
)
