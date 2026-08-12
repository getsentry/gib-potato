package utils

import (
	"regexp"
	"strings"

	"github.com/getsentry/gib-potato/internal/constants"
)

var scrubPattern = regexp.MustCompile(`<@[^>]+>|:` + constants.Potato + `:|:` + constants.PotatoVoucher + `:`)

func ScrubText(text string) string {
	matches := scrubPattern.FindAllString(text, -1)
	return strings.Join(matches, " ")
}
