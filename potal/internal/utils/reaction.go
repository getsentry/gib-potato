package utils

import "slices"

func ReactionReceivers(text string, user string, itemUser string) []string {
	receivers := getRecievers(text)

	// If no one was mentioned in the the message text, return the creator of the message
	if len(receivers) == 0 {
		return []string{itemUser}
	}

	// If only one person is mentioned and the person reacting is them, return the creator of the message
	if len(receivers) == 1 {
		if receivers[0] == user {
			return []string{itemUser}
		}
	}

	// Otherwise, remove the person reacting from the receivers
	return slices.DeleteFunc(receivers, func(v string) bool { return v == user })
}
