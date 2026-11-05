package utils

import (
	"strings"

	"github.com/getsentry/gib-potato/internal/constants"
)

func MessageAmount(text string) int {
	return strings.Count(text, ":"+constants.Potato+":")
}

func MessageSuperPotatoAmount(text string) int {
	return strings.Count(text, ":"+constants.SuperPotato+":")
}

func MessageReceivers(text string) []string {
	return getRecievers(text)
}
