ARG GOVERSION=latest
FROM golang:$GOVERSION AS builder

WORKDIR /src
COPY ./potal .

RUN GOOS=linux GOARCH=amd64 CGO_ENABLED=0 go build -o potal -ldflags="-s -w" .

# Includes ca-certificates and tzdata
FROM gcr.io/distroless/static-debian13:nonroot

EXPOSE 8080

COPY --from=builder /src/potal /bin/potal

WORKDIR /var/potal

ENTRYPOINT ["/bin/potal"]
