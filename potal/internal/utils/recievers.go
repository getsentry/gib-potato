package utils

import (
	"regexp"
	"strings"
)

func getRecievers(text string) []string {
	re := regexp.MustCompile(`<@.*?>`)
	users := re.FindAllString(text, -1)

	receivers := []string{}
	unique := map[string]bool{}

	if len(users) > 0 {
		for _, user := range users {
			// Remove <@...> from the user
			user = strings.Trim(user, "<@>")
			// Onyl store unqiue users
			if _, ok := unique[user]; !ok {
				unique[user] = true
				receivers = append(receivers, user)
			}
		}
	}

	return receivers
}
