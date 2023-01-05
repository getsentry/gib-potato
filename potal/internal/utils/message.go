package utils

import (
	"strings"
)

func MessageAmount(text string) int {
	return strings.Count(text, ":potato:")
}

func MessageReceivers(text string) []string {
	return getRecievers(text)
}
