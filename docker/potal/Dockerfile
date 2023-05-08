ARG GOVERSION=latest
FROM golang:$GOVERSION AS builder

WORKDIR /src
COPY ./potal .

RUN GOOS=linux GOARCH=amd64 CGO_ENABLED=0 go build -o potal -ldflags="-s -w" .

FROM alpine

EXPOSE 8080

RUN apk add --no-cache ca-certificates tzdata

COPY --from=builder /src/potal /bin/potal

WORKDIR /var/potal

ENTRYPOINT ["/bin/potal"]
