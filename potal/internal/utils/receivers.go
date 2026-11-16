package utils

import (
	"regexp"
	"strings"
)

var (
	mentionPattern    = regexp.MustCompile(`<@.*?>`)
	codeBlockPattern  = regexp.MustCompile("(?s)```.*?```")
	inlineCodePattern = regexp.MustCompile("`[^`\n]*`")
)

func getRecievers(text string) []string {
	// Slack keeps mentions inside code as <@...>, but they are not receivers
	text = codeBlockPattern.ReplaceAllString(text, "")
	text = inlineCodePattern.ReplaceAllString(text, "")

	users := mentionPattern.FindAllString(text, -1)

	receivers := []string{}
	unique := map[string]bool{}

	for _, user := range users {
		// Remove <@...> from the user
		user = strings.Trim(user, "<@>")
		// Only store unique users
		if !unique[user] {
			unique[user] = true
			receivers = append(receivers, user)
		}
	}

	return receivers
}
