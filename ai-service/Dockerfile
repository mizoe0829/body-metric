# Stage 1: Build Go Binary
FROM golang:1.24-alpine AS builder
WORKDIR /app
COPY go.mod ./
COPY main.go ./
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-s -w" -o body-metric-ai main.go

# Stage 2: Minimal Runtime
FROM alpine:latest
WORKDIR /app
RUN apk --no-cache add ca-certificates tzdata
COPY --from=builder /app/body-metric-ai .

EXPOSE 8086

CMD ["./body-metric-ai"]

